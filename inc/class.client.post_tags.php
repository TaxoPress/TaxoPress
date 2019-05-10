<?php

class SimpleTags_Client_PostTags {

	/**
	 * SimpleTags_Client_PostTags constructor.
	 */
	public function __construct() {
		// Add adv post tags in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( 'no' !== SimpleTags_Plugin::get_option_value( 'tt_embedded' ) || 1 === (int) SimpleTags_Plugin::get_option_value( 'tt_feed' ) ) {
			add_filter( 'the_content', array( __CLASS__, 'the_content' ), 999992 );
		}

		add_shortcode( 'st-the-tags', array( __CLASS__, 'shortcode' ) );
		add_shortcode( 'st_the_tags', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Replace marker by tags in post content, use ShortCode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts( array( 'param' => '' ), $atts );
		extract( $atts );

		$param = html_entity_decode( $param );
		$param = trim( $param );

		if ( empty( $param ) ) {
			$param = 'title=';
		}

		return self::extendedPostTags( $param );
	}

	/**
	 * Auto add current tags post to post content
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_content( $content = '' ) {
		// Hook already executed ? Check if HTML class exists
		if ( strpos( $content, 'st-post-tags' ) !== false ) {
			return $content;
		}

		// Get option
		$tt_embedded = SimpleTags_Plugin::get_option_value( 'tt_embedded' );

		$marker = false;
		if ( is_feed() && 1 === (int) SimpleTags_Plugin::get_option_value( 'tt_feed' ) ) {
			$marker = true;
		} elseif ( ! empty( $tt_embedded ) ) {
			switch ( $tt_embedded ) {
				case 'blogonly':
					$marker = ( is_feed() ) ? false : true;
					break;
				case 'homeonly':
					$marker = ( is_home() ) ? true : false;
					break;
				case 'singularonly':
					$marker = ( is_singular() ) ? true : false;
					break;
				case 'singleonly':
					$marker = ( is_single() ) ? true : false;
					break;
				case 'pageonly':
					$marker = ( is_page() ) ? true : false;
					break;
				case 'all':
					$marker = true;
					break;
				case 'no':
				default:
					$marker = false;
					break;
			}
		}

		if ( true === $marker ) {
			return ( $content . self::extendedPostTags( '', false ) );
		}

		return $content;
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
			'before'    => __( 'Tags: ', 'simpletags' ),
			'separator' => ', ',
			'after'     => '<br />',
			'post_id'   => 0,
			'inc_cats'  => 0,
			'xformat'   => __( '<a href="%tag_link%" title="%tag_name_attribute%" %tag_rel%>%tag_name%</a>', 'simpletags' ),
			'notagtext' => __( 'No tag for this post.', 'simpletags' ),
			'number'    => 0,
			'format'    => '',
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
		$taxonomies = ( 0 === (int) $inc_cats ) ? 'post_tag' : array( 'post_tag', 'category' );

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

			$terms = array_map( 'get_term', $taxterms );
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
			return $notagtext;
		}

		// HTML Rel
		$rel = SimpleTags_Client::get_rel_attribut();

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

		// Add container
		$output = $before . $output . $after;

		return SimpleTags_Client::output_content( 'st-post-tags', 'div', '', $output, $copyright );
	}
}
