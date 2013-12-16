<?php
/**
 * @package Search_Plus - Media Search engine
 */


class Gee_Media_Search {
	// keeps the plugin options
	private $options;
	// keeps the search terms
	private $search_terms = '';

	function __construct() {
		//load options
		$this->options = get_option( 'gee_searchplus_options' );
		
		if( !empty( $this->options['enable'] ) &&  !empty( $this->options['enable_media'] ) ) {
			add_filter( 'gee_search_original_results', array( $this, 'media_search_query'), 1, 3);
		}
	}
	
	
	/**
	 * Extends search to media and retrieves posts where that media is attached
	 */
	function media_search_query( $original_results , $search_words, $qvars ) {
		//check if media search is enabled
		if( empty( $this->options['enable_media'] ) ) {
			return;
		}
		
		//Media results weight on relevance
		$thumbnail_weight = (int)apply_filters( 'gee_search_thumbnail_weight', 2 );
		$media_weight = (int)apply_filters( 'gee_search_media_weight', 1 );
		
		$result_ids = array_keys( $original_results );
		
		
		
		// 1. Query media where title or description match the search terms
		$extra_args = array();
		
		// if ordering by date we can exclude other results
		if( !empty( $result_ids ) && 'date' == $this->options['order_type'] ) {
			$extra_args = array(
				'post_parent__not_in' => $result_ids
			);
		}
		
		
		$args = array_merge( array(
			'post_type' => 'attachment',
			'post_status'=>'inherit',
			's' => $search_words,
			'nopaging' => true,
			'no_found_rows' => true, 
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'fields' => 'ids'
			), $extra_args );
		
		add_filter( 'posts_search', array( $this, 'extend_posts_search' ), 10, 2 );
		$this->search_terms = $search_words;
		$media_query = new WP_Query( $args );
		
		remove_filter( 'posts_search', array( $this, 'extend_posts_search' ), 10, 2 );
		
		
		if( empty( $media_query->posts ) ) {
			return $original_results;
		}
		
		// 2. Query posts where featured image id belongs to the $media_query set
		if( !empty( $result_ids ) && 'date' == $this->options['order_type'] ) {
			$extra_args = array(
				'post__not_in' => $result_ids
			);
		}
		
		$args = array_merge( array(
			'post_type' => 'any',
			'post_status'=>'publish',
			'nopaging' => true,
			'meta_query' => array(
				array(
				'key' => '_thumbnail_id',
				'value' => $media_query->posts,
				'compare' => 'IN'),
			),
			'no_found_rows' => true, 
			'update_post_meta_cache' => false, 
			'update_post_term_cache' => false,
			'fields' => 'ids'
			), $extra_args );
		
		$posts_media_query = new WP_Query( $args );
		
		
		// 3. Include results into original results
		foreach( $posts_media_query->posts as $post_id ) {
			
			if( 'relevance' == $this->options['order_type'] && array_key_exists( $post_id, $original_results ) ) {
				$original_results[ $post_id ] += $thumbnail_weight;
			} else {
				$original_results[ $post_id ] = $thumbnail_weight;
			}
		}
		
		// In case there are images not set as Featured Image but still matching search terms and attached to posts
		foreach( $media_query->posts as $media_id ) {
			$parents = get_post_ancestors( $media_id );
			if( !empty( $parents ) && is_array( $parents ) ){
				
				foreach( $parents as $parent_id ) {
					//if belongs already to the thumbnail query, do nothing
					if( !in_array( $parent_id, $posts_media_query->posts ) ) {
						if( 'relevance' == $this->options['order_type'] && array_key_exists( $parent_id , $original_results ) ) {
							$original_results[ $parent_id ] += $media_weight;
						} else {
							$original_results[ $parent_id ] = $media_weight;
						}
					}
					
				}
				
				
			}
		}
		
		return $original_results;
	}
	
	/** Extend search query WHERE clause to include the Attachment CAPTION (post_excerpt) */
	function extend_posts_search( $where, $wp_query ) {

		if ( !$wp_query->is_search() || empty( $where ) ) {
			return $where;
		}
		
		global $wpdb;
		$search_words = explode(' ', strtolower( trim( $this->search_terms ) ) );
		$n = ( !empty( $wp_query->query_vars['exact'] ) ) ? '' : '%';
		
		// Search type is OR = add at the end the post_excerpt clauses
		if( 'or' == $this->options['query_type'] ) {
		
			//remove the last ')'
			$where = substr( rtrim( $where ), 0, -1 );
			foreach( $search_words as $word ) {
				$word = trim($word);
				$where .=  " OR ($wpdb->posts.post_excerpt LIKE '{$n}{$word}{$n}')";
			}
			$where .= ') ';
			
		} else {
			// Search type is AND (default) = add the post_excerpt clauses in the middle of each OR chunk
			$chunks_and = explode(' AND ', $where );
			$first = array_shift( $chunks_and );
			$new_where = ' AND ';
			$searchand = '';
			foreach( $chunks_and as $i => $chunk ) {
				
				if( false !== strpos( $chunk, 'post_title' ) && false !== strpos( $chunk, 'post_content' ) ) {
					
					$chunks_or = explode(' OR ', $chunk );
					
					if( !empty( $chunks_or[0] ) ) {
						$clause = str_replace( array('(', ')'), '', $chunks_or[0] );
						$clause = str_replace( array( 'post_title', 'post_content' ), 'post_excerpt', $clause );
						$chunk = preg_replace('/OR/', 'OR ('. $clause .') OR', $chunk, 1); 
					}
					
				} 
				$new_where .= $searchand . $chunk;
				$searchand = ' AND ';
			
			}
		}
		
		
		
		if( !empty( $new_where ) ) {
			return $new_where;
		}
		
		return $where;
		
	}
	
	
	
/*
			$alt = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
			$image_title = $attachment->post_title;
			$caption = $attachment->post_excerpt;
			$description = $image->post_content;
*/
} //end class

?>