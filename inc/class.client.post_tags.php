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
		$enable_hidden_terms = SimpleTags_Plugin::get_option_value('enable_hidden_terms');

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
			'format'    => 'flat',
			'ID'        => false,
			'taxonomy'  => false,
			'embedded'  => false,
			'feed'      => false,
			'hide_output' => 0,
			'wrap_class'  => '',
			'link_class'  => '',
			'hide_terms' => 0,
			'smallest'   => 15,
			'largest'    => 22,
			'unit'       => 'pt',
			'color'      => true,
			'mincolor'   => '#353535',
			'maxcolor'   => '#000000',
		);

		// Get values in DB
		$defaults['before']    = $options['tt_before'];
		$defaults['separator'] = $options['tt_separator'];
		$defaults['after']     = $options['tt_after'];
		$defaults['inc_cats']  = $options['tt_inc_cats'];
		$defaults['xformat']   = $options['tt_xformat'];
		$defaults['format']    = $options['tt_format'];
		$defaults['notagtext'] = $options['tt_notagstext'];
		$defaults['number']    = (int) $options['tt_number'];
		$defaults['smallest']  = isset($options['tt_smallest']) ? (int)$options['tt_smallest'] : 15;
		$defaults['largest']   = isset($options['tt_largest']) ? (int)$options['tt_largest'] : 22;
		$defaults['unit']      = isset($options['tt_unit']) ? $options['tt_unit'] : 'pt';
		$defaults['color']     = isset($options['tt_color']) ? $options['tt_color'] : 1;
		$defaults['mincolor']  = isset($options['tt_mincolor']) ? $options['tt_mincolor'] : '#353535';
		$defaults['maxcolor']  = isset($options['tt_maxcolor']) ? $options['tt_maxcolor'] : '#000000';
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

		$hidden_terms = [];
		if (!empty($enable_hidden_terms) && !empty($args['hide_terms']) && !empty($args['taxonomy'])) {
			$hidden_terms = get_transient('taxopress_hidden_terms_' . $args['taxonomy']);
			if (!empty($hidden_terms) && is_array($hidden_terms)) {
				$args['exclude'] = implode(',', $hidden_terms);
			}
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
		if (!empty($enable_hidden_terms) && !empty($hidden_terms)) {
			$terms = array_filter($terms, function ($term) use ($hidden_terms) {
				return !in_array($term->term_id, $hidden_terms);
			});
		} else {
			$terms = apply_filters('get_the_tags', $terms);
		}

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
				return SimpleTags_Client::output_content( 'st-post-tags', $format, $notagtext, '', $copyright, $separator, '', '', $before, $after );
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
		$output = array();

		// Calculate scaling for font size and color if enabled
		$counts = array();
		foreach ( (array) $terms as $term ) {
			if ( is_object( $term ) ) {
				$counts[ $term->term_id ] = $term->count;
			}
		}

		// Use full RBG code
		if ( strlen( $maxcolor ) == 4 ) {
			$maxcolor = $maxcolor . substr( $maxcolor, 1, strlen( $maxcolor ) );
		}
		if ( strlen( $mincolor ) == 4 ) {
			$mincolor = $mincolor . substr( $mincolor, 1, strlen( $mincolor ) );
		}

		// Check as smallest inferior or equal to largest
		if ( $smallest > $largest ) {
			$smallest = $largest;
		}

		// Scaling - Hard value for the moment
		$scale_min = 0;
		$scale_max = 10;

		if (!empty($counts)) {
			$minval = min( $counts );
			$maxval = max( $counts );
		} else {
			$minval = $maxval = 0;
		}

		$minout = max( $scale_min, 0 );
		$maxout = max( $scale_max, $minout );

		$scale = ( $maxval > $minval ) ? ( ( $maxout - $minout ) / ( $maxval - $minval ) ) : 0;

		foreach ( (array) $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			// Calculate scale_result for this term
			$scale_result = null;
			if ($scale !== 0 && isset($counts[$term->term_id])) {
				$scale_result = (int) ( ( $term->count - $minval ) * $scale + $minout );
			} else {
				$scale_result = ( $scale_max - $scale_min ) / 2;
			}

			// Remove color/size markers if disabled
			if ( $color == '0' || $color === false || $color === 'false' ) {
				$xformat = str_replace( '%tag_color%', '', $xformat );
			}
			if ( $smallest == $largest ) {
				$xformat = str_replace( '%tag_size%', '', $xformat );
			}

			$output[] = SimpleTags_Client::format_internal_tag( $xformat, $term, $rel, $scale_result, $scale_max, $scale_min, $largest, $smallest, $unit, $maxcolor, $mincolor
			);
		}

		return SimpleTags_Client::output_content( 'st-post-tags '.taxopress_format_class($wrap_class).'', $format, '', $output, $copyright, $separator, '', '', $before, $after );
	}
}
