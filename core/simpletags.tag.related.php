<?php
Class SimpleTags_RelatedTags {
	var $options = array();
	
	function SimpleTags_RelatedTags() {
		// Load default options
		$this->options = array(
			// Related tags
			'rt_number' => 5,
			'rt_order' => 'count-desc',
			'rt_separator' => ' ',
			'rt_format' => 'list',
			'rt_method' => 'OR',
			'rt_title' => __('<h4>Related tags</h4>', 'simpletags'),
			'rt_notagstext' => __('No related tags found.', 'simpletags'),
			'rt_xformat' => __('<span>%tag_count%</span> <a href="%tag_link_add%">+</a> <a href="%tag_link%">%tag_name%</a>', 'simpletags'),
			// Remove related tags
			'rt_remove_separator' => ' ',
			'rt_remove_format' => 'list',
			'rt_remove_notagstext' => ' ',
			'rt_remove_xformat' => __('&raquo; <a href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags')
		);
	}
	
	/**
	 * Check is page is a tag view, even if tags haven't post
	 *
	 * @return boolean
	 */
	function isTag() {
		if ( get_query_var('tag') == '' ) {
			return false;
		}
		return true;
	}

	/**
	 * Get related tags for a tags view
	 *
	 * @param string $user_args
	 * @return string|array
	 */
	function relatedTags( $user_args = '' ) {
		$defaults = array(
		'number' => 5,
		'order' => 'count-desc',
		'separator' => ' ',
		'format' => 'list',
		'method' => 'OR',
		'no_follow' => 0,
		'title' => __('<h4>Related tags</h4>', 'simpletags'),
		'notagstext' => __('No related tag found.', 'simpletags'),
		'xformat' => __('<span>%tag_count%</span> <a %tag_rel% href="%tag_link_add%">+</a> <a %tag_rel% href="%tag_link%" title="See posts with %tag_name_attribute%">%tag_name%</a>', 'simpletags')
		);

		// Get values in DB
		$defaults['no_follow'] = $this->options['no_follow'];
		$defaults['number'] = $this->options['rt_number'];
		$defaults['order'] = $this->options['rt_order'];
		$defaults['separator'] = $this->options['rt_separator'];
		$defaults['format'] = $this->options['rt_format'];
		$defaults['method'] = $this->options['rt_method'];
		$defaults['title'] = $this->options['rt_title'];
		$defaults['notagstext'] = $this->options['rt_notagstext'];
		$defaults['xformat'] = $this->options['rt_xformat'];

		if( empty($user_args) ) {
			$user_args = $this->options['rt_adv_usage'];
		}

		$args = wp_parse_args( $user_args, $defaults );
		extract($args);

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Clean memory
		$args = array();
		$defaults = array();
		unset($args, $defaults);

		if ( !is_tag() && !$this->isTag() ) {
			return '';
		}

		// Method union/intersection
		$method = strtoupper($method);
		if ( $method == 'AND' ) {
			$url_tag_sep = '+';
		} else {
			$url_tag_sep = ',';
		}

		// Get currents slugs
		$slugs = get_query_var('tag');
		if ( strpos( $slugs, ',') ) {
			$current_slugs = explode(',', $slugs);
		} elseif ( strpos( $slugs, '+') ) {
			$current_slugs = explode('+', $slugs);
		} elseif ( strpos( $slugs, ' ') ) {
			$current_slugs = explode(' ', $slugs);
		}else {
			$current_slugs[] = $slugs;
		}

		// Get cache if exist
		$related_tags = false;
		global $wp_object_cache;
		if ( $wp_object_cache->cache_enabled === true ) { // Use cache
			// Generate key cache
			$key = md5(maybe_serialize($user_args.$slugs.$url_tag_sep));
			$cache = wp_cache_get( 'related_tags', 'simpletags' );
			if ( $cache ) {
				if ( isset( $cache[$key] ) ) {
					$related_tags = $cache[$key];
				}
			}
		}

		// If cache not exist, get datas and set cache
		if ( $related_tags === false || $related_tags === null ) {
			// Order tags before selection (count-asc/count-desc/name-asc/name-desc/random)
			$order_tmp = strtolower($order);
			$order_by = $order = '';
			switch ( $order_tmp ) {
				case 'count-asc':
					$order_by = 'count';
					$order = 'ASC';
					break;
				case 'random':
					$order_by = 'RAND()';
					$order = '';
					break;
				case 'name-asc':
					$order_by = 'name';
					$order = 'ASC';
					break;
				case 'name-desc':
					$order_by = 'name';
					$order = 'DESC';
					break;
				default: // count-desc
				$order_by = 'count';
				$order = 'DESC';
				break;
			}

			// Get objets
			$terms = "'" . implode("', '", $current_slugs) . "'";
			global $wpdb;
			$object_ids = $wpdb->get_col("
				SELECT tr.object_id
				FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = 'post_tag'
				AND t.slug IN ({$terms})
				GROUP BY tr.object_id
				ORDER BY tr.object_id ASC");

			// Clean memory
			$terms = array();
			unset($terms);

			// Get tags for specified objects
			$all_related_tags = wp_get_object_terms( $object_ids, 'post_tag', array('orderby' => $order_by, 'order' => $order) );

			// Remove duplicates tags
			$all_related_tags = array_intersect_key($all_related_tags, array_unique(array_map('serialize', $all_related_tags)));

			// Exclude current tags
			foreach ( (array) $all_related_tags as $tag ) {
				if ( !in_array($tag->slug, $current_slugs) ) {
					$related_tags[] = $tag;
				}
			}

			// Clean memory
			$all_related_tags = array();
			unset($all_related_tags);

			if ( $wp_object_cache->cache_enabled === true ) { // Use cache
				$cache[$key] = $related_tags;
				wp_cache_set('related_tags', $cache, 'simpletags');
			}
		}

		if ( empty($related_tags) ) {
			return $this->outputContent( 'st-related-tags', $format, $title, $notagstext, true );
		} elseif ( $format == 'object' || $format == 'array' ) {
			return $related_tags;
		}

		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			$related_tags = array_slice( $related_tags, 0, $number );
		}

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $no_follow );

		// Build outpout
		$output = array();
		foreach( $related_tags as $tag ) {
			if ( !is_object($tag) ) {
				continue;
			}

			$element_loop = $xformat;
			$element_loop = $this->formatInternalTag( $element_loop, $tag, $rel, null );
			$element_loop = str_replace('%tag_link_add%', $this->getAddTagToLink( $current_slugs, $tag->slug, $url_tag_sep ), $element_loop);
			$output[] = $element_loop;
		}

		// Clean memory
		$related_tags = array();
		unset($related_tags, $tag, $element_loop);

		return $this->outputContent( 'st-related-tags', $format, $title, $output, true, $separator );
	}

	/**
	 * Add a tag to a current link
	 *
	 * @param array $current_slugs
	 * @param string $tag_slug
	 * @param string $separator
	 * @return string
	 */
	function getAddTagToLink( $current_slugs = array(), $tag_slug = '', $separator = ',' ) {
		// Add new tag slug to current slug
		$current_slugs[] = $tag_slug;

		// Array to string with good separator
		$slugs = implode( $separator, $current_slugs );

		global $wp_rewrite;
		$taglink = $wp_rewrite->get_tag_permastruct();

		if ( empty($taglink) ) { // No permalink
			$taglink = $this->info['home'] . '/?tag=' . $slugs;
		} else { // Custom permalink
			$taglink = $this->info['home'] . user_trailingslashit( str_replace('%tag%', $slugs, $taglink), 'category');
		}

		return apply_filters('st_add_tag_link', clean_url($taglink));
	}

	/**
	 * Get tags to remove in related tags
	 *
	 * @param string $user_args
	 * @return string|array
	 */
	function removeRelatedTags( $user_args = '' ) {
		$defaults = array(
		'separator' => '<br />',
		'format' => 'list',
		'notagstext' => '',
		'no_follow' => 0,
		'xformat' => __('<a %tag_rel% href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags')
		);

		// Get values in DB
		$defaults['no_follow'] = $this->options['no_follow'];
		$defaults['separator'] = $this->options['rt_remove_separator'];
		$defaults['format'] = $this->options['rt_remove_format'];
		$defaults['notagstext'] = $this->options['rt_remove_notagstext'];
		$defaults['xformat'] = $this->options['rt_remove_xformat'];

		if( empty($user_args) ) {
			$user_args = $this->options['rt_remove_adv_usage'];
		}

		$args = wp_parse_args( $user_args, $defaults );
		extract($args);

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Clean memory
		$args = array();
		$defaults = array();
		unset($args, $defaults, $user_args);

		if ( !is_tag() && !$this->isTag() ) {
			return '';
		}

		// Get currents slugs
		$slugs = get_query_var('tag');
		if ( strpos( $slugs, ',') ) {
			$current_slugs = explode(',', $slugs);
			$url_tag_sep = ',';
		} elseif ( strpos( $slugs, '+') ) {
			$current_slugs = explode('+', $slugs);
			$url_tag_sep = '+';
		} elseif ( strpos( $slugs, ' ') ) {
			$current_slugs = explode(' ', $slugs);
			$url_tag_sep = '+';
		} else {
			return $this->outputContent( 'st-remove-related-tags', $format, '', $notagstext, true );
		}

		if ( $format == 'array' || $format == 'object' ) {
			return $current_slugs;
		}

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $no_follow );

		foreach ( $current_slugs as $slug ) {
			// Get term by slug
			$term = get_term_by('slug', $slug, 'post_tag');
			if ( !is_object($term) ) {
				continue;
			}

			$element_loop = $xformat;
			$element_loop = $this->formatInternalTag( $element_loop, $term, $rel, null );
			// Specific marker
			$element_loop = str_replace('%tag_link_remove%', $this->getRemoveTagToLink( $current_slugs, $term->slug, $url_tag_sep ), $element_loop);
			$output[] = $element_loop;
		}

		// Clean memory
		$current_slugs = array();
		unset($current_slugs, $slug, $element_loop);

		return $this->outputContent( 'st-remove-related-tags', $format, '', $output );
	}

	/**
	 * Build tag url without a specifik tag
	 *
	 * @param array $current_slugs
	 * @param string $tag_slug
	 * @param string $separator
	 * @return string
	 */
	function getRemoveTagToLink( $current_slugs = array(), $tag_slug = '', $separator = ',' ) {
		// Remove tag slug to current slugs
		$key = array_search($tag_slug, $current_slugs);
		unset($current_slugs[$key]);

		// Array to string with good separator
		$slugs = implode( $separator, $current_slugs );

		global $wp_rewrite;
		$taglink = $wp_rewrite->get_tag_permastruct();

		if ( empty($taglink) ) { // No permalink
			$taglink = $this->info['home'] . '/?tag=' . $slugs;
		} else { // Custom permalink
			$taglink = $this->info['home'] . user_trailingslashit( str_replace('%tag%', $slugs, $taglink), 'category');
		}
		return apply_filters('st_remove_tag_link', clean_url($taglink));
	}
	
}
?>