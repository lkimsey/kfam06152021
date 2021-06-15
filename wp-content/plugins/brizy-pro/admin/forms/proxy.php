<?php


class BrizyPro_Admin_Forms_Proxy {

	const ACTION_SUBMIT = '_form_proxy';
	const ACTION_INTEGRATION_CONNECT = '_form_integration_auth';

	/**
	 * @var Brizy_Editor_Project
	 */
	private $project;

	/**
	 * BrizyPro_Admin_Cloud_Proxy constructor.
	 */
	public function __construct() {
		$pref = 'wp_ajax_' . Brizy_Editor::prefix();
		add_action( $pref . self::ACTION_SUBMIT, array( $this, 'handleRequest' ) );
		add_action( $pref . self::ACTION_INTEGRATION_CONNECT, array( $this, 'handleIntegrationAuthRequest' ) );
		add_filter( 'brizy_editor_config', array( $this, 'addAjaxActionsInConfig' ) );
	}

	/**
	 * @param $config
	 *
	 * @return mixed
	 */
	public function addAjaxActionsInConfig( $config ) {

		$config['wp']['api']['formProxy']           = Brizy_Editor::prefix(self::ACTION_SUBMIT );
		$config['wp']['api']['formIntegrationAuth'] = Brizy_Editor::prefix(self::ACTION_INTEGRATION_CONNECT );

		return $config;
	}

	/**
	 * @throws Brizy_Editor_Exceptions_NotFound
	 */
	public function handleIntegrationAuthRequest() {
		$vars = $_REQUEST;

		// redirect the request
		$token = Brizy_Editor_Project::get()->getMetaValue( 'brizy-cloud-token' );

		if ( ! $token ) {
			wp_send_json_error( null, 401 );
			exit;
		}

		if ( ! isset( $vars['appId'] ) ) {
			wp_send_json_error( 'Invalid app id', 400 );
			exit;
		}

		$appId = $vars['appId'];

		$url = BrizyPro_Config::BRIZY_APPLICATION_INTEGRATION_URL . "/{$appId}/auth/login?token={$token}";

		wp_redirect( $url );
		exit;
	}

	/**
	 * @throws Brizy_Editor_Exceptions_NotFound
	 */
	public function handleRequest() {

		$vars = $_REQUEST;

		$project = Brizy_Editor_Project::get();

		// redirect the request
		$token          = $project->getMetaValue( 'brizy-cloud-token' );
		$cloudProjectId = $project->getMetaValue( 'brizy-cloud-project' );

		if ( ! $token || ! $cloudProjectId ) {
			wp_send_json_error( null, 401 );
			exit;
		}

		if ( ! isset( $vars['endpoint'] ) ) {
			wp_send_json_error( null, 400 );
			exit;
		}

		$http     = new WP_Http();
		$url      = BrizyPro_Config::BRIZY_APPLICATION_INTEGRATION_URL . $vars['endpoint'];
		$response = $http->request( $url, array(
			'headers' => array(
				'X-TOKEN'      => $token,
				"Content-Type" => "application/x-www-form-urlencoded; charset=UTF-8"
			),
			'body'    => file_get_contents( "php://input" ),
			'method'  => $_SERVER['REQUEST_METHOD'],
		) );

		$status = wp_remote_retrieve_response_code( $response );

		if ( $response instanceof WP_Error ) {
			wp_send_json_error( wp_remote_retrieve_response_message( $response ), $status );
			exit;
		}

		wp_send_json_success( wp_remote_retrieve_body( $response ), $status );
		exit;
	}
}