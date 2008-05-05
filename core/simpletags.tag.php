<?php
Class SimpleTags_Tag {
	/**
	 * Remplace marker by dynamic values (use for related tags, current tags and tag cloud)
	 *
	 * @param string $element_loop
	 * @param object $term
	 * @param string $rel
	 * @param integer $scale_result
	 * @param integer $scale_max
	 * @param integer $scale_min
	 * @param integer $largest
	 * @param integer $smallest
	 * @param string $unit
	 * @param string $maxcolor
	 * @param string $mincolor
	 * @return string
	 */
	function formatInternalTag( $element_loop = '', $term = null, $rel = '', $scale_result = 0, $scale_max = null, $scale_min = 0, $largest = 0, $smallest = 0, $unit = '', $maxcolor = '', $mincolor = '' ) {
		// Need term object
		if ( $term->taxonomy == 'post_tag' ) { // Tag post
			$element_loop = str_replace('%tag_link%', clean_url(get_tag_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($term->term_id)), $element_loop);
		} else { // Category
			$element_loop = str_replace('%tag_link%', clean_url(get_category_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_category_rss_link(false, $term->term_id, '')), $element_loop);
		}
		$element_loop = str_replace('%tag_name%', wp_specialchars( $term->name ), $element_loop);
		$element_loop = str_replace('%tag_name_attribute%', wp_specialchars(strip_tags($term->name)), $element_loop);
		$element_loop = str_replace('%tag_id%', $term->term_id, $element_loop);
		$element_loop = str_replace('%tag_count%', (int) $term->count, $element_loop);

		// Need rel
		$element_loop = str_replace('%tag_rel%', $rel, $element_loop);

		// Need max/min/scale and other :)
		if ( $scale_result !== null ) {
			$element_loop = str_replace('%tag_size%', 'font-size:'.round(($scale_result - $scale_min)*($largest-$smallest)/($scale_max - $scale_min) + $smallest, 2).$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%', 'color:'.$this->getColorByScale(round(($scale_result - $scale_min)*(100)/($scale_max - $scale_min), 2),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_scale%', $scale_result, $element_loop);
		}

		// External link
		$element_loop = str_replace('%tag_technorati%', $this->formatExternalTag( 'technorati', $term->name ), $element_loop);
		$element_loop = str_replace('%tag_flickr%', $this->formatExternalTag( 'flickr', $term->name ), $element_loop);
		$element_loop = str_replace('%tag_delicious%', $this->formatExternalTag( 'delicious', $term->name ), $element_loop);

		return $element_loop;
	}
	
	/**
	 * Format nice URL depending service
	 *
	 * @param string $type
	 * @param string $tag_name
	 * @return string
	 */
	function formatExternalTag( $type = '', $term_name = '' ) {
		if ( empty($term_name) ) {
			return '';
		}

		$term_name = wp_specialchars($term_name);
		switch ( $type ) {
			case 'technorati':
				$link = clean_url('http://technorati.com/tag/'.str_replace(' ', '+', $term_name));
				return '<a class="tag_technorati" href="'.$link.'" rel="tag">'.$term_name.'</a>';
				break;
			case 'flickr':
				$link = clean_url('http://www.flickr.com/photos/tags/'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($term_name)).'/');
				return '<a class="tag_flickr" href="'.$link.'" rel="tag">'.$term_name.'</a>';
				break;
			case 'delicious':
				$link = clean_url('http://del.icio.us/popular/'.strtolower(str_replace(' ', '', $term_name)));
				return '<a class="tag_delicious" href="'.$link.'" rel="tag">'.$term_name.'</a>';
				break;
			default:
				return '';
				break;
		}
	}
	
	/**
	 * Build rel for tag link
	 *
	 * @param integer $no_follow
	 * @return string
	 */
	function buildRel( $no_follow = 1 ) {
		$rel = '';

		global $wp_rewrite;
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?

		$no_follow = (int) $no_follow;
		if ( $no_follow == 1 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';
		}

		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}

		return $rel;
	}
	

	
	/**
	 * Extended get_tags function that use getTerms function
	 *
	 * @param string $args
	 * @return array
	 */
	function getTags( $args = '', $skip_cache = false, $taxonomy = 'post_tag' ) {
		$key = md5(serialize($args));

		if ( $skip_cache == true ) {
			$terms = $this->getTerms( $taxonomy, $args, $skip_cache );
		} else {
			// Get cache if exist
			if ( $cache = wp_cache_get( 'st_get_tags', 'simpletags' ) ) {
				if ( isset( $cache[$key] ) ) {
					return apply_filters('get_tags', $cache[$key], $args);
				}
			}

			// Get tags
			$terms = $this->getTerms( $taxonomy, $args, $skip_cache );
			if ( empty($terms) ) {
				return array();
			}

			$cache[$key] = $terms;
			wp_cache_set( 'st_get_tags', $cache, 'simpletags' );
		}

		$terms = apply_filters('get_tags', $terms, $args);
		return $terms;
	}

	/**
	 * Extended get_terms function support
	 * - Limit category
	 * - Limit days
	 * - Selection restrict
	 * - Min usage
	 *
	 * @param string|array $taxonomies
	 * @param string $args
	 * @return array
	 */
	function getTerms( $taxonomies, $args = '', $skip_cache = false ) {
		global $wpdb;
		$empty_array = array();

		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! is_taxonomy($taxonomy) )
			return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
		}

		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";

		$defaults = array(
			'orderby' => 'name',
			'order' => 'ASC',
			'cloud_selection' => 'count-desc',
			'hide_empty' => true,
			'exclude' => '',
			'include' => '',
			'number' => '',
			'fields' => 'all',
			'slug' => '',
			'parent' => '',
			'hierarchical' => true,
			'child_of' => 0,
			'get' => '',
			'name__like' => '',
			'st_name_like' => '',
			'pad_counts' => false,
			'offset' => '',
			'search' => '',
			'limit_days' => 0,
			'category' => 0,
			'min_usage' => 0
		);

		$args = wp_parse_args( $args, $defaults );
		$args['number'] = absint( $args['number'] );
		$args['offset'] = absint( $args['offset'] );
		if ( !$single_taxonomy || !is_taxonomy_hierarchical($taxonomies[0]) || '' != $args['parent'] ) {
			$args['child_of'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}

		if ( 'all' == $args['get'] ) {
			$args['child_of'] = 0;
			$args['hide_empty'] = 0;
			$args['hierarchical'] = false;
			$args['pad_counts'] = false;
		}
		extract($args, EXTR_SKIP);

		if ( $child_of ) {
			$hierarchy = _get_term_hierarchy($taxonomies[0]);
			if ( !isset($hierarchy[$child_of]) )
			return $empty_array;
		}

		if ( $parent ) {
			$hierarchy = _get_term_hierarchy($taxonomies[0]);
			if ( !isset($hierarchy[$parent]) )
			return $empty_array;
		}

		if ( $skip_cache != true ) {
			// Get cache if exist
			$key = md5( serialize( $args ) . serialize( $taxonomies ) );
			if ( $cache = wp_cache_get( 'get_terms', 'terms' ) ) {
				if ( isset( $cache[$key] ) )
				return apply_filters('get_terms', $cache[$key], $taxonomies, $args);
			}
		}

		// Restrict category
		$category_sql = '';
		if ( !empty($category) && $category != '0' ) {
			$incategories = preg_split('/[\s,]+/', $category);

			$objects_id = get_objects_in_term( $incategories, 'category' );
			$objects_id = array_unique ($objects_id); // to be sure haven't duplicates

			if ( empty($objects_id) ) { // No posts for this category = no tags for this category
				return array();
			}

			foreach ( (array) $objects_id as $object_id ) {
				$category_sql .= "'". $object_id . "', ";
			}

			$category_sql = substr($category_sql, 0, strlen($category_sql) - 2); // Remove latest ", "
			$category_sql = 'AND p.ID IN ('.$category_sql.')';
		}

		// count-asc/count-desc/name-asc/name-desc/random
		$cloud_selection = strtolower($cloud_selection);
		switch ( $cloud_selection ) {
			case 'count-asc':
				$order_by = 'tt.count ASC';
				break;
			case 'random':
				$order_by = 'RAND()';
				break;
			case 'name-asc':
				$order_by = 't.name ASC';
				break;
			case 'name-desc':
				$order_by = 't.name DESC';
				break;
			default: // count-desc
			$order_by = 'tt.count DESC';
			break;
		}

		// Min usage
		$restict_usage = '';
		$min_usage = (int) $min_usage;
		if ( $min_usage != 0 ) {
			$restict_usage = ' AND tt.count >= '. $min_usage;
		}

		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$interms = preg_split('/[\s,]+/',$include);
			foreach ( (array) $interms as $interm ) {
				if (empty($inclusions)) {
					$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
				} else {
					$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
				}
			}
		}

		if ( !empty($inclusions) ) {
			$inclusions .= ')';
		}
		$where .= $inclusions;

		$exclusions = '';
		if ( !empty($exclude) ) {
			$exterms = preg_split('/[\s,]+/',$exclude);
			foreach ( (array) $exterms as $exterm ) {
				if (empty($exclusions)) {
					$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
				} else {
					$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
				}
			}
		}

		if ( !empty($exclusions) ) {
			$exclusions .= ')';
		}
		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args );
		$where .= $exclusions;

		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}

		if ( !empty($name__like) ) {
			$where .= " AND t.name LIKE '{$name__like}%'";
		}

		if ( strpos($st_name_like, ' ') != false || strpos($st_name_like, ' ') != null ) {
			$tmp = '';
			$sts = explode(' ', $st_name_like);
			foreach ( (array) $sts as $st ) {
				if ( empty($st) )
				continue;

				$st = addslashes_gpc($st);
				$tmp .= " t.name LIKE '%{$st}%' OR ";
			}
			// Remove latest OR
			$tmp = substr( $tmp, 0, strlen($tmp) - 4);

			$where .= " AND ( $tmp ) ";
			unset($tmp)	;
		} elseif ( !empty($st_name_like) ) {
			$where .= " AND t.name LIKE '%{$st_name_like}%'";
		}

		if ( '' != $parent ) {
			$parent = (int) $parent;
			$where .= " AND tt.parent = '$parent'";
		}

		if ( $hide_empty && !$hierarchical ) {
			$where .= ' AND tt.count > 0';
		}

		$number_sql = '';
		if ( strpos($number, ',') != false || strpos($number, ',') != null ) {
			$number_sql = $number;
		} else {
			$number = (int) $number;
			if ( $number != 0 ) {
				$number_sql = 'LIMIT ' . $number;
			}
		}

		if ( !empty($search) ) {
			$search = like_escape($search);
			$where .= " AND (t.name LIKE '%$search%')";
		}

		$select_this = '';

		if ( 'all' == $fields ) {
			$select_this = 't.*, tt.*';
		} else if ( 'ids' == $fields ) {
			$select_this = 't.term_id';
		} else if ( 'names' == $fields ) {
			$select_this = 't.name';
		}

		// Limit posts date
		$limitdays_sql = '';
		$limit_days = (int) $limit_days;
		if ( $limit_days != 0 ) {
			$limitdays_sql = 'AND p.post_date_gmt > "' .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). '"';
		}

		$query = "SELECT {$select_this}
			FROM {$wpdb->terms} AS t
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
			INNER JOIN {$wpdb->term_relationships} AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
			INNER JOIN {$wpdb->posts} AS p ON tr.object_id = p.ID
			WHERE tt.taxonomy IN ( {$in_taxonomies} )
			AND p.post_date_gmt < '".current_time('mysql')."'
			{$limitdays_sql}
			{$category_sql}
			{$where}
			{$restict_usage}
			GROUP BY t.term_id
			ORDER BY {$order_by}
			{$number_sql}";

		if ( 'all' == $fields ) {
			$terms = $wpdb->get_results($query);
			if ( $skip_cache != true ) {
				update_term_cache($terms);
			}
		} else if ( ('ids' == $fields) || ('names' == $fields) ) {
			$terms = $wpdb->get_col($query);
		}

		if ( empty($terms) ) {
			$cache[ $key ] = array();
			wp_cache_set( 'get_terms', $cache, 'terms' );
			return apply_filters('get_terms', array(), $taxonomies, $args);
		}

		if ( $child_of || $hierarchical ) {
			$children = _get_term_hierarchy($taxonomies[0]);
			if ( ! empty($children) ) {
				$terms = & _get_term_children($child_of, $terms, $taxonomies[0]);
			}
		}

		// Update term counts to include children.
		if ( $pad_counts )
		_pad_term_counts($terms, $taxonomies[0]);

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = _get_term_children($term->term_id, $terms, $taxonomies[0]);
					foreach ( $children as $child )
					if ( $child->count )
					continue 2;

					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset($terms);

		if ( $skip_cache != true ) {
			$cache[$key] = $terms;
			wp_cache_set( 'get_terms', $cache, 'terms' );
		}

		$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
		return $terms;
	}
	
	/**
	 * Format data for output
	 *
	 * @param string $html_class
	 * @param string $format
	 * @param string $title
	 * @param string $content
	 * @param boolean $copyright
	 * @param string $separator
	 * @return string|array
	 */
	function outputContent( $html_class= '', $format = 'list', $title = '', $content = '', $copyright = true, $separator = '' ) {
		if ( empty($content) ) {
			return ''; // return nothing
		}

		if ( $format == 'array' && is_array($content) ) {
			return $content; // Return PHP array if format is array
		}

		if ( is_array($content) ) {
			switch ( $format ) {
				case 'list' :
					$output = "<ul class='{$html_class}'>\n\t<li>";
					$output .= implode("</li>\n\t<li>", $content);
					$output .= "</li>\n</ul>\n";
					break;
				default :
					$output = "<div class='{$html_class}'>\n\t";
					$output .= implode("{$separator}\n", $content);
					$output .= "</div>\n";
					break;
			}
		} else {
			$content = trim($content);
			switch ( $format ) {
				case 'string' :
					$output = $content;
					break;
				case 'list' :
					$output = "<ul class='{$html_class}'>\n\t";
					$output .= '<li>'.$content."</li>\n\t";
					$output .= "</ul>\n";
					break;
				default :
					$output = "<div class='{$html_class}'>\n\t";
					$output .= $content;
					$output .= "</div>\n";
					break;
			}
		}

		// Replace false by empty
		$title = trim($title);
		if ( strtolower($title) == 'false' ) {
			$title = '';
		}

		// Put title if exist
		if ( !empty($title) ) {
			$title .= "\n\t";
		}

		if ( $copyright === true )
		return "\n" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". $title . $output. "\n";
		else
		return "\n\t". $title . $output. "\n";
	}
	
	
}
?>