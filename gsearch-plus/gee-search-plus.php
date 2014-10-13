<?php
/*
Plugin Name: geeSearch Plus
Version: 1.4.2
Plugin URI: http://www.geethemes.com
Description: Improves the WordPress search engine without messing with the database, sorts results by relevance, and more. Simple and clean!
Author: geeThemes
Author URI: http://twitter.com/geethemeswp
License: GPL2

geeSearch Plus, by geeThemes
Copyright (C) 2013, geeThemes (hello@geethemes.com)

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

define( 'GEE_SP_VERSION', '1.4.2' );


if ( !defined('GEE_SP_URL') )
	define( 'GEE_SP_URL', plugin_dir_url( __FILE__ ) );
if ( !defined('GEE_SP_PATH') )
	define( 'GEE_SP_PATH', plugin_dir_path( __FILE__ ) );


/** Register hooks that are fired when the plugin is activated and deactivated. */
if( is_admin() ) {
	register_activation_hook( __FILE__, array( 'Gee_Search_Plus_Plugin', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Gee_Search_Plus_Plugin', 'deactivate' ) );
}

/** Launch plugin */
$gee_search_plus = new Gee_Search_Plus_Plugin();

/** Main plugin class */
class Gee_Search_Plus_Plugin {


	function __construct() {

		if( is_admin() ) {

			// Load plugin text domain
			add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

			// actions
			add_action( 'plugins_loaded', array( $this, 'backend_actions' ), 0 );

			// filters
			add_filter( 'plugin_action_links_'. plugin_basename( __FILE__) , array( $this, 'plugin_action_links' ) );

		} else {

			add_action( 'plugins_loaded', array( $this, 'frontend_actions' ), 0 );

		}


	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Activate" action, false if WPMU is disabled or plugin is activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		//set default options if not created already
		$options = get_option( 'gomo_searchplus_options' );

		//migrate from previous options name (since 1.2.0)
		if( ! empty( $options ) ) {
			delete_option( 'gomo_searchplus_options' );
			update_option( 'gee_searchplus_options', $options );
		} else {
			$options = get_option( 'gee_searchplus_options' );
		}

		//migrate highlight color from previous version of color picker (since v1.1.7)
		if( isset( $options['highlight_color'] ) && false === strpos( $options['highlight_color'], '#' ) ) {
			$options['highlight_color'] = '#'. $options['highlight_color'];
			update_option( 'gee_searchplus_options', $options );
		}

		if( !is_array( $options ) ) {
			$options = array();
			$options['version'] = GEE_SP_VERSION;
			$options['enable'] = 1;
			$options['query_type'] = 'and'; // since 1.3.0
			$options['order_type'] = 'relevance'; // since 1.3.0
			$options['stopwords'] = 0; // do not use stopwords
			$options['custom_fields'] = 0; // do not search on custom fields
			$options['highlight'] = 0; // do not highlight searched terms
			$options['highlight_color'] = '#ffffff'; // highlight color
			$options['highlight_area'] = '#content'; // highlight area
	//		$options['exclude_tax-post_tags'] = 0;
			$options['specific_stops'] = 'word1,word2';
			$options['enable_tax'] = 1; // Enable search on taxonomies by default
			update_option( 'gee_searchplus_options', $options );
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses "Network Deactivate" action, false if WPMU is disabled or plugin is deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {
		//nothing to declare
	}

	/**
	 * Adds direct link to plugin settings page when on plugins screen
	 */
	public static function plugin_action_links( $links ) {
		$action = array(
			'<a href="' . menu_page_url( 'gee-search-plus', false ) . '">Settings</a>',
			'<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=paypal%40geethemes%2ecom&item_name=geeSearch%20Plus%20plugin&no_shipping=1&cn=Donation%20Notes&tax=0&currency_code=EUR&bn=PP%2dDonationsBF&charset=UTF%2d8" target="_blank"><span class="dashicons dashicons-heart"></span> '.esc_html__( 'Donate', 'gee-search-plus' ).'</a>'
			);
		return array_merge( $action, $links );
	}

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {

		$domain = 'gee-search-plus';
		//$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
		// load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
	}



	/** Run on backend only - admin */
	function backend_actions() {
		require_once( GEE_SP_PATH .'inc/class-gsp-admin.php' );
		$gee_sp_backend = new Gee_Search_Plus_admin();

		include_once( GEE_SP_PATH .'inc/class-gsp-admin-notice.php' );
	}

	/** Run on frontend only  */
	function frontend_actions() {

		include_once( GEE_SP_PATH .'inc/class-search-plus.php' );
		$gee_sp_frontend = new Gee_Search_Plus_Engine();

		include_once( GEE_SP_PATH .'inc/class-gsp-media-search.php' );
		$gee_sp_media = new Gee_Media_Search();

	}


} // end class

?>