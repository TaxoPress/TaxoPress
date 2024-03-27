<?php

class SimpleTags_Client_PostTags {

	/**
	 * SimpleTags_Client_PostTags constructor.
	 */
	public function __construct() {

	}

	/**
	 * Generate current post tags
	 *
	 * @param string $args
	 * @param bool $copyright
	 *
	 * @return string|array|boolean
	 */
	public static function extendedPostTags( $args = '', $copyright = true ) {
		// Get options
		$options = SimpleTags_Plugin::get_option();

		// Default values
		$defaults = array(
			'before'    => __( 'Tags: ', 'simple-tags' ),
			'separator' => ', ',
			'after'     => '<br />',
			'post_id'   => 0,
			'inc_cats'  => 0,
			'xformat'   => __( '<a href="%tag_link%" title="%tag_name_attribute%" %tag_rel%>%tag_name%</a>', 'simple-tags' ),
			'notagtext' => __( 'No tag for this post.', 'simple-tags' ),
			'number'    => 0,
			'format'    => '',
			'ID'        => false,
			'taxonomy'  => false,
			'embedded'  => false,
			'feed'      => false,
			'hide_output' => 0,
			'wrap_class'  => '',
			'link_class'  => '',
		);

		// Get values in DB
		$defaults['before']    = $options['tt_before'];
		$defaults['separator'] = $options['tt_separator'];
		$defaults['after']     = $options['tt_after'];
		$defaults['inc_cats']  = $options['tt_inc_cats'];
		$defaults['xformat']   = $options['tt_xformat'];
		$defaults['notagtext'] = $options['tt_notagstext'];
		$defaults['number']    = (int) $options['tt_number'];
		if ( empty( $args ) ) {
			$args = $options['tt_adv_usage'];
		}

		// Extract data in variables
		$args = wp_parse_args( $args, $defaults );
		extract( $args );

		// If empty use default xformat !
		if ( empty( $xformat ) ) {
			$xformat = $defaults['xformat'];
		}

		$xformat = taxopress_sanitize_text_field($xformat);

		// Choose post ID
		$object_id = (int) $post_id;
		if ( 0 === $object_id ) {
			global $post;
			if ( ! isset( $post->ID ) || 0 === (int) $post->ID ) {
				return false;
			}

			$object_id = (int) $post->ID;
		}

		// Get categories ?
        if($ID){
		    $taxonomies = $taxonomy;
        }else{
		    $taxonomies = ( 0 === (int) $inc_cats ) ? 'post_tag' : array( 'post_tag', 'category' );
        }

		// Get terms
		// According to codex https://developer.wordpress.org/reference/functions/get_object_term_cache/, $taxonomy must be a string
		$terms = array();
		foreach ( (array) $taxonomies as $taxonomy ) {
			$taxterms = get_object_term_cache( $object_id, $taxonomy );

			if ( false === $taxterms ) {
				$taxterms = wp_get_object_terms( $object_id, $taxonomy );
				$to_cache = array();
				foreach ( $taxterms as $key => $term ) {
					$to_cache[ $key ] = $term->data;
				}
				wp_cache_add( $object_id, $to_cache, $taxonomy . '_relationships' );
			}
			if ($taxterms && !is_wp_error($taxterms)) {
				$terms = array_map('get_term', $taxterms);
			}
		}

		// Hook
		$terms = apply_filters( 'get_the_tags', $terms );

		// Limit to max quantity if set
		$number = (int) $number;
		if ( 0 !== $number ) {
			shuffle( $terms ); // Randomize terms
			$terms = array_slice( $terms, 0, $number );
		}

		// Return for object format
		if ( 'object' === $format ) {
			return $terms;
		}

		// If no terms, return text nothing.
		if ( empty( $terms ) ) {
            if((int)$hide_output === 0){
			    return $notagtext;
            }else{
                return '';
            }
		}

		// HTML Rel
		$rel = SimpleTags_Client::get_rel_attribut();

		//update xformat with class link class
		if(!empty(trim($link_class))){
			$link_class = taxopress_format_class($link_class);
			$xformat = taxopress_add_class_to_format($xformat, $link_class);
		}

		// Prepare output
		foreach ( (array) $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			$output[] = SimpleTags_Client::format_internal_tag( $xformat, $term, $rel, null );
		}

		// Array to string
		if ( is_array( $output ) && ! empty( $output ) ) {
			$output = implode( $separator, $output );
		} else {
			$output = $notagtext;
		}

		return SimpleTags_Client::output_content( 'st-post-tags '.taxopress_format_class($wrap_class).'', 'div', '', $output, $copyright, '', '', '', $before, $after );
	}
}
