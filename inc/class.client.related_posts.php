<?php

class SimpleTags_Client_RelatedPosts {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add related posts in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( ( 'no' !== SimpleTags_Plugin::get_option_value( 'rp_embedded' ) ) || ( 1 === SimpleTags_Plugin::get_option_value( 'rp_feed' ) ) ) {
			add_filter( 'the_content', array( __CLASS__, 'the_content' ), 999993 );
		}
	}

	/**
	 * Auto add related posts to post content
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function the_content( $content = '' ) {
		// Hook already executed ? Check if HTML class exists
		if ( strpos( $content, 'st-related-posts' ) !== false ) {
			return $content;
		}

		// Get option
		$rp_embedded = SimpleTags_Plugin::get_option_value( 'rp_embedded' );

		$marker = false;
		if ( is_feed() ) {
			if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'rp_feed' ) ) {
				$marker = true;
			}
		} elseif ( ! empty( $rp_embedded ) ) {
			switch ( $rp_embedded ) {
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

		if ( $marker === true ) {
			return ( $content . self::get_related_posts( '', false ) );
		}

		return $content;
	}

	/**
	 * Generate related posts
	 *
	 * @param string $user_args
	 * @param bool $copyright
	 *
	 * @return string|array|boolean
	 */
	public static function get_related_posts( $user_args = '', $copyright = true ) {
		global $wpdb;

		// Get options
		$options = SimpleTags_Plugin::get_option();

		$defaults = array(
			'taxonomy'      => 'post_tag',
			'post_type'     => 'post',
			'number'        => 5,
			'order'         => 'count-desc',
			'format'        => 'list',
			'separator'     => '',
			'include_page'  => 'true',
			'exclude_posts' => '',
			'exclude_terms' => '',
			'post_id'       => 0,
			'excerpt_wrap'  => 55,
			'limit_days'    => 0,
			'min_shared'    => 1,
			'title'         => __( '<h4>Related posts</h4>', 'simpletags' ),
			'nopoststext'   => __( 'No related posts.', 'simpletags' ),
			'dateformat'    => get_option( 'date_format' ),
			'xformat'       => __( '<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags' ),
		);

		// Get values in DB
		$defaults['number']      = $options['rp_limit_qty'];
		$defaults['order']       = $options['rp_order'];
		$defaults['nopoststext'] = $options['rp_notagstext'];
		$defaults['title']       = $options['rp_title'];
		$defaults['xformat']     = $options['rp_xformat'];
		$defaults['taxonomy']    = $options['rp_taxonomy'];

		if ( empty( $user_args ) ) {
			$user_args = $options['rp_adv_usage'];
		}

		// Replace old markers by new
		$markers = array(
			'%date%'         => '%post_date%',
			'%permalink%'    => '%post_permalink%',
			'%title%'        => '%post_title%',
			'%commentcount%' => '%post_comment%',
			'%tagcount%'     => '%post_tagcount%',
			'%postid%'       => '%post_id%',
		);
		if ( ! is_array( $user_args ) ) {
			$user_args = strtr( $user_args, $markers );
		}

		$args = wp_parse_args( $user_args, $defaults );
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

		// Get cache if exist
		$results = false;

		// Generate key cache
		$key = md5( maybe_serialize( $user_args ) . '-' . $object_id );

		if ( $cache = wp_cache_get( 'related_posts' . $taxonomy, 'simpletags' ) ) {
			if ( isset( $cache[ $key ] ) ) {
				$results = $cache[ $key ];
			}
		}

		// If cache not exist, get datas and set cache
		if ( $results === false || $results === null ) {
			// Get get tags
			$current_terms = get_the_terms( (int) $object_id, $taxonomy );

			if ( $current_terms == false || is_wp_error( $current_terms ) ) {
				return SimpleTags_Client::output_content( 'st-related-posts', $format, $title, $nopoststext, $copyright );
			}

			// Number - Limit
			$number = (int) $number;
			if ( $number == 0 ) {
				$number = 5;
			} elseif ( $number > 50 ) {
				$number = 50;
			}
			$limit_sql = 'LIMIT 0, ' . $number;
			unset( $number );

			// Order tags before output (count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random)
			$order_by = '';
			$order    = strtolower( $order );
			switch ( $order ) {
				case 'count-asc':
					$order_by = 'counter ASC, p.post_title DESC';
					break;
				case 'random':
					$order_by = 'RAND()';
					break;
				case 'date-asc':
					$order_by = 'p.post_date ASC';
					break;
				case 'date-desc':
					$order_by = 'p.post_date DESC';
					break;
				case 'name-asc':
					$order_by = 'p.post_title ASC';
					break;
				case 'name-desc':
					$order_by = 'p.post_title DESC';
					break;
				default: // count-desc
					$order_by = 'counter DESC, p.post_title DESC';
					break;
			}

			// Limit days - 86400 seconds = 1 day
			$limit_days     = (int) $limit_days;
			$limit_days_sql = '';
			if ( $limit_days != 0 ) {
				$limit_days_sql = 'AND p.post_date > "' . date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ) . '"';
			}
			unset( $limit_days );

			// Make array post type
			if ( is_string( $post_type ) ) {
				$post_type = explode( ',', $post_type );
			}

			// Include_page
			$include_page = strtolower( $include_page );
			if ( $include_page == 'true' ) {
				$post_type[] = 'page';
			}
			unset( $include_page );

			// Build post type SQL
			$restrict_sql = "AND p.post_type IN ('" . implode( "', '", $post_type ) . "')";

			// Restrict posts
			$exclude_posts_sql = '';
			if ( $exclude_posts != '' ) {
				$exclude_posts     = (array) explode( ',', $exclude_posts );
				$exclude_posts     = array_unique( $exclude_posts );
				$exclude_posts_sql = "AND p.ID NOT IN (";
				foreach ( $exclude_posts as $value ) {
					$value = (int) $value;
					if ( $value > 0 && $value != $object_id ) {
						$exclude_posts_sql .= '"' . $value . '", ';
					}
				}
				$exclude_posts_sql .= '"' . $object_id . '")';
			} else {
				$exclude_posts_sql = "AND p.ID <> {$object_id}";
			}
			unset( $exclude_posts );

			// Restricts tags
			$terms_to_exclude = array();
			if ( $exclude_terms != '' ) {
				$exclude_terms = (array) explode( ',', $exclude_terms );
				$exclude_terms = array_unique( $exclude_terms );
				foreach ( $exclude_terms as $value ) {
					$terms_to_exclude[] = trim( $value );
				}
			}
			unset( $exclude_terms );

			// SQL Terms list
			$term_list = array();
			foreach ( (array) $current_terms as $term ) {
				if ( ! in_array( $term->name, $terms_to_exclude ) ) {
					$term_list[] = '"' . (int) $term->term_id . '"';
				}
			}
			$term_list = implode( ', ', $term_list );

			// Build SQL terms subqueries array
			$include_terms_sql = array();
			if ( ! empty( $term_list ) ) {
				$include_terms_sql[ $taxonomy ] = $term_list;
			}

			// Group Concat check if post_relatedtags is used by xformat...
			$select_gp_concat = '';
			if ( strpos( $xformat, '%post_relatedtags%' ) || $min_shared > 1 ) {
				$select_gp_concat = ', GROUP_CONCAT(tt.term_id) as terms_id';
			}

			// Check if post_excerpt is used by xformat...
			$select_excerpt = '';
			//if ( strpos( $xformat, '%post_excerpt%' ) ) {
			//	$select_excerpt = ', p.post_content, p.post_excerpt, p.post_password';
			//}

			// If empty return no posts text
			if ( empty( $include_terms_sql ) ) {
				return SimpleTags_Client::output_content( 'st-related-posts', $format, $title, $nopoststext, $copyright );
			}

			// Posts: title, comments_count, date, permalink, post_id, counter
			$results = $wpdb->get_results( "
				SELECT p.*, COUNT(tr.object_id) AS counter {$select_excerpt} {$select_gp_concat}
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				WHERE 1 = 1
				AND (tt.taxonomy = '{$taxonomy}' AND tt.term_id IN ({$term_list}))
				{$exclude_posts_sql}
				AND p.post_status = 'publish'
				AND p.post_date_gmt < '" . current_time( 'mysql' ) . "'
				{$limit_days_sql}
				{$restrict_sql}
				GROUP BY tr.object_id
				ORDER BY {$order_by}
				{$limit_sql}" );

			$cache[ $key ] = $results;
			wp_cache_set( 'related_posts' . $taxonomy, $cache, 'simpletags' );
		}

		if ( $format == 'object' || $format == 'array' ) {
			return $results;
		} elseif ( $results === false || empty( $results ) ) {
			return SimpleTags_Client::output_content( 'st-related-posts', $format, $title, $nopoststext, $copyright );
		}

		if ( empty( $dateformat ) ) {
			$dateformat = get_option( 'date_format' );
		}

		$output = array();
		// Replace placeholders
		foreach ( (array) $results as $result ) {
			if ( ( $min_shared > 1 && ( count( explode( ',', $result->terms_id ) ) < $min_shared ) ) || ! is_object( $result ) ) {
				continue;
			}

			$element_loop = $xformat;
			$post_title   = apply_filters( 'the_title', $result->post_title );
			$element_loop = str_replace( '%post_date%', mysql2date( $dateformat, $result->post_date ), $element_loop );
			$element_loop = str_replace( '%post_permalink%', get_permalink( $result ), $element_loop );
			$element_loop = str_replace( '%post_title%', $post_title, $element_loop );
			$element_loop = str_replace( '%post_title_attribute%', esc_html( strip_tags( $post_title ) ), $element_loop );
			$element_loop = str_replace( '%post_comment%', (int) $result->comment_count, $element_loop );
			$element_loop = str_replace( '%post_tagcount%', (int) $result->counter, $element_loop );
			$element_loop = str_replace( '%post_id%', $result->ID, $element_loop );

			if ( isset( $result->terms_id ) ) {
				$element_loop = str_replace( '%post_relatedtags%', self::get_tags_from_id( $result->terms_id, $taxonomy ), $element_loop );
			}

			if ( isset( $result->post_excerpt ) || isset( $result->post_content ) ) {
				$element_loop = str_replace( '%post_excerpt%', self::get_excerpt_post( $result->post_excerpt, $result->post_content, $result->post_password, $excerpt_wrap ), $element_loop );
			}

			$output[] = $element_loop;
		}

		return SimpleTags_Client::output_content( 'st-related-posts', $format, $title, $output, $copyright, $separator );
	}

	/**
	 * Build excerpt from post data with specific lenght
	 *
	 * @param string $excerpt
	 * @param string $content
	 * @param string $password
	 * @param integer $excerpt_length
	 *
	 * @return string
	 * @author Amaury Balmer
	 */
	public static function get_excerpt_post( $excerpt = '', $content = '', $password = '', $excerpt_length = 55 ) {
		if ( ! empty( $password ) ) { // if there's a password
			if ( $_COOKIE[ 'wp-postpass_' . COOKIEHASH ] != $password ) { // and it doesn't match the cookie
				return __( 'There is no excerpt because this is a protected post.', 'simpletags' );
			}
		}

		if ( ! empty( $excerpt ) ) {
			return apply_filters( 'get_the_excerpt', $excerpt );
		} else { // Fake excerpt
			$content = str_replace( ']]>', ']]&gt;', $content );
			$content = strip_tags( $content );

			$excerpt_length = (int) $excerpt_length;
			if ( 0 === $excerpt_length ) {
				$excerpt_length = 55;
			}

			$words = explode( ' ', $content, $excerpt_length + 1 );
			if ( count( $words ) > $excerpt_length ) {
				array_pop( $words );
				array_push( $words, '[...]' );
				$content = implode( ' ', $words );
			}

			return $content;
		}
	}

	/**
	 * Get and format tags from list ID (SQL Group Concat)
	 *
	 * @param string $terms
	 * @param string $taxonomy
	 *
	 * @return string
	 * @author Amaury Balmer
	 */
	public static function get_tags_from_id( $terms = '', $taxonomy = 'post_tag' ) {
		if ( empty( $terms ) ) {
			return '';
		}

		// Get tags since Term ID.
		$terms = (array) get_terms( $taxonomy, 'include=' . $terms );
		if ( empty( $terms ) ) {
			return '';
		}

		// HTML Rel (tag)
		$rel = SimpleTags_Client::get_rel_attribut();

		$output = array();
		foreach ( (array) $terms as $term ) {
			$link = get_term_link( $term->term_id, $term->taxonomy );
			if ( empty( $link ) || is_wp_error( $link ) ) {
				continue;
			}

			$output[] = '<a href="' . $link . '" title="' . esc_attr( sprintf( _n( '%d topic', '%d topics', (int) $term->count, 'simpletags' ), $term->count ) ) . '" ' . $rel . '>' . esc_html( $term->name ) . '</a>';
		}

		return implode( ', ', $output );
	}
}
