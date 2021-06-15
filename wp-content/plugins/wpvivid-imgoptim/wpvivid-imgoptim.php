<?php

/**
 * @link              https://wpvivid.com
 * @since             0.9.1
 * @package           WPvivid Imgoptim
 *
 * @wordpress-plugin
 * Plugin Name:       WPvivid Imgoptim Free
 * Description:       Optimize, compress and resize images in WordPress in bulk. Automatic image optimization, auto resize images upon upload.
 * Version:           0.9.9
 * Author:            WPvivid Team
 * Author URI:        https://wpvivid.com
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/copyleft/gpl.html
 * Text Domain:       wpvivid-imgoptim
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WPVIVID_IMGOPTIM_VERSION', '0.9.9' );

define('WPVIVID_IMGOPTIM_SLUG','WPvivid_ImgOptim');
define('WPVIVID_IMGOPTIM_NAME',plugin_basename(__FILE__));
define('WPVIVID_IMGOPTIM_URL',plugins_url('',__FILE__));
define('WPVIVID_IMGOPTIM_DIR',dirname(__FILE__));
define('WPVIVID_IMGOPTIM_DEFAULT_SAVE_DIR','wpvivid_image_optimization');
//
if(isset($wpvivid_imgoptim)&&is_a($wpvivid_imgoptim,'WPvivid_ImgOptim'))
{
    return ;
}

require plugin_dir_path( __FILE__ ) . 'includes/class-wpvivid-imgoptim.php';

function run_wpvivid_imgoptim()
{
    $wpvivid_imgoptim = new WPvivid_ImgOptim();
    $GLOBALS['wpvivid_imgoptim'] = $wpvivid_imgoptim;
}
run_wpvivid_imgoptim();