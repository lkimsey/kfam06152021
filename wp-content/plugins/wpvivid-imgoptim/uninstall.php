<?php

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option('wpvivid_get_optimization_url');
delete_option('wpvivid_server_cache');
delete_option('wpvivid_imgoptim_overview');
delete_option('wpvivid_optimization_options');
delete_option('wpvivid_imgoptim_user');
delete_option('wpvivid_image_opt_task_cancel');
delete_option('wpvivid_image_opt_task');