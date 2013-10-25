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
	
	// keeps requested posts_per_page value
	private $posts_per_page = '';
	private $paged = '';
	
	
	
	function __construct() {
		//load options
		$this->options = get_option( 'gee_searchplus_options' );
		
		if( ! empty( $this->options['enable'] ) ) {
			//capture search query
			add_action( 'pre_get_posts', array( $this, 'capture_and_extend_search') );
			
			//Since wp 3.7 - combine WordPress and geeSearch to remove stopwords
			add_filter( 'wp_search_stopwords', array( $this, 'get_stopwords' ) );
			
			// since 1.3 to add the OR-query type
			add_filter( 'posts_search', array( $this,'manipulate_where_clause' ), 1, 1 );
			
			//Extend search results
			add_action( 'wp', array( $this, 'extend_search_at_wp') ); 
			
			//hook the function get_search_query
			//add_filter( 'get_search_query', array( $this, 'return_search_query'), 20, 1 );
			
			// highlight filters
			if( isset( $this->options['highlight'] ) &&  $this->options['highlight'] == 1 ) {
				add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts') );
			}
			
			//default order parameter
			if( empty( $this->options['order_type'] ) ) {
				$this->options['order_type'] = 'relevance';
			}
			if( empty( $this->options['query_type'] ) ) {
				$this->options['query_type'] = 'and';
			}
		}
	}
	
	
	/**
	 * Before WP core query manipulate Search and fetch values
	 */
	function capture_and_extend_search( $query ) {
		
		if( $query->is_admin == 1 || !$query->is_search() || !$query->is_main_query() ) {
			return;
		}
		
		if( empty( $this->options['enable'] ) ) {
			return;
		}
		
		if( empty( $query->query_vars['s'] ) ) {
			return;
		} else {
			$this->search_terms = $query->query_vars['s'];
		}
		
		if( get_bloginfo( 'version' ) >= 3.7 ) {
		
			
			// Since 3.7, search query is relevance default
			if( 'date' == $this->options['order_type'] ) {
				$query->set( 'orderby', 'date' );
			}
			

			
		} else {
			//Older WP need plugin stopwords feature
			
			$stopwords = $this->get_stopwords();

			$words_filter = explode(' ', trim( $this->search_terms ) );
			if( is_array($words_filter) && is_array( $stopwords ) ) {
				$words_filter = array_diff( $words_filter, $stopwords);
				$this->search_terms = implode(' ', $words_filter );
			}
		
			$this->search_terms = preg_replace('/\s+/', ' ', trim( $this->search_terms ) );
			
			// If stopwords mechanism removes all the search terms
			if( empty( $this->search_terms ) || $this->search_terms == ' ' ) {
				$query->set( 's', '' );
				return;
			}
			
			$query->set( 's', $this->search_terms );
			
		
		}
		
		$this->posts_per_page = isset( $query->query_vars['posts_per_page'] ) ? $query->query_vars['posts_per_page'] : 10;
		$this->paged = isset( $query->query_vars['paged'] ) ? $query->query_vars['paged'] : 0;
		
		$query->set( 'posts_per_page', '' );
		$query->set( 'paged', '' );
		$query->set( 'post_type', 'any' );
		$query->set( 'nopaging', true );
		$query->set( 'no_found_rows', true );
		$query->set( 'update_post_meta_cache', false );
		$query->set( 'update_post_term_cache', false );
		
	}
	
	
	/**
	 * Removes all the stopwords from the search terms
	 */
	function get_stopwords( $wp_stopwords = array() ) {
		
		// check if stopwords mechanism is enabled 
		if( empty( $this->options['stopwords'] ) ) {
			return $wp_stopwords;
		}
		
		// calculate specific stopwords - plugin settings
		if( !empty( $this->options['specific_stops'] ) ) {
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
		unset($specific);
		
		// since 1.3.0 (wp 3.7) - merge plugin and wordpress stopwords
		if( !empty( $wp_stopwords ) && is_array( $wp_stopwords ) ) {
			$stopwords = array_merge( $stopwords, $wp_stopwords );
			$stopwords = array_unique( $stopwords );
		}
		
		// apply filters if hooked
		$stopwords = apply_filters( 'gomo_sp_stopwords', $stopwords ); //deprecated
		$stopwords = apply_filters( 'gee_search_stopwords', $stopwords ); // since 1.2.0

		return $stopwords;
	
	}
	
	
	/** Manipulate WP core search query */
	function manipulate_where_clause( $search ) {
		
		if( 'or' == $this->options['query_type'] && is_search() && !empty( $search ) ) {

			$chunks = explode(' AND ', $search );
			$first = array_shift( $chunks );
			$new_search = ' AND ';
			
			foreach( $chunks as $key => $chunk ) {
				if( $key === 0 ) {
					$new_search .= $chunk;
					continue; 
				}
				if( false === strpos( $chunk, 'post_title' ) || false === strpos( $chunk, 'post_content' ) ){
					$new_search .= ' AND '. $chunk;
				} else {
					$new_search .= ' OR '. $chunk;
				}
			}
			
			$new_search = apply_filters( 'gee_search_modify_posts_search', $new_search , $search );
		}
		
		
		
		if( !empty( $new_search ) ) {
			return $new_search;
		}

		return $search;
	}
	
	
	/** Combine search results from WordPress core with the extended geeSearch results */
	function extend_search_at_wp( $wp ) {
		global $wp_query;

		if( ! is_search() ) {
			return;
		}

		//Fetch the same search terms used by core query
		$this->search_terms = $wp_query->query['s'];
		if( empty( $this->search_terms ) ) {
			return;
		}
		
		//reset search results array
		$this->search_results = array();

		//1. Process original query, fetch IDs, measure relevance weight (or not)
		$this->process_wpcore_query( $wp_query->posts );
		
		//2. Process taxonomies query
		$this->process_query_taxonomies();
		
		//3. Process custom fields query
		$this->process_query_custom_fields();
		
		
		//Before proceed allow to some filtering 
		$this->search_results = apply_filters( 'gee_search_original_results', $this->search_results, $this->search_terms, $wp_query->query_vars );
		
		//Final. merge results on wp_query
		if( empty( $this->search_results ) ) {
			return;
		}
		
		// Decide order by
		switch( $this->options['order_type'] ) {
			case 'date':
				$order = 'date';
				break;
			default:
			case 'relevance':
				$order = 'post__in';
				arsort( $this->search_results );
				break;
		}
		
		
		$result_ids = array_keys( $this->search_results );

		//Prepare to final query
		$new_args = array(
			'post_type' => 'any',
			'post__in' => $result_ids,
			'orderby' => $order,
			
		);
		
		$new_search = new WP_Query( $new_args );
		
		// merge results and prepare $wp_query for the real world
		$wp_query->query_vars['nopaging'] = false;
		$wp_query->query_vars['posts_per_page'] = $this->posts_per_page;
		$wp_query->query_vars['paged'] = $this->paged;
		$wp_query->posts = $new_search->posts;
		$wp_query->post_count = $new_search->post_count;
		$wp_query->post = $new_search->post;
		
		
		
	}
	
	
	/**
	 * Process the original wordpress search query (title and contents) and sorts by relevance
	 */
	 
	function process_wpcore_query( $posts ) {
		
		if( empty( $posts ) ) {
			return;
		}
		
		// prepare relevance weights
		if( 'relevance' == $this->options['order_type'] ) {
			$title_weight = (int)apply_filters( 'gee_search_title_weight', 5 );
			$content_weight = (int)apply_filters( 'gee_search_content_weight', 1 );

			$words = explode(' ', strtolower( trim( $this->search_terms ) ) );
		}

		$count = $title_weight; //WP 3.7 - first post deserves more weight
		
		foreach( $posts as $post ) {
			
			if( 'relevance' == $this->options['order_type'] ) {
				$title = strtolower( apply_filters( 'the_title', $post->post_title, $post->ID ) );
				$content = strtolower( apply_filters( 'the_content', $post->post_content ) );
				
				foreach( $words as $word ) {
					$count += $title_weight * substr_count( $title, $word );
					$count += $content_weight * substr_count( $content, $word );
				}
			}
			
			$this->search_results[ $post->ID ] = $count;
			
			$count = 0;
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
		
		if( 'or' == $this->options['query_type'] ) {
			$words = explode(' ', strtolower( trim( $this->search_terms ) ) );
		}
		
		foreach( get_taxonomies( array( 'public' => true ) ) as $taxonomy ) {
		
			if( in_array( $taxonomy, array( 'link_category', 'nav_menu', 'post_format' ) ) ) {
				continue;
			}
			
			if( isset( $this->options[ 'exclude_tax-'. $taxonomy ] ) && $this->options[ 'exclude_tax-'. $taxonomy ] )
				continue;
		
			$list_taxonomy_terms = get_terms( $taxonomy, array('hide_empty' => true, 'fields' => 'all') );
			$hit_slugs = array();
			if( !empty($list_taxonomy_terms) ) {
				foreach( $list_taxonomy_terms as $term ) {
					if( stripos( $term->name, $this->search_terms ) !== false || ( !empty( $words ) && in_array( strtolower( $term->name ), $words ) ) ) {
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
					
					if( 'relevance' == $this->options['order_type'] && array_key_exists( get_the_ID() , $this->search_results ) ) {
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
		
		// prepare weights
		$custom_fields_weight = (int)apply_filters( 'gee_search_customfields_weight', 1 );
		
		
		//default query $args
		$args = array(
			'post_type' => 'any',
			'nopaging' => true,
			'no_found_rows' => true, 
			'update_post_meta_cache' => false, 
			'update_post_term_cache' => false
		);
		
		if( 'or' == $this->options['query_type'] ) {
			$words = explode(' ', strtolower( trim( $this->search_terms ) ) );
		} else {
			$words = array( strtolower( trim( $this->search_terms ) ) );
		}

		foreach( $words as $word ) {
			$args['meta_value'] = $word;
			$args['meta_compare'] = 'LIKE';
			
			$the_custom_query = new WP_Query( $args );
			
			while( $the_custom_query->have_posts() ) : $the_custom_query->the_post();
			
				if( 'relevance' == $this->options['order_type'] && array_key_exists( get_the_ID() , $this->search_results ) ) {
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
	function return_search_query( $query ) {
		if( empty( $this->options['enable'] ) ) {
			return $query;
		}
		return $this->search_terms;
	}
	
	/**
	 * register js to highlight the searched terms
	 */
	function enqueue_styles_scripts() {
	
		if( empty( $this->options['enable'] ) ) {
			return;
		}
		
		if( !empty( $this->search_terms ) && $this->options['highlight_color'] != '' ) {
			wp_register_script( 'gsp-highlight', GEE_SP_URL . 'js/gsp-highlight.js', array('jquery'), '1.3.0', true );
			wp_enqueue_script( 'gsp-highlight' );
			
			$terms = explode(' ', trim( $this->search_terms ) );
			
			if( empty( $this->options['highlight_area'] ) ) { $this->options['highlight_area'] = '#content'; }
			
			wp_localize_script( 'gsp-highlight', 'highlight_args', array( 'area' => apply_filters( 'gsp_highlight_area', $this->options['highlight_area'] ) , 'search_terms' => $terms ) );
		
			// print inline style for the background
			if( apply_filters( 'gee_search_highlight_style', true ) ) {
				echo '<style type="text/css">';
				echo '.gee-search-highlight { ';
				echo 'background-color: '. $this->options['highlight_color'] . '; }';
				echo '</style>';
			}

		
		}
	}
	
} //end class

?>