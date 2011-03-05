<?php
class SimpleTags_Client_PostTags extends SimpleTags_Client {
	function SimpleTags_Client_PostTags() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Add adv post tags in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $options['tt_embedded'] != 'no' || $options['tt_feed'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlinePostTags'), 999992);
		}
	}
	
	/**
	 * Auto add current tags post to post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlinePostTags( $content = '' ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		$marker = false;
		if ( is_feed() ) {
			if ( $options['tt_feed'] == '1' ) {
				$marker = true;
			}
		} elseif ( isset($options['tt_embedded']) ) {
			switch ( $options['tt_embedded'] ) {
				case 'blogonly' :
					$marker = ( is_feed() ) ? false : true;
					break;
				case 'homeonly' :
					$marker = ( is_home() ) ? true : false;
					break;
				case 'singularonly' :
					$marker = ( is_singular() ) ? true : false;
					break;
				case 'singleonly' :
					$marker = ( is_single() ) ? true : false;
					break;
				case 'pageonly' :
					$marker = ( is_page() ) ? true : false;
					break;
				case 'all' :
					$marker = true;
					break;
				case 'no' :
				default:
					$marker = false;
					break;
			}
		}
		
		if ( $marker === true ) {
			return ( $content . $this->extendedPostTags( '', false ) );
		}
		return $content;
	}
	
	/**
	 * Generate current post tags
	 *
	 * @param string $args
	 * @return string
	 */
	function extendedPostTags( $args = '', $copyright = true ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Default values
		$defaults = array(
			'before' 	=> __('Tags: ', 'simpletags'),
			'separator' => ', ',
			'after' 	=> '<br />',
			'post_id' 	=> 0,
			'inc_cats' 	=> 0,
			'xformat' 	=> __('<a href="%tag_link%" title="%tag_name_attribute%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'notagtext' => __('No tag for this post.', 'simpletags'),
			'number' 	=> 0,
			'format' 	=> ''
		);
		
		// Get values in DB
		$defaults['before'] = $options['tt_before'];
		$defaults['separator'] = $options['tt_separator'];
		$defaults['after'] = $options['tt_after'];
		$defaults['inc_cats'] = $options['tt_inc_cats'];
		$defaults['xformat'] = $options['tt_xformat'];
		$defaults['notagtext'] = $options['tt_notagstext'];
		$defaults['number'] = (int) $options['tt_number'];
		if ( empty($args) ) {
			$args = $options['tt_adv_usage'];
		}
		
		// Extract data in variables
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		
		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
		
		// Clean memory
		$args = array();
		$defaults = array();
		
		// Choose post ID
		$object_id = (int) $post_id;
		if ( $object_id == 0 ) {
			global $post;
			$object_id = (int) $post->ID;
			if ( $object_id == 0 ) {
				return false;
			}
		}
		
		// Get categories ?
		$taxonomy = ( (int) $inc_cats == 0 ) ? 'post_tag' : array('post_tag', 'category');
		
		// Get terms
		$terms = apply_filters( 'get_the_tags', wp_get_object_terms($object_id, $taxonomy) );
		
		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			shuffle($terms); // Randomize terms
			$terms = array_slice( $terms, 0, $number );
		}
		
		// Return for object format
		if ( $format == 'object' ) {
			return $terms;
		}
		
		// If no terms, return text nothing.
		if ( empty($terms) ) {
			return $notagtext;
		}
		
		// HTML Rel
		$rel = $this->buildRel();
		
		// Prepare output
		foreach ( (array) $terms as $term ) {
			if ( !is_object($term) ) {
				continue;
			}
			
			$output[] = $this->formatInternalTag( $xformat, $term, $rel, null );
		}
		
		// Clean memory
		$terms = array();
		unset($terms, $term);
		
		
		// Array to string
		if ( is_array($output) && !empty($output) ) {
			$output = implode($separator, $output);
		} else {
			$output = $notagtext;
		}
		
		// Add container
		$output = $before . $output . $after;
		
		return $this->outputContent( '', 'string', '', $output, $copyright );
	}
}
?>