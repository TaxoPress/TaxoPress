<?php

class SimpleTags_Client_TagCloud {
	/**
	 * Init Embedded tag cloud
	 *
	 * SimpleTags_Client_TagCloud constructor.
	 */
	public function __construct() {
		if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'allow_embed_tcloud' ) ) {
			add_shortcode( 'st_tag_cloud', array( __CLASS__, 'shortcode' ) );
			add_shortcode( 'st-tag-cloud', array( __CLASS__, 'shortcode' ) );
		}
	}

	/**
	 * Replace marker by a tag cloud in post content, use ShortCode
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

		return self::extendedTagCloud( $param, false );
	}

	/**
	 * Sort an array without accent for naturel order :)
	 *
	 * @param string $a
	 * @param string $b
	 *
	 * @return boolean
	 */
	public static function uksort_by_name( $a = '', $b = '' ) {
		return strnatcasecmp( remove_accents( $a ), remove_accents( $b ) );
	}

	/**
	 * Generate extended tag cloud
	 *
	 * @param string $args
	 *
	 * @return string|array
	 */
	public static function extendedTagCloud( $args = '', $copyright = true ) {
		$defaults = array(
			// Simple Tag global options defaults
			'selectionby' => 'count',
			'selection'   => 'desc',
			'orderby'     => 'random',
			'order'       => 'asc',
			'format'      => 'flat',
			'xformat'     => __( '<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simple-tags' ),
			'number'      => 45,
			'notagstext'  => __( 'No tags.', 'simple-tags' ),
			'title'       => __( '<h4>Tag Cloud</h4>', 'simple-tags' ),
			'maxcolor'    => '#000000',
			'mincolor'    => '#CCCCCC',
			'largest'     => 22,
			'smallest'    => 8,
			'unit'        => 'pt',
			'taxonomy'    => 'post_tag', // Note: saved as an option but no UI to set it
			// Simple Tag other defaults
			'size'        => 'true',
			'color'       => 'true',
			'exclude'     => '',
			'include'     => '',
			'limit_days'  => 0,
			'min_usage'   => 0,
			'category'    => 0,
			'ID'          => 0,
			'hide_title'  => 0,
			'hide_output' => 0,
			'post_type'   => '',
			'wrap_class'  => '',
			'link_class'  => '',
		);

		// Get options
		$options = SimpleTags_Plugin::get_option();

		// Get values in DB
		$defaults['selectionby'] = $options['cloud_selectionby'];
		$defaults['selection']   = $options['cloud_selection'];
		$defaults['orderby']     = $options['cloud_orderby'];
		$defaults['order']       = $options['cloud_order'];
		$defaults['format']      = $options['cloud_format'];
		$defaults['xformat']     = $options['cloud_xformat'];
		$defaults['number']      = $options['cloud_limit_qty'];
		$defaults['notagstext']  = $options['cloud_notagstext'];
		$defaults['title']       = $options['cloud_title'];
		$defaults['maxcolor']    = $options['cloud_max_color'];
		$defaults['mincolor']    = $options['cloud_min_color'];
		$defaults['largest']     = $options['cloud_max_size'];
		$defaults['smallest']    = $options['cloud_min_size'];
		$defaults['unit']        = $options['cloud_unit'];
		$defaults['taxonomy']    = $options['cloud_taxonomy'];

		$adv_usage = $options['cloud_adv_usage'];
		if ( empty( $args ) ) {
			$args = $adv_usage;
		} else {
			$args = $adv_usage . "&" . $args;
		}
		$args = wp_parse_args( $args, $defaults );

		// Add compatibility tips with old field syntax
		if ( isset( $args['cloud_sort'] ) ) {
			$args['cloud_order'] = $args['cloud_sort'];
			unset( $args['cloud_sort'] );
		}

		// Translate selection order
		if ( isset( $args['cloud_order'] ) ) {
			$args['orderby'] = self::compatOldOrder( $args['cloud_order'], 'orderby' );
			$args['order']   = self::compatOldOrder( $args['cloud_order'], 'order' );
		}

		// Category names to ID codes
		if ( isset( $args['category'] ) ) {
			$category = explode( ",", $args['category'] );
			foreach ( $category as $key => $name ) {
				$category[ $key ] = is_numeric( $name ) ? $name : get_cat_ID( $name );
				if ( $category[ $key ] == 0 ) {
					unset( $category[ $key ] );
				}
			}
			$args['category'] = implode( ",", $category );
		}

        // Get correct taxonomy ?
		$taxonomy = self::_get_current_taxonomy( $args['taxonomy'] );

		// Get terms
		$terms = self::getTags( $args, $taxonomy );
		extract( $args ); // Params to variables

		// If empty use default xformat !
		if ( empty( $xformat ) ) {
			$xformat = $defaults['xformat'];
		}

        //remove title if in settings
        if((int)$hide_title > 0){
            $title = '';
        }

		if ( empty( $terms ) ) {
            if((int)$hide_output === 0){
			    return SimpleTags_Client::output_content( 'st-tag-cloud', $format, $title, $notagstext, $copyright, '', $wrap_class, $link_class );
            }else{
                return '';
            }
		}

		$counts = $terms_data = array();
		foreach ( (array) $terms as $term ) {
			$counts[ $term->name ]     = $term->count;
			$terms_data[ $term->name ] = $term;
		}

		// Remove temp data from memory
		$terms = array();
		unset( $terms );

		// Use full RBG code
		if ( strlen( $maxcolor ) == 4 ) {
			$maxcolor = $maxcolor . substr( $maxcolor, 1, strlen( $maxcolor ) );
		}
		if ( strlen( $mincolor ) == 4 ) {
			$mincolor = $mincolor . substr( $mincolor, 1, strlen( $mincolor ) );
		}

		// Check as smallest inferior or egal to largest
		if ( $smallest > $largest ) {
			$smallest = $largest;
		}

		// Scaling - Hard value for the moment
		$scale_min = 0;
		$scale_max = 10;

		$minval = min( $counts );
		$maxval = max( $counts );

		$minout = max( $scale_min, 0 );
		$maxout = max( $scale_max, $minout );

		$scale = ( $maxval > $minval ) ? ( ( $maxout - $minout ) / ( $maxval - $minval ) ) : 0;

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace( '%tag_color%', '', $xformat );
		}

		// Remove size marquer if size = false
		if ( $size == 'false' ) {
			$xformat = str_replace( '%tag_size%', '', $xformat );
		}

		// Order terms before output
		// count, name, rand | asc, desc

		$orderby = strtolower( $orderby );
		if ( $orderby == 'count' ) {
			asort( $counts );
		} elseif ( $orderby == 'name' ) {
			uksort( $counts, array( __CLASS__, 'uksort_by_name' ) );
		} else { // rand
			SimpleTags_Client::random_array( $counts );
		}

		$order = strtolower( $order );
		if ( $order == 'desc' && $orderby != 'random' ) {
			$counts = array_reverse( $counts );
		}

		$output = array();

		//update xformat with class link class
		if(!empty(trim($link_class))){
			$link_class = taxopress_format_class($link_class);
			$xformat = taxopress_add_class_to_format($xformat, $link_class);
		}
		
		foreach ( (array) $counts as $term_name => $count ) {
			if ( ! is_object( $terms_data[ $term_name ] ) ) {
				continue;
			}

			$term         = $terms_data[ $term_name ];
			$scale_result = (int) ( $scale <> 0 ? ( ( $term->count - $minval ) * $scale + $minout ) : ( $scale_max - $scale_min ) / 2 );
			$output[]     = SimpleTags_Client::format_internal_tag( $xformat, $term, $rel, $scale_result, $scale_max, $scale_min, $largest, $smallest, $unit, $maxcolor, $mincolor );
		}

		return SimpleTags_Client::output_content( 'st-tag-cloud', $format, $title, $output, $copyright, '', $wrap_class, $link_class );
	}


	/**
	 * Generate extended tag result
	 *
	 * @param string $args
	 *
	 * @return array
	 */
	public static function extendedTagResult( $args = '', $copyright = true ) {
		$defaults = array(
			// Simple Tag global options defaults
			'selectionby' => 'count',
			'selection'   => 'desc',
			'orderby'     => 'random',
			'order'       => 'asc',
			'format'      => 'flat',
			'xformat'     => __( '<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simple-tags' ),
			'number'      => 45,
			'notagstext'  => __( 'No tags.', 'simple-tags' ),
			'title'       => __( '<h4>Tag Cloud</h4>', 'simple-tags' ),
			'maxcolor'    => '#000000',
			'mincolor'    => '#CCCCCC',
			'largest'     => 22,
			'smallest'    => 8,
			'unit'        => 'pt',
			'taxonomy'    => 'post_tag', // Note: saved as an option but no UI to set it
			// Simple Tag other defaults
			'size'        => 'true',
			'color'       => 'true',
			'exclude'     => '',
			'include'     => '',
			'limit_days'  => 0,
			'min_usage'   => 0,
			'category'    => 0
		);

		// Get options
		$options = SimpleTags_Plugin::get_option();

		// Get values in DB
		$defaults['selectionby'] = $options['cloud_selectionby'];
		$defaults['selection']   = $options['cloud_selection'];
		$defaults['orderby']     = $options['cloud_orderby'];
		$defaults['order']       = $options['cloud_order'];
		$defaults['format']      = $options['cloud_format'];
		$defaults['xformat']     = $options['cloud_xformat'];
		$defaults['number']      = $options['cloud_limit_qty'];
		$defaults['notagstext']  = $options['cloud_notagstext'];
		$defaults['title']       = $options['cloud_title'];
		$defaults['maxcolor']    = $options['cloud_max_color'];
		$defaults['mincolor']    = $options['cloud_min_color'];
		$defaults['largest']     = $options['cloud_max_size'];
		$defaults['smallest']    = $options['cloud_min_size'];
		$defaults['unit']        = $options['cloud_unit'];
		$defaults['taxonomy']    = $options['cloud_taxonomy'];

		$adv_usage = $options['cloud_adv_usage'];
		if ( empty( $args ) ) {
			$args = $adv_usage;
		} else {
			$args = $adv_usage . "&" . $args;
		}
		$args = wp_parse_args( $args, $defaults );

		// Add compatibility tips with old field syntax
		if ( isset( $args['cloud_sort'] ) ) {
			$args['cloud_order'] = $args['cloud_sort'];
			unset( $args['cloud_sort'] );
		}

		// Translate selection order
		if ( isset( $args['cloud_order'] ) ) {
			$args['orderby'] = self::compatOldOrder( $args['cloud_order'], 'orderby' );
			$args['order']   = self::compatOldOrder( $args['cloud_order'], 'order' );
		}

		// Category names to ID codes
		if ( isset( $args['category'] ) ) {
			$category = explode( ",", $args['category'] );
			foreach ( $category as $key => $name ) {
				$category[ $key ] = is_numeric( $name ) ? $name : get_cat_ID( $name );
				if ( $category[ $key ] == 0 ) {
					unset( $category[ $key ] );
				}
			}
			$args['category'] = implode( ",", $category );
		}

        // Get correct taxonomy ?
		$taxonomy = self::_get_current_taxonomy( $args['taxonomy'] );

		// Get terms
		$terms = self::getTags( $args, $taxonomy );
		extract( $args ); // Params to variables

		// If empty use default xformat !
		if ( empty( $xformat ) ) {
			$xformat = $defaults['xformat'];
		}

		if ( empty( $terms ) ) {
			return [];
		}

		$counts = $terms_data = array();
		foreach ( (array) $terms as $term ) {
			$counts[ $term->name ]     = $term->count;
			$terms_data[ $term->name ] = $term;
		}

		// Remove temp data from memory
		$terms = array();
		unset( $terms );

		// Use full RBG code
		if ( strlen( $maxcolor ) == 4 ) {
			$maxcolor = $maxcolor . substr( $maxcolor, 1, strlen( $maxcolor ) );
		}
		if ( strlen( $mincolor ) == 4 ) {
			$mincolor = $mincolor . substr( $mincolor, 1, strlen( $mincolor ) );
		}

		// Check as smallest inferior or egal to largest
		if ( $smallest > $largest ) {
			$smallest = $largest;
		}

		// Scaling - Hard value for the moment
		$scale_min = 0;
		$scale_max = 10;

		$minval = min( $counts );
		$maxval = max( $counts );

		$minout = max( $scale_min, 0 );
		$maxout = max( $scale_max, $minout );

		$scale = ( $maxval > $minval ) ? ( ( $maxout - $minout ) / ( $maxval - $minval ) ) : 0;

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace( '%tag_color%', '', $xformat );
		}

		// Remove size marquer if size = false
		if ( $size == 'false' ) {
			$xformat = str_replace( '%tag_size%', '', $xformat );
		}

		// Order terms before output
		// count, name, rand | asc, desc

		$orderby = strtolower( $orderby );
		if ( $orderby == 'count' ) {
			asort( $counts );
		} elseif ( $orderby == 'name' ) {
			uksort( $counts, array( __CLASS__, 'uksort_by_name' ) );
		} else { // rand
			SimpleTags_Client::random_array( $counts );
		}

		$order = strtolower( $order );
		if ( $order == 'desc' && $orderby != 'random' ) {
			$counts = array_reverse( $counts );
		}

		$output = array();
		foreach ( (array) $counts as $term_name => $count ) {
			if ( ! is_object( $terms_data[ $term_name ] ) ) {
				continue;
			}
			$output[]   = $terms_data[ $term_name ];
		}


		return $output;
	}

	/**
	 * Check if taxonomy exist and return it, otherwise return default post tags.
	 *
	 * @param string|array $taxonomies
	 * @param boolean $force_single
	 *
	 * @return array|string
	 * @author WebFactory Ltd
	 */
	public static function _get_current_taxonomy( $taxonomies, $force_single = false ) {
		if ( is_array( $taxonomies ) ) {
			foreach ( $taxonomies as $key => $value ) {
				if ( ! taxonomy_exists( $value ) ) { // Remove from array is taxonomy not exist !
					unset( $taxonomies[ $key ] );
				} elseif ( true === $force_single ) {// Force single instead array ?
					return $value;
				}
			}

			return $taxonomies;
		} elseif ( taxonomy_exists( $taxonomies ) ) {
			return $taxonomies;
		}

		return 'post_tag';
	}

	/**
	 * Extended get_tags public static function that use getTerms function
	 *
	 * @param string|array $args
	 * @param string|array $taxonomy
	 *
	 * @return array
	 */
	public static function getTags( $args = '', $taxonomy = 'post_tag' ) {
		$key = md5( maybe_serialize( $args ) . $taxonomy );

		// Get cache if exist
		if ( $cache = wp_cache_get( 'st_get_tags', 'simple-tags' ) ) {
			if ( isset( $cache[ $key ] ) ) {
				return apply_filters( 'get_tags', $cache[ $key ], $args );
			}
		}

		// Get tags
		$terms = self::getTerms( $taxonomy, $args );
		if ( empty( $terms ) ) {
			return array();
		}

		$cache[ $key ] = $terms;
		wp_cache_set( 'st_get_tags', $cache, 'simple-tags' );

		$terms = apply_filters( 'st_get_tags', $terms, $args );

		return $terms;
	}

	/**
	 * Helper public static function for keep compatibility with old options TaxoPress widgets
	 *
	 * @param string $old_value
	 * @param string $key
	 *
	 * @return string
	 * @author WebFactory Ltd
	 */
	public static function compatOldOrder( $old_value = '', $key = '' ) {
		$return = array();

		switch ( strtolower( $old_value ) ) { // count-asc/count-desc/name-asc/name-desc/random
			case 'count-asc':
				$return = array( 'orderby' => 'count', 'order' => 'ASC' );
				break;
			case 'rand':
			case 'random':
				$return = array( 'orderby' => 'random', 'order' => '' );
				break;
			case 'name-asc':
				$return = array( 'orderby' => 'name', 'order' => 'ASC' );
				break;
			case 'name-desc':
				$return = array( 'orderby' => 'name', 'order' => 'DESC' );
				break;
			case 'count-desc':
				$return = array( 'orderby' => 'count', 'order' => 'DESC' );
				break;
		}

		if ( empty( $return ) || ! isset( $return[ $key ] ) ) {
			return $old_value;
		}

		return $return[ $key ];
	}

	/**
	 * Extended get_terms public static function support
	 * - Limit category
	 * - Limit days
	 * - Selection restrict
	 * - Min usage
	 *
	 * @param string|array $taxonomies
	 * @param string $args
	 *
	 * @return array|WP_Error
	 */
	public static function getTerms( $taxonomies, $args = '' ) {
		global $wpdb;
		$empty_array   = array();
		$join_relation = false;

		$single_taxonomy = false;
		if ( ! is_array( $taxonomies ) ) {
			$single_taxonomy = true;
			$taxonomies      = array( $taxonomies );
		}

		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists( $taxonomy ) ) {
				$error = new WP_Error( 'invalid_taxonomy', __( 'Invalid Taxonomy' ) );

				return $error;
			}
		}
		$in_taxonomies = "'" . implode( "', '", $taxonomies ) . "'";

		$defaults = array(
			'orderby'       => 'name',
			'order'         => 'ASC',
			'hide_empty'    => true,
			'exclude'       => array(),
			'exclude_tree'  => array(),
			'include'       => array(),
			'number'        => '',
			'fields'        => 'all',
			'slug'          => '',
			'parent'        => '',
			'hierarchical'  => true,
			'child_of'      => 0,
			'get'           => '',
			'name__like'    => '',
			'pad_counts'    => false,
			'offset'        => '',
			'search'        => '',
			// TaxoPress added
			'limit_days'    => 0,
			'category'      => 0,
			'min_usage'     => 0,
			'st_name__like' => '',
			'post_type'     => false
		);

		$args = wp_parse_args( $args, $defaults );

		// Translate selection order
		$args['orderby'] = self::compatOldOrder( $args['selectionby'], 'orderby' );
		$args['order']   = self::compatOldOrder( $args['selection'], 'order' );

		$args['number']     = absint( $args['number'] );
		$args['offset']     = absint( $args['offset'] );
		$args['limit_days'] = absint( $args['limit_days'] );
		$args['min_usage']  = absint( $args['min_usage'] );

		if ( ! $single_taxonomy || ! is_taxonomy_hierarchical( $taxonomies[0] ) ||
		     '' !== $args['parent']
		) {
			$args['child_of']     = 0;
			$args['hierarchical'] = false;
			$args['pad_counts']   = false;
		}

		if ( 'all' == $args['get'] ) {
			$args['child_of']     = 0;
			$args['hide_empty']   = 0;
			$args['hierarchical'] = false;
			$args['pad_counts']   = false;
		}
		extract( $args, EXTR_SKIP );

		if ( $child_of ) {
			$hierarchy = _get_term_hierarchy( $taxonomies[0] );
			if ( ! isset( $hierarchy[ $child_of ] ) ) {
				return $empty_array;
			}
		}

		if ( $parent ) {
			$hierarchy = _get_term_hierarchy( $taxonomies[0] );
			if ( ! isset( $hierarchy[ $parent ] ) ) {
				return $empty_array;
			}
		}

		// $args can be whatever, only use the args defined in defaults to compute the key
		$filter_key   = ( has_filter( 'list_terms_exclusions' ) ) ? serialize( $GLOBALS['wp_filter']['list_terms_exclusions'] ) : '';
		$key          = md5( serialize( compact( array_keys( $defaults ) ) ) . serialize( $taxonomies ) . $filter_key );
		$last_changed = wp_cache_get( 'last_changed', 's-terms' );
		if ( ! $last_changed ) {
			$last_changed = time();
			wp_cache_set( 'last_changed', $last_changed, 's-terms' );
		}
		$cache_key = "get_terms:$key:$last_changed";
		$cache     = wp_cache_get( $cache_key, 's-terms' );
		if ( false !== $cache ) {
			$cache = apply_filters( 'get_terms', $cache, $taxonomies, $args );

			return $cache;
		}

		$_orderby = strtolower( $orderby );
		if ( 'count' == $_orderby ) {
			$orderby = 'tt.count';
		}
		if ( 'random' == $_orderby ) {
			$orderby = 'RAND()';
		} else if ( 'name' == $_orderby ) {
			$orderby = 't.name';
		} else if ( 'slug' == $_orderby ) {
			$orderby = 't.slug';
		} else if ( 'term_group' == $_orderby ) {
			$orderby = 't.term_group';
		} elseif ( empty( $_orderby ) || 'id' == $_orderby ) {
			$orderby = 't.term_id';
		}
		$orderby = apply_filters( 'get_terms_orderby', $orderby, $args );

		if ( ! empty( $orderby ) ) {
			$orderby = "ORDER BY $orderby";
		} else {
			$order = '';
		}

		$where      = '';
		$inclusions = '';
		if ( ! empty( $include ) ) {
			$exclude      = '';
			$exclude_tree = '';
			$interms      = wp_parse_id_list( $include );
			foreach ( $interms as $interm ) {
				if ( empty( $inclusions ) ) {
					$inclusions = ' AND ( t.term_id = ' . intval( $interm ) . ' ';
				} else {
					$inclusions .= ' OR t.term_id = ' . intval( $interm ) . ' ';
				}
			}
		}

		if ( ! empty( $inclusions ) ) {
			$inclusions .= ')';
		}
		$where .= $inclusions;

		$exclusions = '';
		if ( ! empty( $exclude_tree ) ) {
			$excluded_trunks = wp_parse_id_list( $exclude_tree );
			foreach ( $excluded_trunks as $extrunk ) {
				$excluded_children   = (array) get_terms( $taxonomies[0], array(
					'child_of' => intval( $extrunk ),
					'fields'   => 'ids'
				) );
				$excluded_children[] = $extrunk;
				foreach ( $excluded_children as $exterm ) {
					if ( empty( $exclusions ) ) {
						$exclusions = ' AND ( t.term_id <> ' . intval( $exterm ) . ' ';
					} else {
						$exclusions .= ' AND t.term_id <> ' . intval( $exterm ) . ' ';
					}
				}
			}
		}

		if ( ! empty( $exclude ) ) {
			$exterms = wp_parse_id_list( $exclude );
			foreach ( $exterms as $exterm ) {
				if ( empty( $exclusions ) ) {
					$exclusions = ' AND ( t.term_id <> ' . intval( $exterm ) . ' ';
				} else {
					$exclusions .= ' AND t.term_id <> ' . intval( $exterm ) . ' ';
				}
			}
		}

		if ( ! empty( $exclusions ) ) {
			$exclusions .= ')';
		}
		$exclusions = apply_filters( 'list_terms_exclusions', $exclusions, $args );
		$where      .= $exclusions;

		// ST Features : Restrict category
		if (!empty($category)) {
			if ( ! is_array( $taxonomies ) ) {
				$taxonomies = array( $taxonomies );
			}

			$incategories = wp_parse_id_list( $category );

			$taxonomies   = "'" . implode( "', '", $taxonomies ) . "'";
			$incategories = "'" . implode( "', '", $incategories ) . "'";

			$where .= " AND tr.object_id IN ( ";
			$where .= "SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->posts as p ON tr.object_id=p.ID WHERE tt.term_id IN ($incategories) AND p.post_status='publish'";
			$where .= " ) ";
			$join_relation = true;
			unset( $incategories, $category );
		}

		// ST Features : Limit posts date
		if ( (int)$limit_days > 0 ) {
            if($post_type){
                $post_type = "AND post_type = '$post_type'";
            }else{
                $post_type = '';
            }
			$where .= " AND tr.object_id IN ( ";
			$where .= "SELECT DISTINCT ID FROM $wpdb->posts AS p WHERE p.post_date_gmt > '" . date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ) . "' $post_type";
			$where .= " ) ";
			$join_relation = true;
			unset( $limit_days );
		}else{
            if($post_type){
			$where .= " AND tr.object_id IN ( ";
			$where .= "SELECT DISTINCT ID FROM $wpdb->posts AS p WHERE post_type = '$post_type'";
			$where .= " ) ";
			$join_relation = true;
            }
        }

		if ( ! empty( $slug ) ) {
			$slug  = sanitize_title( $slug );
			$where .= " AND t.slug = '$slug'";
		}

		if ( ! empty( $name__like ) ) {
			$where .= " AND t.name LIKE '{$name__like}%'";
		}

		if ( '' !== $parent ) {
			$parent = (int) $parent;
			$where  .= " AND tt.parent = '$parent'";
		}

		// ST Features : Another way to search
		if ( strpos( $st_name__like, ' ' ) !== false ) {

			$st_terms_formatted = array();
			$st_terms           = preg_split( '/[\s,]+/', $st_name_like );
			foreach ( (array) $st_terms as $st_term ) {
				if ( empty( $st_term ) ) {
					continue;
				}
				$st_terms_formatted[] = "t.name LIKE '%" . like_escape( $st_term ) . "%'";
			}

			$where .= " AND ( " . explode( ' OR ', $st_terms_formatted ) . " ) ";
			unset( $st_term, $st_terms_formatted, $st_terms );

		} elseif ( ! empty( $st_name__like ) ) {

			$where .= " AND t.name LIKE '%{$st_name__like}%'";

		}

        if(in_array($taxonomies[0], ['post_tag', 'category'])){
        //TODO - count not working for attachment and/or CPT ?
		// ST Features : Add min usage
		if ( $hide_empty && ! $hierarchical ) {
			if ( $min_usage == 0 ) {
				$where .= ' AND tt.count > 0';
			} else {
				$where .= $wpdb->prepare( ' AND tt.count >= %d', $min_usage );
			}
		}
        }


		if ( ! empty( $search ) ) {
			$search = like_escape( $search );
			$where  .= " AND (t.name LIKE '%$search%')";
		}

		$selects = array();
		switch ( $fields ) {
			case 'all':
				$selects = array( 't.*', 'tt.*' );
				break;
			case 'ids':
			case 'id=>parent':
				$selects = array( 't.term_id', 'tt.parent', 'tt.count' );
				break;
			case 'names':
				$selects = array( 't.term_id', 'tt.parent', 'tt.count', 't.name' );
				break;
			case 'count':
				$orderby = '';
				$order   = '';
				$selects = array( 'COUNT(*)' );
		}
		$select_this = implode( ', ', apply_filters( 'get_terms_fields', $selects, $args ) );

		// Add inner to relation table ?
		$join_relation = $join_relation == false ? '' : "INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
    // Query parts are individually escaped above, $taxonomies are individually checked if they exists

    
		// don't limit the query results when we have to descend the family tree
		if ( ! empty( $number ) && ! $hierarchical && empty( $child_of ) && '' == $parent ) {
			if ( $offset ) {
                $query = $wpdb->prepare("SELECT $select_this
			FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			$join_relation
			WHERE 1 = %d AND tt.taxonomy IN ($in_taxonomies)
			$where
			$orderby $order
            LIMIT %d, %d",
            1,
            $offset,
            $number );
			} else {
                $query = $wpdb->prepare("SELECT $select_this
			FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			$join_relation
			WHERE 1 = %d AND tt.taxonomy IN ($in_taxonomies)
			$where
			$orderby $order
            LIMIT %d",
            1,
            $number );
			}
		} else {
            $query = $wpdb->prepare("SELECT $select_this
			FROM $wpdb->terms AS t
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			$join_relation
			WHERE 1 = %d AND tt.taxonomy IN ($in_taxonomies)
			$where
			$orderby $order",
            1 );
		}

		// GROUP BY t.term_id

		if ( 'count' == $fields ) {
			$term_count = $wpdb->get_var( $query );

			return $term_count;
		}

		$terms = $wpdb->get_results( $query );
		if ( 'all' == $fields ) {
			update_term_cache( $terms );
		}

		if ( empty( $terms ) ) {
			wp_cache_add( $cache_key, array(), 's-terms' );
			$terms = apply_filters( 'get_terms', array(), $taxonomies, $args );

			return $terms;
		}

		if ( $child_of ) {
			$children = _get_term_hierarchy( $taxonomies[0] );
			if ( ! empty( $children ) ) {
				$terms = &_get_term_children( $child_of, $terms, $taxonomies[0] );
			}
		}

		// Update term counts to include children.
		if ( $pad_counts && 'all' == $fields ) {
			_pad_term_counts( $terms, $taxonomies[0] );
		}

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty && is_array( $terms ) ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = _get_term_children( $term->term_id, $terms, $taxonomies[0] );
					if ( is_array( $children ) ) {
						foreach ( $children as $child ) {
							if ( $child->count ) {
								continue 2;
							}
						}
					}

					// It really is empty
					unset( $terms[ $k ] );
				}
			}
		}
		reset( $terms );

		$_terms = array();
		if ( 'id=>parent' == $fields ) {
			while ( $term = array_shift( $terms ) ) {
				$_terms[ $term->term_id ] = $term->parent;
			}
			$terms = $_terms;
		} elseif ( 'ids' == $fields ) {
			while ( $term = array_shift( $terms ) ) {
				$_terms[] = $term->term_id;
			}
			$terms = $_terms;
		} elseif ( 'names' == $fields ) {
			while ( $term = array_shift( $terms ) ) {
				$_terms[] = $term->name;
			}
			$terms = $_terms;
		}

		if ( 0 < $number && intval( @count( $terms ) ) > $number ) {
			$terms = array_slice( $terms, $offset, $number );
		}

		wp_cache_add( $cache_key, $terms, 's-terms' );

		$terms = apply_filters( 'get_terms', $terms, $taxonomies, $args );

		return $terms;
	}
}
