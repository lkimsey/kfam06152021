<?php
/**
 * Plugin Name: Brizy Pro
 * Description: Extended functionality for the Brizy WordPress builder plugin.
 * Plugin URI: https://brizy.io/
 * Author: Brizy.io
 * Author URI: https://brizy.io/
 * Version: 2.2.11
 * Text Domain: brizy-pro
 * License: To be announced
 * Domain Path: /languages
 */

define( 'BRIZY_PRO_DEVELOPMENT', false );
define( 'BRIZY_PRO_VERSION', '2.2.11' );
define( 'BRIZY_PRO_EDITOR_VERSION', BRIZY_PRO_DEVELOPMENT ? 'dev' : '67-wp' );
define( 'BRIZY_PRO_FILE', __FILE__ );
define( 'BRIZY_REQUIRED_VERSION', '2.2.16' );
define( 'BRIZY_PRO_PLUGIN_BASE', plugin_basename( BRIZY_PRO_FILE ) );
define( 'BRIZY_PRO_PLUGIN_PATH', dirname( BRIZY_PRO_FILE ) );
define( 'BRIZY_PRO_PLUGIN_URL', rtrim( plugin_dir_url( BRIZY_PRO_FILE ), "/" ) );

include_once rtrim( BRIZY_PRO_PLUGIN_PATH, "/" ) . '/autoload.php';
include_once rtrim( BRIZY_PRO_PLUGIN_PATH, "/" ) . '/whitelabel/main.php';

if ( BRIZY_PRO_DEVELOPMENT ) {
    $dotenv = new \Symfony\Component\Dotenv\Dotenv('APP_ENV');
    $dotenv->loadEnv( __DIR__ . '/.env' );
}

add_action( 'brizy_plugin_included', function () {

	if ( ! defined( 'BRIZY_VERSION' ) ) {
		add_action( 'admin_notices', 'brizy_pro_notices' );
		return;
	}

	if ( version_compare( BRIZY_VERSION, BRIZY_REQUIRED_VERSION ) < 0 ) {
		// show a notice if the free version of the plugin is not installed
		add_action( 'admin_notices', 'brizy_pro_notices' );

		return;
	}

	load_plugin_textdomain( 'brizy-pro', false, plugin_basename( dirname( BRIZY_PRO_FILE ) ) . '/languages' );

	$mainInstance = new BrizyPro_Main();
	$mainInstance->run();

	add_action( 'upgrader_process_complete', 'brizypro_upgrade_completed', 10, 2 );
	register_activation_hook( BRIZY_PRO_FILE, 'brizypro_install' );
} );


function brizy_pro_notices() {
	?>
    <div class="notice notice-error is-dismissible">
        <p>
			<?php echo __bt( 'brizy', 'Brizy' ) ?> PRO requires Brizy <?php echo BRIZY_REQUIRED_VERSION ?> or newer.
            <b><?php echo strtoupper( __bt( 'brizy', 'Brizy' ) ) ?> PRO IS NOT RUNNING. </b>
        </p>
    </div>
	<?php
}

/**
 * @param $upgrader_object
 * @param $options
 */
function brizypro_upgrade_completed( $upgrader_object, $options ) {
	if ( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
		foreach ( $options['plugins'] as $plugin ) {
			if ( $plugin == BRIZY_PRO_PLUGIN_BASE ) {
				add_option( 'brizypro-regenerate-permalinks', 1 );
			}
		}
	}
}


function brizypro_install() {

	if ( defined( 'BRIZY_VERSION' ) ) {
		Brizy_Editor::get()->registerCustomPostTemplates();
	}

	$mainInstance = new BrizyPro_Main();
	$mainInstance->registerCustomPosts();

	add_option( 'brizypro-regenerate-permalinks', 1 );
}
