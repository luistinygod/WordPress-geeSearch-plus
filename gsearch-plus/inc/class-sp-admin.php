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
			'title' => __('Highlight searched terms', 'gee-search-plus'),
			'content' => $this->contextual_help_content( 'highlight' )
		));
	
	}
	
	/** Add contextual help content */
	function contextual_help_content( $context = '' ) {
		$html = '';
		
		switch( $context ) {
			case 'highlight':
				$html = '<h3>' . esc_html__('Highlight searched terms', 'gee-search-plus') .'</h3>';
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
				<h3><?php esc_html_e( 'Like it?', 'gee-search-plus'); ?></h3>
				<p><?php esc_html_e( 'Want to help make this plugin even better? Donate now!', 'gee-search-plus'); ?></p>
				<form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_top">
					<input type="hidden" name="cmd" value="_s-xclick">
					<input type="hidden" name="hosted_button_id" value="USZXRKWMBPAML">
					<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
					<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
				</form>

				<p><?php esc_html_e( 'Rate this plugin at ', 'gee-search-plus' ); ?><a target="_blank" href="http://wordpress.org/support/view/plugin-reviews/gsearch-plus">wordpress.org</a></p>
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
		add_settings_section( 'gee-settings-section-general', __( 'General Settings', 'gee-search-plus' ), array( $this,'settings_section_general'), 'gee-search-plus' );
		add_settings_section( 'gee-settings-section-exclude', __( 'Exclude from search', 'gee-search-plus' ) , array( $this,'settings_section_exclude'), 'gee-search-plus' );
		add_settings_section( 'gee-settings-section-highlight', __( 'Highlight searched terms', 'gee-search-plus' ), array( $this,'settings_section_highlight'), 'gee-search-plus' );
				
		//SECTION: General Settings
		// Enable
		add_settings_field( 'gee-settings-enable', __( 'Enable geeSearch Plus engine', 'gee-search-plus' ), array( $this,'settings_checkbox_enable'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[enable]', 'value' => $options ) );
		
		// Enable search on taxonomies
		add_settings_field( 'gee-settings-enable-tax', __( 'Enable search on taxonomies', 'gee-search-plus' ), array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[enable_tax]', 'key' => 'enable_tax', 'value' => $options ) );
		
		// Enable search on custom fields
		add_settings_field( 'gee-settings-customfields', __( 'Enable search on custom fields', 'gee-search-plus' ), array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[custom_fields]', 'key' => 'custom_fields', 'value' => $options ) );
		
		// Query type AND or OR
		$types_query = array( 'and' => __( 'Match all terms (AND)' , 'gee-search-plus' ), 'or' => __( 'Match at least one term (OR)' , 'gee-search-plus' ) );
		add_settings_field( 'gee-settings-querytype', __( 'Search type', 'gee-search-plus'), array( $this,'settings_selectbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[query_type]', 'key' => 'query_type', 'value' => $options, 'choices' => $types_query ) );
		
		// Order type - Relevance / date
		$types_order = array( 'relevance' => __( 'Relevance' , 'gee-search-plus' ), 'date' => __( 'Date' , 'gee-search-plus' ) );
		add_settings_field( 'gee-settings-ordertype', __( 'Order results by', 'gee-search-plus' ), array( $this,'settings_selectbox'), 'gee-search-plus', 'gee-settings-section-general', array( 'name' => 'gee_searchplus_options[order_type]', 'key' => 'order_type', 'value' => $options, 'choices' => $types_order ) );
		
		
		// SECTION: Exclude Search
		// Use stopwords
		add_settings_field( 'gee-settings-stopwords', __( 'Remove Stopwords by language', 'gee-search-plus'), array( $this,'settings_stopwords'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options ) );
		
		// Exclude stopwords from search
		add_settings_field( 'gee-settings-specific-stop', __( 'Exclude specific stopwords', 'gee-search-plus' ), array( $this,'settings_specific_stopwords'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options['specific_stops'] ) );
		
		// Exclude taxonomies from search
		add_settings_field( 'gee-settings-taxonomies', __( 'Exclude Taxonomies', 'gee-search-plus' ), array( $this,'settings_exclude_taxonomies'), 'gee-search-plus', 'gee-settings-section-exclude', array( 'value' => $options ) );
		
		// SECTION:  Highlight search terms terms
		// enable highlight
		add_settings_field( 'gee-settings-highlight', __( 'Highlight searched terms', 'gee-search-plus'), array( $this,'settings_checkbox'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight]', 'key' => 'highlight', 'value' => $options ) );
		// color picker
		add_settings_field( 'gee-settings-colorpicker', __( 'Highlight color', 'gee-search-plus'), array( $this,'settings_color_picker'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight_color]', 'value' => $options ) );
		// highlight allowed area
		add_settings_field( 'gee-settings-highlight-area', __( 'Highlight allowed areas', 'gee-search-plus'), array( $this,'render_input_text'), 'gee-search-plus', 'gee-settings-section-highlight', array( 'name' => 'gee_searchplus_options[highlight_area]', 'key' =>'highlight_area' , 'value' => $options ) );
		
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
		esc_html_e( 'Exclude specific stopwords and/or taxonomies from search (enable search on taxonomies).', 'gee-search-plus');
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
			echo '<div id="gsp_notice" class="updated settings-error">';
			echo '<p><strong>' . esc_html__( 'Please note: geeSearch Plus engine is disabled. Enable it now to improve WordPress search!' , 'gee-search-plus' ) .'</strong></p>';
			echo '</div>';
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
	
	function settings_selectbox( $args ) {
		$name = esc_attr( $args['name'] );
		$key = $args['key'];
		$value = ( isset( $args['value'][ $key ] ) ) ? $args['value'][ $key ] : '';
		$options = $args['choices'];
		
		echo '<select name="'. $name .'" >';
		foreach ( $options as $id => $title ) {
			echo '<option value="'. esc_attr( $id ) .'" '. selected( $id, $value ) .'>'. esc_html( $title ) .'</option>';
		}
		echo '</select>';
		
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
		echo '<option value="0" '. selected( $value, 0 ) .'>' . esc_html__( 'Disable stopwords' , 'gee-search-plus' ) .'</option>';
		echo '<option value="1" '. selected( $value, 1 ) .'>' . esc_html__( 'Enable specific stopwords only' , 'gee-search-plus' ) .'</option>';
		if( is_array( $stop_files ) ) {
			foreach ($stop_files as $stop_file) {
				$lang = str_replace(".php", '', str_replace( GEE_SP_PATH ."stop/stopwords-", '', $stop_file) );
				/* translators: Use file-xpto.php */
				echo '<option value="'.$lang.'" '. selected( $value, $lang ) .'>'.esc_html__( 'Use' , 'gee-search-plus' ).' stopwords-'. $lang .'.php</option>';
			}
		}
		echo '<option value="stella" '. selected( $value, 'stella' ) .'>' . esc_html__( 'Use stopwords files according to Stella languages' , 'gee-search-plus' ) .'</option>';
		echo '</select>';
	}
	
	
	function settings_specific_stopwords( $args ) {
		$value = $args['value'];
		echo '<textarea name="gee_searchplus_options[specific_stops]" style="width: 350px; height: 100px;" >'. esc_textarea( $value ).'</textarea>';
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