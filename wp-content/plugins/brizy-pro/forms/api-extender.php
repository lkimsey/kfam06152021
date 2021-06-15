<?php


class BrizyPro_Forms_ApiExtender {

	const AJAX_AUTHENTICATE_INTEGRATION = '_authenticate_integration';
	const AJAX_GET_LISTS = '_get_lists';
	const AJAX_GET_FIELDS = '_get_fields';
	const AJAX_GET_SERVICE_ACCOUNTS = '_service_accounts';
	const AJAX_DELETE_SERVICE_ACCOUNT = '_delete_service_account';
	const AJAX_CREATE_GROUP = '_create_group';
	const AJAX_GET_GROUP_PROPERTIES = '_get_group_properties';
	const AJAX_GET_ACCOUNT_PROPERTIES = '_get_account_properties';

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * BrizyPro_Forms_ApiExtender constructor.
	 */
	public function __construct() {

		add_filter( 'brizy_add_integration_accounts', array( $this, 'addIntegrationAccounts' ), 10, 2 );

		add_filter( 'brizy_prepare_integration', array( $this, 'populateIntegrationFolders' ), 10, 2 );
		add_filter( 'brizy_prepare_integration', array( $this, 'populateIntegrationLists' ), 10, 2 );
		add_filter( 'brizy_prepare_integration', array( $this, 'populateIntegrationFields' ), 10, 2 );
		add_filter( 'brizy_prepare_integration', array( $this, 'populateGroupProperties' ), 10, 2 );

		add_filter( 'brizy_update_integration', array( $this, 'updateIntegrationAccounts' ), 10, 2 );
		add_action( 'brizy_submit_form', array( $this, 'formSubmit' ), 10, 3 );
		add_action( 'brizy_form_email_body', array( $this, 'formEmailBody' ), 10, 3 );
		add_action( 'brizy_form_email_subject', array( $this, 'formEmailSubject' ), 10, 3 );

		if ( Brizy_Editor_User::is_user_allowed() ) {
			$pref = 'wp_ajax_' . Brizy_Editor::prefix();
			add_action( $pref . self::AJAX_AUTHENTICATE_INTEGRATION, array( $this, 'authenticateIntegration' ) );
			add_action( $pref . self::AJAX_GET_SERVICE_ACCOUNTS, array( $this, 'getServiceAccountList' ) );
			add_action( $pref . self::AJAX_DELETE_SERVICE_ACCOUNT, array( $this, 'deleteServiceAccount' ) );
			add_action( $pref . self::AJAX_CREATE_GROUP, array( $this, 'createGroup' ) );
			add_action( $pref . self::AJAX_GET_ACCOUNT_PROPERTIES, array( $this, 'getAccountProperties' ) );
		}
	}


	/**
	 * @param Brizy_Editor_Forms_AbstractIntegration $integration
	 *
	 * @return \BrizyForms\Service\Service
	 * @throws \BrizyForms\Exception\ServiceException
	 */
	protected function getIntegrationService( Brizy_Editor_Forms_AbstractIntegration $integration ) {

		/**
		 * @var \BrizyForms\Service\Service $service ;
		 */
		$service = \BrizyForms\ServiceFactory::getInstance( $integration->getId() );

		$accountManager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );

		if ( $usedAccountId = $integration->getUsedAccount() ) {
			$account = $accountManager->getAccount( $usedAccountId );

			// initialize an instance of AuthenticationData
			$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToOptionValue() );
			$service->setAuthenticationData( $authData );
		}

		return $service;

	}

	/**
	 * @param \BrizyForms\Service\Service $service
	 * @param Brizy_Editor_Forms_Form $form
	 * @param $fields
	 * @param Brizy_Editor_Forms_AbstractIntegration $integration
	 *
	 * @throws \BrizyForms\Exception\AuthenticationDataException
	 * @throws \BrizyForms\Exception\FieldMapException
	 */
	public function formSubmit( \BrizyForms\Service\Service $service, $fields, Brizy_Editor_Forms_AbstractIntegration $integration ) {
		$accountManager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );

		// initialize an instance of AuthenticationData
		$account = $accountManager->getAccount( $integration->getUsedAccount() );

		$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToOptionValue() );
		$service->setAuthenticationData( $authData );

		$fieldMap = new \BrizyForms\FieldMap( array_map( function ( $obj ) {
			return get_object_vars( $obj );
		}, json_decode( $integration->getFieldsMap() ) ) );

		$data = array_map( function ( $obj ) {
			return new \BrizyForms\Model\Data( $obj->name, $obj->value );
		}, $fields );

		$service->createMember( $fieldMap, $integration->getUsedList(), $data, $integration->isConfirmationNeeded() );
	}

	/**
	 * @param Brizy_Editor_Forms_AbstractIntegration $integration
	 * @param Brizy_Editor_Forms_Form $form
	 *
	 * @return Brizy_Editor_Forms_AbstractIntegration
	 * @throws Exception
	 */
	public function addIntegrationAccounts( Brizy_Editor_Forms_AbstractIntegration $integration ) {
		if ( $integration instanceof Brizy_Editor_Forms_ServiceIntegration ) {
			$accountManager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );
			$integration->setAccounts( $accountManager->getAccountsByService( $integration->getid() ) );
		}

		return $integration;
	}

	/**
	 * @param Brizy_Editor_Forms_AbstractIntegration $integration
	 * @param Brizy_Editor_Forms_Form $form
	 *
	 * @return Brizy_Editor_Forms_AbstractIntegration
	 * @throws Exception
	 */
	public function updateIntegrationAccounts( Brizy_Editor_Forms_AbstractIntegration $integration, Brizy_Editor_Forms_Form $form ) {
		if ( $integration instanceof Brizy_Editor_Forms_ServiceIntegration ) {
			$accountManager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );
			// detect integration changes and reset the values when account or lists is changed
			$oldIntegration = $form->getIntegration( $integration->getid() );

			if ( ! $oldIntegration ) {
				$this->error( 404, "Integration not found" );
			}

			$integration->setAccounts( $accountManager->getAccountsByService( $integration->getId() ) );

			// reset fields and lists if the account is changed
			if ( $oldIntegration->getUsedFolder() != $integration->getUsedFolder() ) {
				$integration->setLists( array() );
				$integration->setFields( array() );
				$integration->setUsedList( null );
				$integration->setFieldsMap( '[]' );
				$integration->setCompleted( false );
				$integration->setConfirmationNeeded( false );
			}

			// reset fields and lists if the account is changed
			if ( $oldIntegration->getUsedAccount() != $integration->getUsedAccount() ) {
				$integration->setLists( array() );
				$integration->setFields( array() );
				$integration->setUsedList( null );
				$integration->setFieldsMap( '[]' );
				$integration->setCompleted( false );
				$integration->setConfirmationNeeded( false );
			}

			// reset fields and fieldmap if the used list is changed
			if ( $oldIntegration->getUsedList() != $integration->getUsedList() ) {
				$integration->setFields( array() );
				$integration->setFieldsMap( '[]' );
				$integration->setCompleted( false );
			}


			// check if there are autogenerate fields
			$hasAutogenerateFields = false;
			foreach ( json_decode( $integration->getFieldsMap() ) as $field ) {
				if ( $field->target == '_auto_generate' ) {
					$hasAutogenerateFields = true;
					break;
				}
			}

			$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
			$service = $this->getIntegrationService( $integration );

			if ( $hasAutogenerateFields ) {

				$fieldMap = new \BrizyForms\FieldMap( array_map( function ( $obj ) {
					return get_object_vars( $obj );
				}, json_decode( $integration->getFieldsMap() ) ) );

				try {
					$newFieldMap = $service->createFields( $fieldMap, $integration->getUsedList() );

					$fieldMap = array();

					/**
					 * @var \BrizyForms\Model\FieldLink $fieldLink ;
					 */
					foreach ( $newFieldMap->toArray() as $fieldLink ) {
						$fieldMap[] = (object) array(
							'sourceId'    => $fieldLink->getSourceId(),
							'sourceTitle' => $fieldLink->getSourceId(),
							'target'      => $fieldLink->getTarget(),
						);
					}

					$integration->setFieldsMap( json_encode( $fieldMap ) );


				} catch ( Exception $e ) {
					$this->error( 400, $e->getMessage() );
				}

//				try {
//					$list = new \BrizyForms\Model\Group();
//					if ( $integration->getUsedList() ) {
//						$userlist = $integration->getUsedListObject();
//						$list     = new \BrizyForms\Model\Group( $userlist->getId(), $userlist->getName() );
//					}
//
//					$fields = $service->getFields( $list );
//
//					$integration->setFields( array() );
//					foreach ( $fields as $field ) {
//						$integration->addField( new Brizy_Editor_Forms_Field( $field ) );
//					}
//
//				} catch ( Exception $e ) {
//					$this->error( 400, $e->getMessage() );
//				}
			}

			$integration->setHasConfirmation( $service->hasConfirmation() );

			// save groups in integration
			$form->updateIntegration( $integration );
			$manager->addForm( $form );

		}

		return apply_filters( 'brizy_create_integration', $integration, $form );;
	}


	/**
	 * @throws \BrizyForms\Exception\AuthenticationDataException
	 * @throws \BrizyForms\Exception\ServiceException
	 */
	public function authenticateIntegration() {

		$manager        = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
		$accountManager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );

		$form = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 404, "Form not found" );
		}

		$integrationId = $_REQUEST['integration'];

		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		if ( ! $integration ) {
			$this->error( 404, "Integration not found" );
		}

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			$this->error( 400, "Unsupported integration" );
		}

		/**
		 * @var \BrizyForms\Service\Service $service ;
		 */
		$service = $this->getIntegrationService( $integration );

		$data          = json_decode( file_get_contents( 'php://input' ) );
		$data->service = $integration->getId();
		$account       = new Brizy_Editor_Accounts_Account( get_object_vars( $data ) );

		if ( $accountManager->hasAccount( $account ) ) {
			$this->error( 400, "Duplicate account" );
		}

		$response = null;

		try {
			// initialize an instance of AuthenticationData
			$authData = new \BrizyForms\Model\AuthenticationData( $account->convertToAuthData() );
			$service->setAuthenticationData( $authData );

			$response = $service->authenticate();

		} catch ( Exception $e ) {
			$this->error( 401, $e->getMessage() );
		}

		if ( $response instanceof \BrizyForms\Model\Response ) {

			if ( $response->getCode() == 200 ) {

				$serviceAccount = $service->getAccount();

				if ( $serviceAccount instanceof \BrizyForms\Model\Account ) {
					$account->setName( $serviceAccount->getName() );
				} else {
					$this->error( 500, "Unable to obtain account data." );
				}

				$folders = $service->getFolders();
				if ( $folders ) {
					$folders = array_map( function ( $folder ) {
						return Brizy_Editor_Forms_Folder::createFromJson( $folder->jsonSerialize() );
					}, $folders );

					$integration->setFolders( $folders );
				}

				if ( $form->updateIntegration( $integration ) ) {
					$manager->addForm( $form );
					$accountManager->addAccount( $account );
					$this->success( $account );
				}
			} else {
				$this->error( 401, $response->getMessage() );
			}
		}

		$this->error( 500, 'Failed to authenticate service' );
	}


	/**
	 * @param Brizy_Editor_Forms_ServiceIntegration $integration
	 * @param Brizy_Editor_Forms_Form $form
	 *
	 * @return Brizy_Editor_Forms_ServiceIntegration
	 * @throws \BrizyForms\Exception\AuthenticationDataException
	 * @throws \BrizyForms\Exception\ServiceException
	 */
	public function populateIntegrationFolders( Brizy_Editor_Forms_AbstractIntegration $integration, Brizy_Editor_Forms_Form $form ) {

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			return $integration;
		}

		if ( ! $integration->getUsedAccount() ) {
			return $integration;
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			if ( $service instanceof \BrizyForms\Service\CampaignMonitorService ||
			     $service instanceof \BrizyForms\Service\SendinBlueService ) {
				$integration->setFolders( $service->getFolders() );
			}

		} catch ( \BrizyForms\Exception\AccessDeniedException $e ) {
			$integration->setFolders( [] );
		}

		return $integration;
	}


	/**
	 * @param Brizy_Editor_Forms_ServiceIntegration $integration
	 * @param Brizy_Editor_Forms_Form $form
	 *
	 * @return Brizy_Editor_Forms_ServiceIntegration
	 * @throws \BrizyForms\Exception\AuthenticationDataException
	 * @throws \BrizyForms\Exception\ServiceException
	 */
	public function populateIntegrationLists( Brizy_Editor_Forms_AbstractIntegration $integration, Brizy_Editor_Forms_Form $form ) {

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			return $integration;
		}

		if ( ! $integration->getUsedAccount() ) {
			return $integration;
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			$folder = null;

			if ( $folderId = $integration->getUsedFolder() ) {
				$folder = new \BrizyForms\Model\Folder( $folderId );
			}

			$groups = $service->getGroups( $folder );
			$integration->setLists( array() );
			foreach ( $groups as $group ) {
				$integration->addList( new Brizy_Editor_Forms_Group( $group ) );
			}

		} catch ( \BrizyForms\Exception\AccessDeniedException $e ) {
			$integration->setLists( null );
		} catch ( \BrizyForms\Exception\ServiceException $e ) {
			$integration->setLists( null );
		}

		return $integration;
	}


	/**
	 * @param Brizy_Editor_Forms_ServiceIntegration $integration
	 * @param Brizy_Editor_Forms_Form $form
	 *
	 * @return Brizy_Editor_Forms_ServiceIntegration
	 * @throws Exception
	 */
	public function populateIntegrationFields( Brizy_Editor_Forms_AbstractIntegration $integration, Brizy_Editor_Forms_Form $form ) {

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			return $integration;
		}

		if ( ! $integration->getUsedAccount() ) {
			return $integration;
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			$list = new \BrizyForms\Model\Group();
			if ( $integration->getUsedList() ) {
				$usedList = $integration->getUsedListObject();
				if ( $usedList ) {
					$list = new \BrizyForms\Model\Group( $usedList->getId(), $usedList->getName() );
				}
			}

			$fields = $service->getFields( $list );

			$integration->setFields( array() );
			foreach ( $fields as $field ) {
				$integration->addField( new Brizy_Editor_Forms_Field( $field ) );
			}

		} catch ( Exception $e ) {
			Brizy_Logger::instance()->error( $e->getMessage(), [ $e ] );
		}

		return $integration;
	}

	public function populateGroupProperties( Brizy_Editor_Forms_AbstractIntegration $integration, Brizy_Editor_Forms_Form $form ) {

		if ( $integration instanceof Brizy_Editor_Forms_WordpressIntegration ) {
			return $integration;
		}

		if ( ! $integration->getUsedAccount() ) {
			return $integration;
		}

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			$data = $service->getGroupProperties();

			$integration->setListProperties( $data );

		} catch ( Exception $e ) {

		}

		return $integration;
	}

	/**
	 * @throws Exception
	 */
	public function getServiceAccountList() {

		$this->authorize();

		$manager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );

		$serviceId = $_REQUEST['service'];
		if ( ! $serviceId ) {
			$this->error( 400, "Invalid form service id" );
		}

		$accounts = $manager->getAccountsByService( $serviceId );

		$this->success( $accounts );
	}

	/**
	 * @throws Exception
	 */
	public function deleteServiceAccount() {

		$this->authorize();

		$manager = new Brizy_Editor_Accounts_ServiceAccountManager( Brizy_Editor_Project::get() );

		$serviceId = $_REQUEST['service'];
		if ( ! $serviceId ) {
			$this->error( 400, "Invalid form service id" );
		}

		$accountId = $_REQUEST['account'];
		if ( ! $accountId ) {
			$this->error( 400, "Invalid account id" );
		}

		$manager->deleteAccountById( $accountId );

		$this->success( null );
	}

	/**
	 * @throws Exception
	 */
	public function createGroup() {

		$this->authorize();

		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );

		$form = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}
		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );


		$object = json_decode( file_get_contents( 'php://input' ) );

		if ( ! is_object( $object ) ) {
			$this->error( 400, "Invalid group data" );
		}

		$groupData = new \BrizyForms\Model\GroupData( get_object_vars( $object ) );

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			if ( $service ) {
				try {
					$group = $service->createGroup( $groupData );
					$this->success( $group );
				} catch ( Exception $e ) {
					$this->error( 400, $e->getMessage() );
				}
			}
		} catch ( Exception $e ) {
			$this->error( 500, $e->getMessage() );
		}

		$this->error( 404, 'Integration not found' );
	}

//	public function createFields() {
//
//		$this->authorize();
//
//		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );
//
//		$form = $manager->getForm( $_REQUEST['formId'] );
//		if ( ! $form ) {
//			$this->error( 400, "Invalid form id" );
//		}
//
//		$integrationId = $_REQUEST['integration'];
//		if ( ! $integrationId ) {
//			$this->error( 400, "Invalid form integration" );
//		}
//
//		$integration = $form->getIntegration( $integrationId );
//
//
//		$fields = json_decode( file_get_contents( 'php://input' ) );
//
//		if ( ! is_array( $fields ) ) {
//			$this->error( 400, "Invalid group data" );
//		}
//
//		try {
//			/**
//			 * @var \BrizyForms\Service\Service $service ;
//			 */
//			$service = $this->getIntegrationService($integration);
//
//			if ( $service ) {
//				try {
//
//					$fieldMap = new \BrizyForms\FieldMap( array_map( function ( $obj ) {
//						return get_object_vars( $obj );
//					}, $fields ) );
//
//					$fields = $service->createFields( $fieldMap );
//
//					$integration->setFields( array() );
//					foreach ( $fields as $field ) {
//						$integration->addField( new Brizy_Editor_Forms_Field( $field ) );
//					}
//
//					// save groups in integration
//					$form->updateIntegration( $integration );
//					$manager->addForm( $form );
//
//					$this->success( $fields );
//				} catch ( Exception $e ) {
//					$this->error( 400, $e->getMessage() );
//				}
//			}
//		} catch ( Exception $e ) {
//			$this->error( 500, $e->getMessage() );
//		}
//
//		$this->error( 404, 'Integration not found' );
//	}


	public function getAccountProperties() {
		$this->authorize();

		$manager = new Brizy_Editor_Forms_FormManager( Brizy_Editor_Project::get() );

		$form = $manager->getForm( $_REQUEST['formId'] );
		if ( ! $form ) {
			$this->error( 400, "Invalid form id" );
		}

		$integrationId = $_REQUEST['integration'];
		if ( ! $integrationId ) {
			$this->error( 400, "Invalid form integration" );
		}

		$integration = $form->getIntegration( $integrationId );

		try {
			/**
			 * @var \BrizyForms\Service\Service $service ;
			 */
			$service = $this->getIntegrationService( $integration );

			$data = $service->getAccountProperties();

			$this->success( $data ? $data : array() );
		} catch ( Exception $e ) {
			$this->error( 500, $e->getMessage() );
		}
	}

	/**
	 * @param Brizy_Editor_Forms_Form $form
	 * @param $fields
	 * @param $body
	 *
	 * @return mixed|string
	 */
	public function formEmailBody( $body, $fields, Brizy_Editor_Forms_Form $form ) {

		if ( $form->hasEmailTemplate() ) {
			$body = $form->getEmailTemplate();
			foreach ( $fields as $field ) {
				$body = str_replace( "{{" . $field->label . "}}", $field->value, $body );
			}

			$body = str_replace( [ "\n" ], [ "<br>" ], $body );
		}

		return $body;
	}

	public function formEmailSubject( $subject, $fields, Brizy_Editor_Forms_Form $form ) {

		foreach ( (array)$fields as $field ) {
			$subject = str_replace( "{{" . $field->label . "}}", $field->value, $subject );
		}

		return $subject;
	}

	private function authorize() {
		if ( ! wp_verify_nonce( $_REQUEST['hash'], Brizy_Editor_API::nonce ) ) {
			wp_send_json_error( array( 'code' => 400, 'message' => 'Bad request' ), 400 );
		}
	}

	protected function error( $code, $message ) {
		wp_send_json_error( array( 'code' => $code, 'message' => $message ), $code );
	}

	protected function success( $data ) {
		wp_send_json_success( $data );
	}

}