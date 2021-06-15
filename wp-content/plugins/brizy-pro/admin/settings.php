<?php

class BrizyPro_Admin_Settings {

	/**
	 * @return BrizyPro_Admin_Settings
	 */
	public static function _init() {

		static $instance;

		return $instance ? $instance : $instance = new self();
	}

	private function __construct() {
		add_action( 'brizy_settings_capability_options', array( $this, 'add_capability_options' ) );
	}

	public function add_capability_options( $capability_options ) {
		$capability_options[] = array(
			'label'      => __( 'Limited Access', 'brizy-pro' ),
			'capability' => Brizy_Admin_Capabilities::CAP_EDIT_CONTENT_ONLY
		);

		return $capability_options;
	}


}