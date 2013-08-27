<?php 
/*
Plugin Name: gSearch Plus
Version: 1.1.6
Plugin URI: http://www.gomo.pt/plugins/gsearch-plus/
Description: Improves the WordPress search engine without messing with the database, sorts results by relevance, and more. Simple and clean!
Author: Luis Godinho
Author URI: http://twitter.com/luistinygod
License: GPL2

gSearch Plus, by GOMO (GOMO SP)
Copyright (C) 2013, GOMO - Luis Godinho (email : luis@gomo.pt )

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, see <http://www.gnu.org/licenses/>, or 
write to the Free Software Foundation, Inc., 51 Franklin St, Fifth Floor, 
Boston, MA  02110-1301  USA.

*/

/**
 * @package Main
 */

if ( !defined('DB_NAME') ) {
	header('HTTP/1.0 403 Forbidden');
	die;
}

define( 'GOMO_SP_VERSION', '1.1.6' );


if ( !defined('GOMO_SP_URL') )
	define( 'GOMO_SP_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GOMO_SP_PATH') )
	define( 'GOMO_SP_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Include classes
 */
 
require( GOMO_SP_PATH .'inc/class-search-plus.php' );



/**
 * Run action and filters according to scenario
 */
 
if ( is_admin() ) {
	add_action( 'plugins_loaded', 'gomo_sp_admin', 0 );
	add_filter( 'plugin_action_links_'. plugin_basename( __FILE__) , 'gomo_sp_plugin_action_links' );
	
	register_activation_hook(__FILE__, 'gomo_sp_activation');
	register_deactivation_hook(__FILE__, 'gomo_sp_deactivation');
} else {
	add_action( 'plugins_loaded', 'gomo_sp_frontend', 0 );
}


/**
 * Activation / deactivation hooks
 */
function gomo_sp_activation() {
	//set default options if not created already
	$options = get_option( 'gomo_searchplus_options' );
	if( !is_array( $options ) ) {
		$options = array();
		$options['version'] = GOMO_SP_VERSION;
		$options['enable'] = 1;
		$options['stopwords'] = 0; // do not use stopwords
		$options['custom_fields'] = 0; // do not search on custom fields
		$options['highlight'] = 0; // do not highlight searched terms
		$options['highlight_color'] = '4AFF92'; // highlight color
//		$options['exclude_tax-post_tags'] = 0;
		$options['specific_stops'] = 'word1,word2';
		$options['enable_tax'] = 1; // Enable search on taxonomies by default
		update_option( 'gomo_searchplus_options', $options );
	}
}

function gomo_sp_deactivation() {
	//not used
}

// Register 'settings' link in the plugin admin
function gomo_sp_plugin_action_links( $actions ) {
	$actions[] = '<a href="' . menu_page_url( 'gomo-search-plus', false ) . '">Settings</a>';
	return $actions;
}


/**
 * Load frontend specific functions
 */
function gomo_sp_frontend() {
	$gomo_sp_search = new GOMO_Search_Plus();
}


/**
 * Load admin specific functions
 */
function gomo_sp_admin() {
	require( GOMO_SP_PATH .'inc/class-sp-admin.php' );
}

?>