<?php

class BrizyPro_Admin_WhiteLabel {

	const KEY = 'brizy-white-label';
	const WL_SESSION_KEY = 'brizy-white-label-enabled';

	/**
	 * @var string[]
	 */
	private $values;

	/**
	 * @return BrizyPro_Admin_WhiteLabel
	 * @throws Exception
	 */
	public static function _init() {

		static $instance;

		return $instance ? $instance : $instance = new self();
	}

	/**
	 * BrizyPro_Admin_WhiteLabel constructor.
	 * @throws Exception
	 */
	private function __construct() {

		add_action( 'admin_init', array( $this, 'enableWhiteLabelInterface' ) );
		add_action( 'wp_logout', array( $this, 'disableWhiteLabelInterface' ) );
		add_action( 'admin_init', array( $this, '_action_enqueue_editor_assets' ), 9999 );

		if ( is_multisite() ) {
			add_action( 'network_admin_menu', array( $this, 'actionRegisterPage' ),11 );
		} else {
			if ( get_transient( self::WL_SESSION_KEY ) == 1 ) {
				add_action( 'admin_menu', array( $this, 'actionRegisterPage' ), 11 );
			}
		}

		// hide traces of brizy if the white labels was activated
		if ( $this->getEnabled() ) {
			if ( is_admin() && ! is_network_admin() ) {
				add_filter( 'all_plugins', [ $this, 'all_plugins' ] );
				add_filter( 'plugin_row_meta', [ $this, 'plugin_row_meta' ], 10, 2 );
				add_filter( 'after_plugin_row_brizy-pro/brizy-pro.php', [ $this, 'after_plugin_row' ], 9, 2 );
				add_filter( 'after_plugin_row_brizy/brizy.php', [ $this, 'after_plugin_row' ], 9, 2 );
				add_action( 'core_upgrade_preamble', [ $this, 'core_upgrade_preamble' ] );
				add_action( 'admin_head', [ $this, 'admin_head' ] );
				add_action( 'admin_footer', [ $this, 'admin_footer' ] );
			}
		}

		add_filter( 'brizy_wl_value', array( $this, 'filterKeys' ), 11 );
		add_filter( 'brizy_editor_config_texts', array( $this, 'editorConfigTexts' ) );

		if ( isset( $_REQUEST['brz-action'] ) && $_REQUEST['brz-action'] == 'save-values' ) {
			add_action( 'admin_init', array( $this, 'handleSubmit' ), 10 );
		}

		if ( isset( $_REQUEST['brz-action'] ) && $_REQUEST['brz-action'] == 'reset-values' ) {
			add_action( 'admin_init', array( $this, 'handleResetValues' ), 10 );
		}

		$this->values = $this->getValues();
	}

	public function _action_enqueue_editor_assets() {

		if ( isset($_REQUEST['page']) &&  $_REQUEST['page']===self::KEY  ) {
			// jQuery
			wp_enqueue_script('jquery');
			// This will enqueue the Media Uploader script
			wp_enqueue_media();
		}

	}

	public function enableWhiteLabelInterface() {
		if ( isset( $_GET['brizy_enable_wl'] ) ) {

			if ( is_network_admin() ) {
				$url = network_admin_url( 'admin.php?page=' . self::network_menu_slug(), false );
			} else {
				$url = menu_page_url( Brizy_Admin_Settings::menu_slug(), false );
			}

			set_transient( self::WL_SESSION_KEY, 1, 3 * HOUR_IN_SECONDS );

			header( "location: " . $url );
			exit;
		}
	}

	public function disableWhiteLabelInterface() {
		if ( get_transient( self::WL_SESSION_KEY ) == 1 ) {
			delete_transient( self::WL_SESSION_KEY );
		}
	}

	public function getDefaultValues() {
		return array(
			'brizy'         => new BrizyPro_Whitelabel_Value( 'brizy', 'text', 'Brizy', 'Company Name' ),
			'description'   => new BrizyPro_Whitelabel_Value( 'description', 'textarea', 'A drag & drop front-end page builder to help you create WordPress pages lightning fast.', __( 'Description', 'brizy-pro' ) ),
			'brizy-prefix'  => new BrizyPro_Whitelabel_Value( 'brizy-refix', 'text', 'brizy', 'Prefix' ),
			'brizy-logo'    => new BrizyPro_Whitelabel_Value( 'logo-brizy-text', 'file', BRIZY_PLUGIN_URL . '/admin/static/img/brizy-logo.svg', 'Logo (20px x 20px)' ),
			'brizy-logo-2x' => new BrizyPro_Whitelabel_Value( 'logo-brizy-text-2x', 'file', BRIZY_PLUGIN_URL . '/admin/static/img/brizy-logo.svg', 'Logo Retina (40px x 40px)' ),
			'support-url'   => new BrizyPro_Whitelabel_Value( 'support-url', 'text', Brizy_Config::SUPPORT_URL, 'Support URL' ),
			'about-url'     => new BrizyPro_Whitelabel_Value( 'about-url', 'text', Brizy_Config::ABOUT_URL, 'About URL' )
		);
	}

	/**
	 * @return BrizyPro_Whitelabel_Value[]
	 */
	private function getValues() {

		if ( $this->values ) {
			return $this->values;
		}

		$defaults = $this->getDefaultValues();
		$data     = is_multisite() ? get_network_option( null, self::KEY, $defaults ) : get_option( self::KEY, $defaults );

		return wp_parse_args( $data, $defaults );
	}

	/**
	 * @param $data
	 *
	 * @return $this
	 */
	private function saveValues( $data ) {

		$this->values = $data;

		if ( is_multisite() ) {
			update_network_option( null, self::KEY, $data );
		} else {
			update_option( self::KEY, $data, true );
		}

		Brizy_Editor_Post::mark_all_for_compilation();

		return $this;
	}

	public function getEnabled() {

		$values  = $this->getValues();
		$enabled = false;

		if ( isset( $values['brizy'] ) && $values['brizy'] instanceof BrizyPro_Whitelabel_Value ) {
			$enabled = $values['brizy']->getValue() != 'Brizy';
		}

		return $enabled;
	}

	public function getPrefix() {

		$values = $this->getValues();
		$prefix = false;

		if ( isset( $values['brizy-prefix'] ) && $values['brizy-prefix'] instanceof BrizyPro_Whitelabel_Value ) {
			$prefix = $values['brizy-prefix']->getValue();
		}

		return $prefix ? $prefix : 'brizy';
	}

	public function handleSubmit() {
		$data = array();

		foreach ( $this->getDefaultValues() as $key => $defaultValue ) {
			$data[ $key ] = new BrizyPro_Whitelabel_Value( $key, $_POST['values'][ $key ]['type'], wp_unslash( $_POST['values'][ $key ]['value'] ) );
		}

		$this->saveValues( $data );

		Brizy_Admin_Flash::instance()->add_success( __( 'Settings saved.', 'brizy-pro' ) );

		if ( is_multisite() ) {
			wp_redirect( network_admin_url( 'admin.php?page=' . self::network_menu_slug(), false ) );
		} else {
			wp_redirect( menu_page_url( self::menu_slug(), false ) );
		}

		exit;
	}

	public function handleResetValues() {
		$this->saveValues( $this->getDefaultValues() );

		Brizy_Admin_Flash::instance()->add_success( __( 'Settings saved.', 'brizy-pro' ) );

		if ( is_multisite() ) {
			wp_redirect( network_admin_url( 'admin.php?page=' . self::network_menu_slug(), false ) );
		} else {
			wp_redirect( menu_page_url( self::menu_slug(), false ) );
		}

		exit;
	}

	public function filterKeys( $data ) {

		if ( isset( $this->values[ $data['key'] ] ) && $this->values[ $data['key'] ] instanceof BrizyPro_Whitelabel_Value ) {
			return $this->values[ $data['key'] ]->getValue();
		}

		return $data;
	}

	public function actionRegisterPage() {

		add_submenu_page( is_multisite() ? Brizy_Admin_NetworkSettings::menu_slug() : Brizy_Admin_Settings::menu_slug(),
			__( 'White Label', 'brizy-pro' ),
			__( 'White Label', 'brizy-pro' ),
			is_multisite() ? 'manage_network' : 'manage_options',
			is_multisite() ? self::network_menu_slug() : self::menu_slug(),
			array( $this, 'render' )
		);
	}

	/**
	 * @throws Twig_Error_Loader
	 * @throws Twig_Error_Runtime
	 * @throws Twig_Error_Syntax
	 */
	public function render() {

		$context = array(
			'action'       => add_query_arg( 'brz-action', 'save-values', menu_page_url( self::menu_slug(), false ) ),
			'resetAction'  => add_query_arg( 'brz-action', 'reset-values', menu_page_url( self::menu_slug(), false ) ),
			'nonce'        => wp_nonce_field( 'validate-wl', '_wpnonce', true, false ),
			'defaultData'  => $this->getDefaultValues(),
			'data'         => $this->getValues(),
			'submit_label' => 'Save Changes',
			'message'      => isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : null,
		);

		echo Brizy_TwigEngine::instance( BRIZY_PRO_PLUGIN_PATH . "/admin/views/" )->render( 'white-label.html.twig', $context );
	}

	public static function menu_slug() {
		return self::KEY;
	}

	public static function network_menu_slug() {
		return 'network-' . self::KEY;
	}

	public function editorConfigTexts( $texts ) {

		if ( ! $this->getEnabled() ) {
			return $texts;
		}

		$brizy = __bt( 'brizy', 'Brizy' );

		foreach ( $texts as $key => $text ) {
			if ( strpos( $text, 'Brizy' ) !== false ) {
				$texts[ $key ] = str_replace( 'Brizy', $brizy, $text );
			}
		}

		return $texts;
	}

	public function all_plugins( $plugins ) {

		$values = $this->getValues();

		$data = [
			'Name'        => $values['brizy']->getValue(),
			'PluginURI'   => $values['about-url']->getValue(),
			'Description' => $values['description']->getValue(),
			'Author'      => $values['brizy']->getValue(),
			'AuthorURI'   => $values['about-url']->getValue(),
			'Title'       => $values['brizy']->getValue(),
		];

		if ( array_key_exists( 'brizy/brizy.php', $plugins ) ) {
			$plugins['brizy/brizy.php'] = wp_parse_args( $data, $plugins['brizy/brizy.php'] );
		}

		if ( array_key_exists( 'brizy-pro/brizy-pro.php', $plugins ) ) {
			$data['Name'] = $data['Name'] . ' Pro';
			$plugins['brizy-pro/brizy-pro.php'] = wp_parse_args( $data, $plugins['brizy-pro/brizy-pro.php'] );
		}

		return $plugins;
	}

	public function plugin_row_meta( $plugin_meta, $plugin_file ) {

		if ( ! in_array( $plugin_file, [ 'brizy/brizy.php', 'brizy-pro/brizy-pro.php' ] ) ) {
			return $plugin_meta;
		}

		$plugin_meta = array_filter( $plugin_meta, function( $value ) {
			return ( strpos( $value, 'plugin-information' ) === false );
		} );

		return $plugin_meta;
	}

	/**
	 * Rewrite the function(wp_plugin_update_row), hook added by function wp_plugin_update_rows on each plugin row in the admin plugins list.
	 *
	 * @param $file
	 * @param $plugin_data
	 *
	 * @return void|false
	 */
	public function after_plugin_row( $file, $plugin_data ) {

		remove_filter( 'after_plugin_row_brizy/brizy.php', 'wp_plugin_update_row' );
		remove_filter( 'after_plugin_row_brizy-pro/brizy-pro.php', 'wp_plugin_update_row' );

		$current = get_site_transient( 'update_plugins' );
		if ( ! isset( $current->response[ $file ] ) ) {
			return false;
		}

		$response = $current->response[ $file ];

		$plugins_allowedtags = array(
			'a'       => array(
				'href'  => array(),
				'title' => array(),
			),
			'abbr'    => array( 'title' => array() ),
			'acronym' => array( 'title' => array() ),
			'code'    => array(),
			'em'      => array(),
			'strong'  => array(),
		);

		$plugin_name = wp_kses( $plugin_data['Name'], $plugins_allowedtags );
		$details_url = self_admin_url( 'plugin-install.php?tab=plugin-information&plugin=' . $response->slug . '&section=changelog&TB_iframe=true&width=600&height=800' );

		/** @var WP_Plugins_List_Table $wp_list_table */
		$wp_list_table = _get_list_table(
			'WP_Plugins_List_Table',
			array(
				'screen' => get_current_screen(),
			)
		);

		if ( is_network_admin() || ! is_multisite() ) {
			if ( is_network_admin() ) {
				$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
			} else {
				$active_class = is_plugin_active( $file ) ? ' active' : '';
			}

			$requires_php   = isset( $response->requires_php ) ? $response->requires_php : null;
			$compatible_php = is_php_version_compatible( $requires_php );
			$notice_type    = $compatible_php ? 'notice-warning' : 'notice-error';

			printf(
				'<tr class="plugin-update-tr%s" id="%s" data-slug="%s" data-plugin="%s">' .
				'<td colspan="%s" class="plugin-update colspanchange">' .
				'<div class="update-message notice inline %s notice-alt"><p>',
				$active_class,
				esc_attr( $response->slug . '-update' ),
				esc_attr( $response->slug ),
				esc_attr( $file ),
				esc_attr( $wp_list_table->get_column_count() ),
				$notice_type
			);

			if ( ! current_user_can( 'update_plugins' ) ) {
				printf(
				/* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number. */
					__( 'There is a new version(%1$s) of %2$s available.', 'brizy-pro' ),
					$response->new_version,
					$plugin_name
				);
			} elseif ( empty( $response->package ) ) {
				printf(
				/* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number. */
					__( 'There is a new version(%1$s) of %2$s available. <em>Automatic update is unavailable for this plugin.</em>', 'brizy-pro' ),
					$response->new_version,
					$plugin_name
				);
			} else {
				if ( $compatible_php ) {
					printf(
					/* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number, 5: Update URL, 6: Additional link attributes. */
						__( 'There is a new version(%1$s) of %2$s available. Please <a href="%3$s" %4$s>update now</a>.', 'brizy-pro' ),
						$response->new_version,
						$plugin_name,
						wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ),
						sprintf(
							'class="update-link" aria-label="%s"',
							/* translators: %s: Plugin name. */
							esc_attr( sprintf( _x( 'Update %s now', 'plugin' ), $plugin_name ) )
						)
					);
				} else {
					printf(
					/* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number 5: URL to Update PHP page. */
						__( 'There is a new version(%1$s) of %2$s available, but it doesn&#8217;t work with your version of PHP. <a href="%3$s">Learn more about updating PHP</a>.', 'brizy-pro' ),
						$response->new_version,
						$plugin_name,
						esc_url( wp_get_update_php_url() )
					);
					wp_update_php_annotation( '<br><em>', '</em>' );
				}
			}

			/**
			 * Fires at the end of the update message container in each
			 * row of the plugins list table.
			 *
			 * The dynamic portion of the hook name, `$file`, refers to the path
			 * of the plugin's primary file relative to the plugins directory.
			 *
			 * @since 2.8.0
			 *
			 * @param array $plugin_data {
			 *     An array of plugin metadata.
			 *
			 *     @type string $name        The human-readable name of the plugin.
			 *     @type string $plugin_uri  Plugin URI.
			 *     @type string $version     Plugin version.
			 *     @type string $description Plugin description.
			 *     @type string $author      Plugin author.
			 *     @type string $author_uri  Plugin author URI.
			 *     @type string $text_domain Plugin text domain.
			 *     @type string $domain_path Relative path to the plugin's .mo file(s).
			 *     @type bool   $network     Whether the plugin can only be activated network wide.
			 *     @type string $title       The human-readable title of the plugin.
			 *     @type string $author_name Plugin author's name.
			 *     @type bool   $update      Whether there's an available update. Default null.
			 * }
			 * @param array $response {
			 *     An array of metadata about the available plugin update.
			 *
			 *     @type int    $id          Plugin ID.
			 *     @type string $slug        Plugin slug.
			 *     @type string $new_version New plugin version.
			 *     @type string $url         Plugin URL.
			 *     @type string $package     Plugin update package URL.
			 * }
			 */
			do_action( "in_plugin_update_message-{$file}", $plugin_data, $response ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			echo '</p></div></td></tr>';
		}
	}

	public function core_upgrade_preamble() {

		echo
			"<script>
				( function( $ ) {
	                $( 'input[value=\"brizy/brizy.php\"]' ).closest( 'tr' ).find( '.open-plugin-details-modal' ).remove();
	                $( 'input[value=\"brizy-pro/brizy-pro.php\"]' ).closest( 'tr' ).find( '.open-plugin-details-modal' ).remove();
				} )( jQuery );
			</script>";
	}

	public function admin_head() {

		$current_screen = get_current_screen();

		add_action( 'gettext_brizy', [ $this, 'gettext_domain' ], 10, 2 );
		add_action( 'gettext_brizy-pro', [ $this, 'gettext_domain' ], 10, 2 );

		if ( $current_screen->id == 'update-core' ) {
			ob_start( [ $this, 'replaceWlUpdateCore' ] );
		}
	}

	public function replaceWlUpdateCore( $buffer ) {

		$values = $this->getValues();

		$html = preg_replace( '/https:\/\/ps.w.org\/brizy\/assets\/[^"]*/', $values['brizy-logo-2x']->getValue(), $buffer );

		return $html ? $html : $buffer;
	}

	public function admin_footer() {

		$current_screen = get_current_screen();

		if ( $current_screen->id == 'update-core' ) {
			ob_end_flush();
		}
	}

	public function gettext_domain( $translation, $text ) {

		$values = $this->getValues();

		if ( $text == 'Brizy' ) {
			$translation = $values['brizy']->getValue();
		}

		if ( $text == 'Brizy Pro' ) {
			$translation = $values['brizy']->getValue() . ' Pro';
		}

		if ( $text == 'Brizy.io' ) {
			$translation = $values['brizy']->getValue();
		}

		if ( $text == 'https://brizy.io/' ) {
			$translation = $values['about-url']->getValue();
		}

		if ( $text == 'Extended functionality for the Brizy WordPress builder plugin.' ) {
			$translation = $values['description']->getValue();
		}

		if ( $text == "A free drag & drop front-end page builder to help you create WordPress pages lightning fast. It's easy with Brizy." ) {
			$translation = $values['description']->getValue();
		}

		return $translation;
	}
}
