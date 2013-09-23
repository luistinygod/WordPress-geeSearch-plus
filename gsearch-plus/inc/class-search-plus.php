<?php
/**
 * @package geeSearch_Plus Engine
 */

if ( !defined( 'GEE_SP_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

class Gee_Search_Plus_Engine {
	// keeps the plugin options
	private $options;
	
	// keeps the search terms
	private $search_terms = '';
	
	// keeps the search results ( key = post_id , value = relevance )
	private $search_results = array();
	
	function __construct() {
		//load options
		$this->options = get_option( 'gee_searchplus_options' );
		
		if( isset( $this->options['enable'] ) &&  $this->options['enable'] == 1) {
			//capture search query
			add_action( 'pre_get_posts', array($this, 'capture_and_extend_search') );
			
			//Make WP object correct after queries are made
			add_action( 'wp', array( $this, 'add_search_wp_object') ); 
			
			//hook the function get_search_query
			add_filter( 'get_search_query', array( $this, 'return_search_query'), 1);
			
			// highlight filters
			if( isset( $this->options['highlight'] ) &&  $this->options['highlight'] == 1) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts') );
			}
		}
	}
	
	
	/**
	 * Extends search according to options
	 */
	function capture_and_extend_search( $query ) {
		
		if( $query->is_admin == 1 || !$query->is_search() || !$query->is_main_query() ) {
			return;
		}
		
		if( empty( $query->query_vars['s'] ) ) {
			return;
		} else {
			$this->search_terms = $query->query_vars['s'];
		}
		
		//reset search results array
		$this->search_results = array();
		
		//1. Remove stopwords
		$this->remove_stopwords();
		$this->search_terms = preg_replace('/\s+/', ' ', trim( $this->search_terms ));
		
		if( empty( $this->search_terms ) || $this->search_terms == ' ' ) {
			$query->set( 's', '' );
			$query->set( 'post__in', array(0) );
			return;
		}
		
		//2. Run the original search query and sort
		$this->process_original_query( $query->query_vars );
		
		//3. Query taxonomies
		$this->process_query_taxonomies();
		
		//3.1 Query custom fields
		$this->process_query_custom_fields();
		
		//3.2 Hook for external filters to change the search results so far.
		$this->search_results = apply_filters( 'gee_search_original_results', $this->search_results, $this->search_terms, $query->query_vars );
		
		//4. Deliver query $query
		if( !empty( $this->search_results ) ) {
			
			//sort by relevance
			arsort( $this->search_results );
			error_log(' SEARCH RESULTS: '. print_r( $this->search_results, true) );
			$result_ids = array_keys( $this->search_results );
			
			$query->set( 's', '' );
			$query->set( 'post__in', $result_ids );
			$query->set( 'orderby', 'post__in');
		}

	}
	
	/** Deliver the 's' query var to its original content to avoid issues on page loading */
	function add_search_wp_object( $wp ) {
		global $wp_query;
		set_query_var('s', $this->search_terms );
	}
	
	
	/**
	 * Removes all the stopwords from the search terms
	 */
	function remove_stopwords() {
		
		// check if stopwords mechanism is enabled 
		if( !isset( $this->options['stopwords'] ) || ( isset( $this->options['stopwords'] ) && $this->options['stopwords'] === '0' ) ) {
			return;
		}
		
		// calculate specific stopwords - plugin settings
		if( isset( $this->options['specific_stops'] ) && !empty( $this->options['specific_stops'] ) ) {
			$specific = explode(',', trim( $this->options['specific_stops'] ) );
		}
		if( !isset( $specific ) || !is_array( $specific ) ) {
			$specific = array();
		}
		
		if( $this->options['stopwords'] == 'stella') {
			if( defined( 'STELLA_CURRENT_LANG' ) ) {
				if( file_exists( GEE_SP_PATH .'stop/stopwords-'. STELLA_CURRENT_LANG  .'.php' ) ) {
					include( GEE_SP_PATH .'stop/stopwords-'. STELLA_CURRENT_LANG  .'.php' );
				}
			}
		} elseif( $this->options['stopwords'] != '1' ) {
			if( file_exists( GEE_SP_PATH .'stop/stopwords-'. $this->options['stopwords']  .'.php' ) ) {
				include( GEE_SP_PATH .'stop/stopwords-'. $this->options['stopwords']  .'.php' );
			}
		}
		
		if( isset($stopwords) && is_array($stopwords) ) {
			$stopwords = array_merge( $stopwords, $specific );
		} else {
			$stopwords = $specific;
		}
		
		// apply filters if hooked
		$stopwords = apply_filters( 'gomo_sp_stopwords', $stopwords ); //deprecated
		$stopwords = apply_filters( 'gee_search_stopwords', $stopwords ); // since 1.2.0
		
		//Remove stopwords from search terms
		$words_filter = explode(' ', trim( $this->search_terms ) );
		if( is_array($words_filter) && is_array($stopwords) ) {
			$words_filter = array_diff( $words_filter, $stopwords);
			$this->search_terms = implode(' ', $words_filter );
		}
		
		unset($stopwords);
		unset($specific);
	
	}
	
	
	/**
	 * Runs the original wordpress search query (title and contents) and sorts by relevance
	 */
	function process_original_query( $qvars ) {
		
		//runs the original query without paging
		$args = array_merge( $qvars, array( 'post_type' => 'any', 'nopaging' => true, 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) );
		$initial_query = new WP_Query( $args );
		
		$cool_posts = array();
		
		
		if( $initial_query->have_posts() ) {
		
			// prepare weights
			$title_weight = (int)apply_filters( 'gee_search_title_weight', 5 );
			$content_weight = (int)apply_filters( 'gee_search_content_weight', 1 );
			
			$words = explode(' ', strtolower( trim( $this->search_terms ) ) );
			
			//setup
			$title = $content = '';
		
			while( $initial_query->have_posts() ) : $initial_query->the_post();
			
				$count = 0;
				$title = strtolower( get_the_title() );
				$content = strtolower( get_the_content() );
				foreach( $words as $word ) {
					$count += $title_weight * substr_count( $title, $word );
					$count += $content_weight * substr_count( $content, $word );
				}
				
				$this->search_results[ get_the_ID() ] = $count;
	
			endwhile;
			
		}
		
	}
	
	/**
	 * Runs the taxonomy query, excluding all the IDs from the original query
	 */
	function process_query_taxonomies() {
		
		//check if taxonomies search is enabled
		if( empty( $this->options['enable_tax'] ) ) {
			return;
		}
		
		// prepare tax query
		$my_tax_query = array();
		foreach( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
		
			if( in_array( $taxonomy, array( 'link_category', 'nav_menu', 'post_format' ) ) ) {
				continue;
			}
			
			if ( isset( $this->options[ 'exclude_tax-'. $taxonomy ] ) && $this->options[ 'exclude_tax-'. $taxonomy ] )
				continue;
		
			$list_taxonomy_terms = get_terms( $taxonomy, array('hide_empty' => true, 'fields' => 'all') );
			$hit_slugs = array();
			if( !empty($list_taxonomy_terms) ) {
				foreach( $list_taxonomy_terms as $term ) {
					if( stripos( $term->name,  $this->search_terms ) !== false ) {
						$hit_slugs[] = $term->slug;
					}
				}
				if( !empty($hit_slugs) ) {
					$my_tax_query[] = array(
						'taxonomy' => $taxonomy,
						'field' => 'slug',
						'terms' => $hit_slugs,
					);
				}
			}
		}

		//run the search by taxonomies query
		if( !empty( $my_tax_query ) ) {
			
			$my_tax_query['relation'] = 'OR';
			
			$args = array(
				'post_type' => 'any',
				'nopaging' => true,
				'tax_query' => $my_tax_query,
				'no_found_rows' => true, 
				'update_post_meta_cache' => false, 
				'update_post_term_cache' => false
			);
			
			$the_tax_query = new WP_Query( $args );
			
			if( $the_tax_query->have_posts() ) {
				
				// prepare weights
				$tax_weight = (int)apply_filters( 'gee_search_taxonomy_weight', 2 );
				
				while( $the_tax_query->have_posts() ) : $the_tax_query->the_post();
					
					if( array_key_exists( get_the_ID() , $this->search_results ) ) {
						$this->search_results[ get_the_ID() ] += $tax_weight;
					} else {
						$this->search_results[ get_the_ID() ] = $tax_weight;
					}
					
				endwhile;
			}
		}
	}
	
	/**
	 * Runs the custom fields query, excluding all the IDs from the original query
	 */
	function process_query_custom_fields() {
		//check if custom fields search is enabled
		if( empty( $this->options['custom_fields'] ) ) {
			return;
		}
	
		$args = array(
			'post_type' => 'any',
			'nopaging' => true,
			'meta_value' => $this->search_terms,
			'meta_compare' => 'LIKE',
			'no_found_rows' => true, 
			'update_post_meta_cache' => false, 
			'update_post_term_cache' => false
		);
		
		$the_tax_query = new WP_Query( $args );
		
		if( $the_tax_query->have_posts() ) {
		
			// prepare weights
			$custom_fields_weight = (int)apply_filters( 'gee_search_customfields_weight', 1 );
		
			while( $the_tax_query->have_posts() ) : $the_tax_query->the_post();
			
				if( array_key_exists( get_the_ID() , $this->search_results ) ) {
					$this->search_results[ get_the_ID() ] += $custom_fields_weight;
				} else {
					$this->search_results[ get_the_ID() ] = $custom_fields_weight;
				}
			
			endwhile;
		}

	}
	
	
	/**
	 * If theme uses get_search_query or the_search_query calls, then gSP returns the (filtered) search terms
	 */
	function return_search_query() {
		return $this->search_terms;	
	}
	
	/**
	 * register js to highlight the searched terms
	 */
	function enqueue_styles_scripts() {
		if( !empty( $this->search_terms ) && $this->options['highlight_color'] != '' ) {
			wp_register_script( 'gsp-highlight', GEE_SP_URL . 'js/gsp-highlight.js', array('jquery'), '1.1.7', true );
			wp_enqueue_script( 'gsp-highlight' );
			
			$terms = explode(' ', trim( $this->search_terms ) );
			
			if( empty( $this->options['highlight_area'] ) ) { $this->options['highlight_area'] = '#content'; }
			
			wp_localize_script( 'gsp-highlight', 'highlight_args', array( 'area' => apply_filters( 'gsp_highlight_area', $this->options['highlight_area'] ) , 'color' => $this->options['highlight_color'], 'search_terms' => $terms ) );
		}
	}
	
} //end class

?>