<?php
/**
 * @package Search_Plus Engine
 */

if ( !defined( 'GOMO_SP_VERSION' ) ) {
	header( 'HTTP/1.0 403 Forbidden' );
	die;
}

class GOMO_Search_Plus {
	// keeps the plugin options
	private $options;
	
	// keeps the search terms
	private $search_terms = '';

	// keeps the post ids of original query
	private $original_results_ids = array();
	
	//keeps the post ids of taxonomies query & custom fields query
	private $extra_results_ids = array();
	
	function __construct() {
		//load options
		$this->options = get_option( 'gomo_searchplus_options' );
		if( isset( $this->options['enable'] ) &&  $this->options['enable'] == 1) {
			add_action( 'pre_get_posts', array($this, 'gomo_sp_extend_search') );
			add_filter('get_search_query', array($this, 'gomo_sp_return_search_query'), 1);
			// highlight filters
			if( isset( $this->options['highlight'] ) &&  $this->options['highlight'] == 1) {
				add_action( 'wp_enqueue_scripts', array( $this, 'gomo_sp_load_jshighlight') );
				add_action( 'wp_footer', array( $this, 'gomo_sp_highlight_terms') );
			}	
		}
	}
	
	
	/**
	 * Extends search according to options
	 */
	function gomo_sp_extend_search( $query ) {
		
		if( $query->is_admin == 1 || !$query->is_search() || !$query->is_main_query() ) {
			return;
		}
		
		if( empty( $query->query_vars['s'] ) ) {
			return;
		} else {
			$this->search_terms = $query->query_vars['s'];
		}
		
		// 
		$this->original_results_ids = array();
		$this->extra_results_ids = array();
		
		//1. Remove stopwords
		$this->gomo_sp_remove_stopwords();
		$this->search_terms = preg_replace('/\s+/', ' ', trim( $this->search_terms ));
		
		if( empty( $this->search_terms ) || $this->search_terms == ' ' ) {
			$query->set( 's', '' );
			$query->set( 'post__in', array(0) );
			return;
		}
		
		//2. Run the original search query and sort
		$this->gomo_sp_query_original( $query->query_vars );
		
		//3. Query taxonomies
		$this->gomo_sp_query_taxonomies();
		
		$this->original_results_ids = array_merge($this->original_results_ids, $this->extra_results_ids);
		
		//3.1 Query custom fields
		$this->gomo_sp_query_custom_fields();
		
		$this->original_results_ids = array_merge($this->original_results_ids, $this->extra_results_ids);
		
		//3.2 Hook for external filters to change the search results so far.
		$filter_result = apply_filters( 'geesp_original_results', $this->original_results_ids, $this->search_terms, $query->query_vars );
		
		if( isset( $filter_result ) && is_array( $filter_result ) && !empty( $filter_result ) ) {
			$this->original_results_ids = array_merge( $this->original_results_ids, $filter_result );
		}
		
		
		//4. Deliver query $query
		
		if( !empty($this->original_results_ids) ) {
			$query->set( 's', '' );
			$query->set( 'post__in', $this->original_results_ids );
			$query->set('orderby', 'post__in');
			//$query->set('no_found_rows', 1 );
			//$query->set('update_post_meta_cache', 0 );
			//$query->set('update_post_term_cache', 0 );
		}

	}
	
	
	
	/**
	 * Removes all the stopwords from the search terms
	 */
	function gomo_sp_remove_stopwords() {
		
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
				if( file_exists( GOMO_SP_PATH .'stop/stopwords-'. STELLA_CURRENT_LANG  .'.php' ) ) {
					include( GOMO_SP_PATH .'stop/stopwords-'. STELLA_CURRENT_LANG  .'.php' );
				}
			}
		} elseif( $this->options['stopwords'] != '1' ) {
			if( file_exists( GOMO_SP_PATH .'stop/stopwords-'. $this->options['stopwords']  .'.php' ) ) {
				include( GOMO_SP_PATH .'stop/stopwords-'. $this->options['stopwords']  .'.php' );
			}
		}
		
		if( isset($stopwords) && is_array($stopwords) ) {
			$stopwords = array_merge( $stopwords, $specific );
		} else {
			$stopwords = $specific;
		}
		
		// apply filters if hooked
		$stopwords = apply_filters( 'gomo_sp_stopwords', $stopwords );
		
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
	function gomo_sp_query_original( $qvars ) {
		
		//runs the original query without paging
		
		$args = array_merge( $qvars, array('post_type' => 'any', 'nopaging' => true, 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) );
		$initial_query = new WP_Query( $args );
		
		$cool_posts=array();
		$title = $content = '';
		
		if( $initial_query->have_posts() ) {
			
			$words = explode(' ', trim( $this->search_terms ) );
		
			while( $initial_query->have_posts() ) : $initial_query->the_post();
				$count= 0;
				$title = apply_filters('the_title', $title, get_the_ID() );
				$content = apply_filters('the_content', $content);
				
				$post_content = strtolower( $title . ' ' . $content );
				
				foreach( $words as $word ) {
					$count += substr_count($post_content, strtolower( $word ) );
				}
				$cool_posts[ get_the_ID() ] = $count;
	
			endwhile;
			
			
			arsort($cool_posts);
			$this->original_results_ids = array_keys($cool_posts);
		}
		
	}
	
	/**
	 * Runs the taxonomy query, excluding all the IDs from the original query
	 */
	function gomo_sp_query_taxonomies() {
		
		//check if taxonomies search is enabled
		if( !isset( $this->options['enable_tax'] ) || ( isset( $this->options['enable_tax'] ) && $this->options['enable_tax'] === '0' ) ) {
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
			if( !empty( $this->original_results_ids ) ) {
				$args = array(
					'post_type' => 'any',
					'nopaging' => true,
					'tax_query' => array_merge( array('relation' => 'OR'), $my_tax_query ),
					'post__not_in' => $this->original_results_ids
				);
			} else {
				$args = array(
					'post_type' => 'any',
					'nopaging' => true,
					'tax_query' => array_merge( array('relation' => 'OR'), $my_tax_query ),
				);
			}
			$the_tax_query = new WP_Query ( array_merge( $args, array( 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) ) );
			
			if( $the_tax_query->have_posts() ) {
				// calculate all the IDs from the first query
				while( $the_tax_query->have_posts() ) : $the_tax_query->the_post();
				    $this->extra_results_ids[] = get_the_ID();
				endwhile;
			}
		}
	}
	
	/**
	 * Runs the custom fields query, excluding all the IDs from the original query
	 */
	function gomo_sp_query_custom_fields() {
		//check if custom fields search is enabled
		if( !isset( $this->options['custom_fields'] ) || ( isset( $this->options['custom_fields'] ) && $this->options['custom_fields'] === '0' ) ) {
			return;
		}
	
		if( !empty( $this->original_results_ids ) ) {
			$args = array(
				'post_type' => 'any',
				'nopaging' => true,
				'meta_value' => $this->search_terms,
				'meta_compare' => 'LIKE',
				'post__not_in' => $this->original_results_ids
			);
		} else {
			$args = array(
				'post_type' => 'any',
				'nopaging' => true,
				'meta_value' => $this->search_terms,
				'meta_compare' => 'LIKE',
			);
		}
		$the_tax_query = new WP_Query( array_merge( $args, array( 'no_found_rows' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false ) ) );
		
		if( $the_tax_query->have_posts() ) {
			// calculate all the IDs from the first query
			while( $the_tax_query->have_posts() ) : $the_tax_query->the_post();
			    $this->extra_results_ids[] = get_the_ID();
			endwhile;
		}

	}
	
	
	/**
	 * If theme uses get_search_query or the_search_query calls, then gSP returns the (filtered) search terms
	 */
	function gomo_sp_return_search_query() {
		return $this->search_terms;	
	}
	
	/**
	 * register js to highlight the searched terms
	 */
	function gomo_sp_load_jshighlight() {
		if( !empty( $this->search_terms ) && $this->options['highlight_color'] != '' ) {
			wp_register_script( 'gomo_jshighlight', GOMO_SP_URL . 'lib/jshighlight/jshighlight.js', array('jquery')  ); 
			wp_enqueue_script( 'gomo_jshighlight' );
		}
	}
	
	function gomo_sp_highlight_terms() {
		if( !empty( $this->search_terms ) && $this->options['highlight_color'] != '' ) {
			$terms = explode(' ', trim( $this->search_terms ) );
			echo '<script type="text/javascript">';
			echo '	jQuery(document).ready(function() {';
			echo '		var searchTerms = [';
			foreach( $terms as $term ) {
				echo '"'. $term . '",';
			}
			echo '];';
			echo '		highlightTermsIn( jQuery(document.body) , searchTerms, "#'. $this->options['highlight_color'] .'");';
			echo '	});';
			echo '</script>';
		}
	}
	
} //end class

?>