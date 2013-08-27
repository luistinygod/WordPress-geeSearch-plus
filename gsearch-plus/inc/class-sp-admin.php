<?php
/**
 * @package Gomo Search_Plus Admin
 */

if ( !defined( 'GOMO_SP_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

/**
 *  Class to generate the admin page for the Search Plus Plugin
 */
class GOMO_Search_Plus_admin {
	
	/**
	 * Class constructor
	 */
	function __construct() {
		add_action('admin_init', array( $this, 'gomo_search_register_settings') );
		add_action('admin_menu', array( $this, 'gomo_search_admin_menu') );
	}	

	function gomo_search_admin_menu() {
		$page_hook_suffix = add_options_page( 'gSearch Plus','gSearch Plus','manage_options','gomo-search-plus', array($this, 'gomo_search_settings_page') );
		add_action('admin_print_scripts-' . $page_hook_suffix, array($this,'gomo_sp_admin_print_scripts'));
	}
	
	function gomo_sp_admin_print_scripts() {
		wp_enqueue_script( 'gomo-jscolor' );
	}
	
	function gomo_search_settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon('options-general'); ?><h2>gSearch Plus, improved WordPress search</h2>
			<div class="postbox-container" style="width:70%;margin-right: 5%;min-width:400px;max-width:700px;">
				<form action="options.php" method="POST">
					<?php settings_fields( 'gomo-search-settings-group' ); ?>
					<?php do_settings_sections( 'gomo-search-plus' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
			
			<div class="postbox-container" style="width:20%; padding-left: 2%;min-width:210px;max-width:210px;border-left: 1px solid #ddd;">
				<a target="_blank" href="http://plugins.gomo.pt/"><img src="<?php echo GOMO_SP_URL . '/img/logo_gomo.png'; ?>" alt="GOMO agency logo" /></a>
				<h3>Need help with your website?</h3>
				<p><a href="mailto:gomo@gomo.pt">GOMO</a> is a design and web development studio specialized in WordPress!</p>
				<?php /* <br><hr>
				<h3>Resources          <a target="_blank" class="button-secondary" href="http://www.gomo.pt/plugins/gsearch-plus/">Visit us ›</a></h3>
				<p>Read documentation, learn more about this plugin and find some tips for your web project. </p> */ ?>
				<br><hr>
				<h3>Like it?</h3>
				<p>Want to help make this plugin even better? Donate now!</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
				<input type="hidden" name="cmd" value="_s-xclick">
				<input type="hidden" name="hosted_button_id" value="PM5X7Z8JVF62W">
				<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
				<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>
				<p>Rate this plugin at <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/gsearch-plus">wordpress.org</a></p>		
			</div>
		</div>
		<?php
	}
	
	
	// Register settings, sections and fields
	
	function gomo_search_register_settings() {
	
		//register jQuery scripts
		wp_register_script( 'gomo-jscolor', GOMO_SP_URL . 'lib/jscolor/jscolor.js' );
		
		//register Search+ settings - gomo_searchplus_options
		register_setting( 'gomo-search-settings-group', 'gomo_searchplus_options', array($this, 'gomo_settings_sanitize'));
		
		//get options
		$options = get_option( 'gomo_searchplus_options' );
		
		//register settings sections
		add_settings_section( 'gomo-settings-section-general', 'General Settings', array($this,'gomo_settings_section_general'), 'gomo-search-plus' );
		add_settings_section( 'gomo-settings-section-exclude', 'Exclude from search', array($this,'gomo_settings_section_exclude'), 'gomo-search-plus' );
		add_settings_section( 'gomo-settings-section-highlight', 'Highlight searched terms', array($this,'gomo_settings_section_highlight'), 'gomo-search-plus' );
				
		//SECTION: General Settings
		// Enable
		add_settings_field( 'gomo-settings-enable', 'Enable gSearch Plus engine', array($this,'gomo_settings_checkbox_enable'), 'gomo-search-plus', 'gomo-settings-section-general', array( 'name' => 'gomo_searchplus_options[enable]', 'value' => $options ) );
		
		// Enable search on taxonomies
		add_settings_field( 'gomo-settings-enable-tax', 'Enable search on taxonomies', array($this,'gomo_settings_checkbox'), 'gomo-search-plus', 'gomo-settings-section-general', array( 'name' => 'gomo_searchplus_options[enable_tax]', 'key' => 'enable_tax', 'value' => $options ) );
		
		// Enable search on custom fields
		add_settings_field( 'gomo-settings-customfields', 'Enable search on custom fields', array($this,'gomo_settings_checkbox'), 'gomo-search-plus', 'gomo-settings-section-general', array( 'name' => 'gomo_searchplus_options[custom_fields]', 'key' => 'custom_fields', 'value' => $options ) );
		/* Add-ons: Media Search */
		if( is_plugin_active('gsp-media-search/gsp-media-search.php') && get_option('gomo_spms_addon') ) {
			add_settings_field( 'gomo-settings-mediasearch', 'Enable search on media <em>(add-on)</em>', array($this,'gomo_settings_checkbox'), 'gomo-search-plus', 'gomo-settings-section-general', array( 'name' => 'gomo_searchplus_options[enable_media]', 'key' => 'enable_media', 'value' => $options ) );
		} /*else {
			add_settings_field( 'gomo-settings-mediasearch', 'Enable search on media <em>(add-on)</em>', array($this,'gomo_buy_add_on'), 'gomo-search-plus', 'gomo-settings-section-general', array( 'name' => 'Media Search' ) );
		} */
		
		// SECTION: Exclude Search
		// Use stopwords
		add_settings_field( 'gomo-settings-stopwords', 'Remove Stopwords by language', array($this,'gomo_settings_stopwords'), 'gomo-search-plus', 'gomo-settings-section-exclude', array( 'value' => $options ) );
		
		// Exclude stopwords from search
		add_settings_field( 'gomo-settings-specific-stop', 'Exclude specific stopwords', array($this,'gomo_settings_specific_stopwords'), 'gomo-search-plus', 'gomo-settings-section-exclude', array( 'value' => $options['specific_stops'] ) );
		
		// Exclude taxonomies from search
		add_settings_field( 'gomo-settings-taxonomies', 'Exclude Taxonomies', array($this,'gomo_settings_exclude_taxonomies'), 'gomo-search-plus', 'gomo-settings-section-exclude', array( 'value' => $options ) );
		
		// SECTION:  Highlight search terms terms 
		// enable highlight
		add_settings_field( 'gomo-settings-highlight', 'Highlight searched terms', array($this,'gomo_settings_checkbox'), 'gomo-search-plus', 'gomo-settings-section-highlight', array( 'name' => 'gomo_searchplus_options[highlight]', 'key' => 'highlight', 'value' => $options ) );
		// color picker
		add_settings_field( 'gomo-settings-colorpicker', 'Highlight color', array($this,'gomo_settings_color_picker'), 'gomo-search-plus', 'gomo-settings-section-highlight', array( 'name' => 'gomo_searchplus_options[highlight_color]', 'value' => $options ) );
		
	}
	
	// Sanitize input
	function gomo_settings_sanitize( $input ) {
		if( isset( $input['specific_stops'] ) && !empty( $input['specific_stops'] ) ) {
			$input['specific_stops'] = str_replace( array(',,',',,,',',,,,'), ',' , preg_replace('/\s+/', ',', trim( $input['specific_stops'] )) );
		}
		return $input;
	}
	
	// Sections
	function gomo_settings_section_general() {
	   // void
	}
	function gomo_settings_section_exclude() {
		echo "Exclude specific stopwords and/or taxonomies from search (enable search on taxonomies).";
	}
	
	function gomo_settings_section_highlight() {
		 // void
	}
	
	// Fields
	function gomo_settings_checkbox_enable( $args ) {
		$name = esc_attr( $args['name'] );
		$options = $args['value'];
		if( isset( $options['enable'] ) && $options['enable'] == 1 ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		echo '<input type="checkbox" name="'. $name.'" value="1" '. $checked.'/>';
		if( $checked == 'checked' ) {
			echo '';
		} else {
			echo '<p style="color: brown;"><em>Turn on the gSearch Plus engine!</em></p>';
		}
	}
	
	function gomo_settings_checkbox( $args ) {
		$name = esc_attr( $args['name'] );
		$key = $args['key'];
		$options = $args['value'];
		if( isset( $options[$key] ) && $options[$key] == 1 ) {
			$checked = 'checked';
		} else {
			$checked = '';
		}
		echo '<input type="checkbox" name="'. $name.'" value="1" '. $checked.'/>';
	}
	
	
	function gomo_settings_text_input( $args ) {
		$name = esc_attr( $args['name'] );
		$value = esc_attr( $args['value'] );
		echo '<input type="text" name="'. $name.'" value="'. $value.'" style="width: 140px;"/>';
	}
	
	function gomo_settings_color_picker( $args ) {
		$name = esc_attr( $args['name'] );
		$options = $args['value'];
		if( !isset( $options['highlight_color'] ) ) {
			$options['highlight_color'] = '4AFF92';
		}
		echo '<input class="color" name="'. $name.'" value="'. $options['highlight_color'] .'" style="width: 140px;"/>';
	}
	
	
	function gomo_settings_stopwords( $args ) {
		$options = $args['value'];
		
		if( isset( $options['stopwords'] ) ) { 
			$value = $options['stopwords']; 
		} else {
			$value = 0;
		}
		
		$stop_files = glob(GOMO_SP_PATH ."stop/stopwords-*.php" );
		
		echo '<select name="gomo_searchplus_options[stopwords]" style="width: 350px;">';
		echo '<option value="0" '. selected( $value, 0 ) .'>Disable stopwords</option>';
		echo '<option value="1" '. selected( $value, 1 ) .'>Enable specific stopwords only</option>';
		if( is_array( $stop_files ) ) {
			foreach ($stop_files as $stop_file) {
				$lang = str_replace(".php", '', str_replace(GOMO_SP_PATH ."stop/stopwords-", '', $stop_file) );
				echo '<option value="'.$lang.'" '. selected( $value, $lang ) .'>Use stopwords-'.$lang .'.php file</option>';
			}
		}
		echo '<option value="stella" '. selected( $value, 'stella' ) .'>Use stopwords files according to Stella languages</option>';
		echo '</select>';
	}
	
	
	function gomo_settings_specific_stopwords( $args ) {
		$value = $args['value'];
		echo '<textarea name="gomo_searchplus_options[specific_stops]" style="width: 350px; height: 100px;" >'. $value .'</textarea>';
	}
	
	
	function gomo_settings_exclude_taxonomies($args) {
		$options = $args['value'];
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			if ( in_array( $taxonomy->name, array( 'link_category', 'nav_menu', 'post_format' ) ) )
				continue; 
			if( isset( $options['exclude_tax-'.$taxonomy->name] ) && $options['exclude_tax-'.$taxonomy->name] == 1 ) { 
				$checked = 'checked';
			} else { 
				$checked = ''; 
			}
			echo '<label><input type="checkbox" name="gomo_searchplus_options[exclude_tax-'. $taxonomy->name.']" value="1" '. $checked.' />    '. $taxonomy->labels->name .' ('. $taxonomy->name .')</label><br>';
		}
	}
	
	// Display Buy Add On field
	function gomo_buy_add_on( $args ) {
		$plugins = get_plugins();
		if( is_array( $plugins ) && array_key_exists( 'gsp-media-search/gsp-media-search.php', $plugins ) ){
			echo '<p><a class="button-secondary" href="plugins.php">Activate Add-On</a></p>';
			
		} else {
			echo '<p style="color: #fd7800;"><a target="_blank" class="button-secondary" href="http://www.gomo.pt/plugins/gsearch-plus/">Buy Now›</a></p>';
		}
		
	}
	
	
}
global $gomo_sp_admin;
$gomo_sp_admin = new GOMO_Search_Plus_admin;

?>