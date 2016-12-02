<?php
/**
 * @package uninstall geeSearch Plus (by GOMO)
 *
 * Code used when the plugin is removed (not just deactivated but actively deleted through the WordPress Admin).
 */

if( !defined( 'ABSPATH') && !defined('WP_UNINSTALL_PLUGIN') ) {
    exit();
}

delete_option('gee_searchplus_options');


?>