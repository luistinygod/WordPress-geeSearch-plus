<?php
/**
 * @package  geeSearch_Plus Admin
 */

if ( !defined( 'GEE_SP_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

/**
 *  Class to generate the admin page for the geeSearch Plus Plugin
 */
class Gee_Search_Plus_admin {
	
	private $options_page_hook;
	
	/**
	 * Class constructor
	 */
	function __construct() {
		add_action( 'admin_init', array( $this, 'register_settings') );
		add_action( 'admin_menu', array( $this, 'add_admin_menu') );
	}

	/** Add admin menu, context help and scripts */
	function add_admin_menu() {
		$this->options_page_hook = add_options_page( 'geeSearch Plus', 'geeSearch Plus', 'manage_options', 'gee-search-plus', array( $this, 'render_settings_page') );
		
		// Add contextual help tab
		add_action( 'load-'. $this->options_page_hook, array( $this, 'add_contextual_help_tab'), 10 );
		
		add_action( 'admin_print_scripts-' . $this->options_page_hook, array( $this, 'admin_print_scripts' ) );
		add_action( 'admin_print_styles-' . $this->options_page_hook, array( $this, 'admin_print_styles' ) );
	}
	
	function admin_print_scripts() {
		wp_enqueue_script( 'gsp-admin' );
	}
	
	function admin_print_styles() {
		wp_enqueue_style( 'wp-color-picker' );
	}
	
	/** Add contextual help tab */
	function add_contextual_help_tab() {
	
		$screen = get_current_screen();
		if( $screen->id != $this->options_page_hook )
			return;
				
		$screen->add_help_tab( array(
			'id' => 'highlight',
			'title' => __('Highlight searched terms', 'gsearch-plus'),
			'content' => $this->contextual_help_content( 'highlight' )
		));
	
	}
	
	/** Add contextual help content */
	function contextual_help_content( $context = '' ) {
		$html = '';
		
		switch( $context ) {
			case 'highlight':
				$html = '<h3>' . __('Highlight searched terms', 'gsearch-plus') .'</h3>';
				$html .= '<p>The field <strong>Highlight allowed areas</strong> enables the possibility to define the website areas where the plugin can highlight terms. It is defined to be a jQuery selector so it accepts any valid selector. </p>';
				$html .= '<p><strong>Examples:</strong></p>';
				$html .= '<ul>';
				$html .= '<li>Use <em>article.type-post</em> to highlight only searched terms found on the articles titles and contents with the <em>type-post</em> html class</li>';
				$html .= '<li>Use <em>#content</em> to highlight searched terms found on the <em>content</em> html div id.</li>';
				$html .= '</ul>';
				$html .= '<p>Both examples work perfectly on WordPress Twenty Thirteen default theme.</p>';
				break;
				
			case 'xpto':
				// add $html
				break;

		}
		return $html;
	}
	
	function render_settings_page() {
		?>
		<div class="wrap">
			<?php screen_icon('options-general'); ?><h2>geeSearch Plus, improved WordPress search</h2>
			<div class="postbox-container" style="width:70%;margin-right: 5%;min-width:400px;max-width:700px;">
				<form action="options.php" method="POST">
					<?php settings_fields( 'gee-search-settings-group' ); ?>
					<?php do_settings_sections( 'gee-search-plus' ); ?>
					<?php submit_button(); ?>
				</form>
			</div>
			
			<div class="postbox-container" style="width:20%; padding-left: 2%;min-width:210px;max-width:210px;border-left: 1px solid #ddd;">
				<a target="_blank" href="http://www.geethemes.com/"><img src="<?php echo GEE_SP_URL . '/img/geethemes-logo.png'; ?>" alt="geeThemes Premium WordPress Themes & Plugins" /></a>
				<br>
				<h3>Like it?</h3>
				<p>Want to help make this plugin even better? Donate now!</p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="USZXRKWMBPAML">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>

				<p>Rate this plugin at <a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/gsearch-plus">wordpress.org</a></p>
			</div>
		</div>
		<?php
	}
	
	
	// Register settings, sections and fields
	
	function register_settings() {
	
		//register jQuery scripts
		wp_register_script( 'gsp-admin', GEE_SP_URL . 'js/gsp-admin.js', array( 'jquery', 'wp-color-picker') );
		
		//register geeSearch+ settings - gee_searchplus_options
		register_setting( 'gee-search-settings-group', 'gee_searchplus_options', array( $this, 'settings_sanitize' ) );
		
		//get options
		$options = get_option( 'gee_searchplus_options' );
		
		//register settings sections
		add_settings_section( 'gee-settings-section-general', 'General Settings', array( $this,'settings_section_general'), 'gee-search-plus' );
		add_settings_section( 'gee-settings-section-exclude', 'Exclude from search', array( $this,'settings_section_exclude'), 'gee-search-plus' );
		add_settings_section( 'gee-settings-section-highlight', 'Highlight searched terms', array( $this,'settings_section_highlight'), 'gee-search-plus' );
				
		//SECTION: General Settings
		// Enable
		add_settings_field( 'gee-settings-enable', 'Enable geeSearch Plus engine', array( $this,'settings_checkbox_enable'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[enable]', 'value' => $options ) );
		
		// Enable search on taxonomies
		add_settings_field( 'gee-settings-enable-tax', 'Enable search on taxonomies', array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[enable_tax]', 'key' => 'enable_tax', 'value' => $options ) );
		
		// Enable search on custom fields
		add_settings_field( 'gee-settings-customfields', 'Enable search on custom fields', array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[custom_fields]', 'key' => 'custom_fields', 'value' => $options ) );
		
		// SECTION: Exclude Search
		// Use stopwords
		add_settings_field( 'gee-settings-stopwords', 'Remove Stopwords by language', array( $this,'settings_stopwords'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options ) );
		
		// Exclude stopwords from search
		add_settings_field( 'gee-settings-specific-stop', 'Exclude specific stopwords', array( $this,'settings_specific_stopwords'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options['specific_stops'] ) );
		
		// Exclude taxonomies from search
		add_settings_field( 'gee-settings-taxonomies', 'Exclude Taxonomies', array( $this,'settings_exclude_taxonomies'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options ) );
		
		// SECTION:  Highlight search terms terms
		// enable highlight
		add_settings_field( 'gee-settings-highlight', 'Highlight searched terms', array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight]', 'key' => 'highlight', 'value' => $options ) );
		// color picker
		add_settings_field( 'gee-settings-colorpicker', 'Highlight color', array( $this,'settings_color_picker'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight_color]', 'value' => $options ) );
		// highlight allowed area
		add_settings_field( 'gee-settings-highlight-area', 'Highlight allowed areas', array( $this,'render_input_text'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight_area]', 'key' =>'highlight_area' , 'value' => $options ) );
		
	}
	
	// Sanitize input
	function settings_sanitize( $input ) {
		if( isset( $input['specific_stops'] ) && !empty( $input['specific_stops'] ) ) {
			$input['specific_stops'] = str_replace( array(',,',',,,',',,,,'), ',' , preg_replace('/\s+/', ',', trim( $input['specific_stops'] )) );
		}
		$input['version'] = GEE_SP_VERSION;
		return $input;
	}
	
	// Sections
	function settings_section_general() {
	   // void
	}
	function settings_section_exclude() {
		echo "Exclude specific stopwords and/or taxonomies from search (enable search on taxonomies).";
	}
	
	function settings_section_highlight() {
		 // void
	}
	
	// Fields
	function settings_checkbox_enable( $args ) {
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
			echo '<p style="color: brown;"><em>Turn on the geeSearch Plus engine!</em></p>';
		}
	}
	
	function settings_checkbox( $args ) {
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
	
	
	function render_input_text( $args ) {
		$name = esc_attr( $args['name'] );
		$key = $args['key'];
		$value = ( isset( $args['value'][ $key ] ) ) ? $args['value'][ $key ] : '';
		
		echo '<input type="text" name="'. $name.'" value="'. $value .'" class="regular-text ltr">';
	}
	
	function settings_color_picker( $args ) {
		$name = esc_attr( $args['name'] );
		$options = $args['value'];
		if( !isset( $options['highlight_color'] ) ) {
			$options['highlight_color'] = '#ffffff';
		}
		echo '<input type="text" name="'. $name .'" value="'. $options['highlight_color'] .'" class="wp-color-picker-field" data-default-color="#ffffff">';
	}
	
	
	function settings_stopwords( $args ) {
		$options = $args['value'];
		
		if( isset( $options['stopwords'] ) ) { 
			$value = $options['stopwords']; 
		} else {
			$value = 0;
		}
		
		$stop_files = glob( GEE_SP_PATH ."stop/stopwords-*.php" );
		
		echo '<select name="gee_searchplus_options[stopwords]" style="width: 350px;">';
		echo '<option value="0" '. selected( $value, 0 ) .'>Disable stopwords</option>';
		echo '<option value="1" '. selected( $value, 1 ) .'>Enable specific stopwords only</option>';
		if( is_array( $stop_files ) ) {
			foreach ($stop_files as $stop_file) {
				$lang = str_replace(".php", '', str_replace(GEE_SP_PATH ."stop/stopwords-", '', $stop_file) );
				echo '<option value="'.$lang.'" '. selected( $value, $lang ) .'>Use stopwords-'.$lang .'.php file</option>';
			}
		}
		echo '<option value="stella" '. selected( $value, 'stella' ) .'>Use stopwords files according to Stella languages</option>';
		echo '</select>';
	}
	
	
	function settings_specific_stopwords( $args ) {
		$value = $args['value'];
		echo '<textarea name="gee_searchplus_options[specific_stops]" style="width: 350px; height: 100px;" >'. $value .'</textarea>';
	}
	
	
	function settings_exclude_taxonomies($args) {
		$options = $args['value'];
		foreach ( get_taxonomies( array( 'public' => true ), 'objects' ) as $taxonomy ) {
			if ( in_array( $taxonomy->name, array( 'link_category', 'nav_menu', 'post_format' ) ) )
				continue; 
			if( isset( $options['exclude_tax-'.$taxonomy->name] ) && $options['exclude_tax-'.$taxonomy->name] == 1 ) { 
				$checked = 'checked';
			} else { 
				$checked = ''; 
			}
			echo '<label><input type="checkbox" name="gee_searchplus_options[exclude_tax-'. $taxonomy->name.']" value="1" '. $checked.' />    '. $taxonomy->labels->name .' ('. $taxonomy->name .')</label><br>';
		}
	}
	
}

?>