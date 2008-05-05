<?php
// Option: tt_embedded, tt_feed
Class SimpleTags_RelatedPosts {
	var $options = array();
	
	function SimpleTags_RelatedPosts() {
		// Load default options
		$this->options = array(
			'rp_feed' => 0,
			'rp_embedded' => 'no',
			'rp_order' => 'count-desc',
			'rp_limit_qty' => 5,
			'rp_notagstext' => __('No related posts.', 'simpletags'),
			'rp_title' => __('<h4>Related posts</h4>', 'simpletags'),
			'rp_xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags'),
			'rp_adv_usage' => ''
		);
		
		// Add related posts in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $this->options['rp_embedded'] != 'no' || $this->options['rp_feed'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlineRelatedPosts'), 999993);
		}
	}
	
	/**
	 * Auto add related posts to post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlineRelatedPosts( $content = '' ) {
		$marker = false;
		if ( is_feed() ) {
			if ( $this->options['rp_feed'] == '1' ) {
				$marker = true;
			}
		} else {
			switch ( $this->options['rp_embedded'] ) {
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
			return ( $content . $this->relatedPosts( '', false ) );
		}
		return $content;
	}
	
	/**
	 * Generate related posts
	 *
	 * @param string $user_args
	 * @return string|array
	 */
	function relatedPosts( $user_args = '', $copyright = true ) {
		$defaults = array(
		'number' => 5,
		'order' => 'count-desc',
		'format' => 'list',
		'separator' => '',
		'include_page' => 'true',
		'include_cat' => '',
		'exclude_posts' => '',
		'exclude_tags' => '',
		'post_id' => 0,
		'excerpt_wrap' => 55,
		'limit_days' => 0,
		'min_shared' => 1,
		'title' => __('<h4>Related posts</h4>', 'simpletags'),
		'nopoststext' => __('No related posts.', 'simpletags'),
		'dateformat' => get_option('date_format'),
		'xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags')
		);

		// Get values in DB
		$defaults['number'] = $this->options['rp_limit_qty'];
		$defaults['order'] = $this->options['rp_order'];
		$defaults['nopoststext'] = $this->options['rp_notagstext'];
		$defaults['title'] = $this->options['rp_title'];
		$defaults['xformat'] = $this->options['rp_xformat'];

		if( empty($user_args) ) {
			$user_args = $this->options['rp_adv_usage'];
		}

		// Replace old markers by new
		$markers = array('%date%' => '%post_date%', '%permalink%' => '%post_permalink%', '%title%' => '%post_title%', '%commentcount%' => '%post_comment%', '%tagcount%' => '%post_tagcount%', '%postid%' => '%post_id%');
		$user_args = strtr($user_args, $markers);

		$args = wp_parse_args( $user_args, $defaults );
		extract($args);

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Clean memory
		$args = array();
		$defaults = array();

		// Get current post data
		$object_id = (int) $post_id;
		if ( $object_id == 0 ) {
			global $post;
			$object_id = (int) $post->ID;
			if ( $object_id == 0 ) {
				return false;
			}
		}

		// Get cache if exist
		$results = false;
		global $wp_object_cache;
		if ( $wp_object_cache->cache_enabled === true ) { // Use cache
			// Generate key cache
			$key = md5(maybe_serialize($user_args.'-'.$object_id));

			if ( $cache = wp_cache_get( 'related_posts', 'simpletags' ) ) {
				if ( isset( $cache[$key] ) ) {
					$results = $cache[$key];
				}
			}
		}

		// If cache not exist, get datas and set cache
		if ( $results === false || $results === null ) {
			// Get get tags
			$current_tags = get_the_tags( (int) $object_id );

			if ( $current_tags === false ) {
				return $this->outputContent( 'st-related-posts', $format, $title, $nopoststext, $copyright );
			}

			// Number - Limit
			$number = (int) $number;
			if ( $number == 0 ) {
				$number = 5;
			} elseif( $number > 50 ) {
				$number = 50;
			}
			$limit_sql = 'LIMIT 0, '.$number;
			unset($number);

			// Order tags before output (count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random)
			$order_by = '';
			$order = strtolower($order);
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
			$limit_days = (int) $limit_days;
			$limit_days_sql = '';
			if ( $limit_days != 0 ) {
				$limit_days_sql = 'AND p.post_date > "' .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). '"';
			}
			unset($limit_days);

			// Include_page
			$include_page = strtolower($include_page);
			if ( $include_page == 'true' ) {
				$restrict_sql = "AND p.post_type IN ('page', 'post')";
			} else {
				$restrict_sql = "AND p.post_type = 'post'";
			}
			unset($include_page);

			// Restrict posts
			$exclude_posts_sql = '';
			if ( $exclude_posts != '' ) {
				$exclude_posts = (array) explode(',', $exclude_posts);
				$exclude_posts = array_unique($exclude_posts);
				$exclude_posts_sql = "AND p.ID NOT IN (";
				foreach ( $exclude_posts as $value ) {
					$value = (int) $value;
					if( $value > 0 && $value != $object_id ) {
						$exclude_posts_sql .= '"'.$value.'", ';
					}
				}
				$exclude_posts_sql .= '"'.$object_id.'")';
			} else {
				$exclude_posts_sql = "AND p.ID <> {$object_id}";
			}
			unset($exclude_posts);

			// Restricts tags
			$tags_to_exclude = array();
			if ( $exclude_tags != '' ) {
				$exclude_tags = (array) explode(',', $exclude_tags);
				$exclude_tags = array_unique($exclude_tags);
				foreach ( $exclude_tags as $value ) {
					$tags_to_exclude[] = trim($value);
				}
			}
			unset($exclude_tags);

			// SQL Tags list
			$tag_list = '';
			foreach ( (array) $current_tags as $tag ) {
				if ( !in_array($tag->name, $tags_to_exclude) ) {
					$tag_list .= '"'.(int) $tag->term_id.'", ';
				}
			}

			// If empty return no posts text
			if ( empty($tag_list) ) {
				return $this->outputContent( 'st-related-posts', $format, $title, $nopoststext, $copyright );
			}

			// Remove latest ", "
			$tag_list = substr($tag_list, 0, strlen($tag_list) - 2);

			global $wpdb;

			// Include category
			$include_cat_sql = '';
			$inner_cat_sql = '';
			if ($include_cat != '') {
				$include_cat = (array) explode(',', $include_cat);
				$include_cat = array_unique($include_cat);
				foreach ( $include_cat as $value ) {
					$value = (int) $value;
					if( $value > 0 ) {
						$sql_cat_in .= '"'.$value.'", ';
					}
				}
				$sql_cat_in = substr($sql_cat_in, 0, strlen($sql_cat_in) - 2);
				$include_cat_sql = " AND (ctt.taxonomy = 'category' AND ctt.term_id IN ({$sql_cat_in})) ";
				$inner_cat_sql = " INNER JOIN {$wpdb->term_relationships} AS ctr ON (p.ID = ctr.object_id) ";
				$inner_cat_sql .= " INNER JOIN {$wpdb->term_taxonomy} AS ctt ON (ctr.term_taxonomy_id = ctt.term_taxonomy_id) ";
			}

			// Group Concat only for MySQL > 4.1 and check if post_relatedtags is used by xformat...
			$select_gp_concat = '';
			if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') && ( strpos($xformat,'%post_relatedtags%') || $min_shared > 1 ) ) {
				$select_gp_concat = ', GROUP_CONCAT(tt.term_id) as terms_id';
			} else {
				$xformat = str_replace('%post_relatedtags%', '', $xformat); // Group Concat only for MySQL > 4.1, remove related tags
			}

			// Check if post_excerpt is used by xformat...
			$select_excerpt = '';
			if ( strpos( $xformat, '%post_excerpt%' ) ) {
				$select_excerpt = ', p.post_content, p.post_excerpt, p.post_password';
			}

			// Posts: title, comments_count, date, permalink, post_id, counter
			$results = $wpdb->get_results("
				SELECT p.post_title, p.comment_count, p.post_date, p.ID, COUNT(tr.object_id) AS counter {$select_excerpt} {$select_gp_concat}
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				{$inner_cat_sql}
				WHERE (tt.taxonomy = 'post_tag' AND tt.term_id IN ({$tag_list}))
				{$include_cat_sql}
				{$exclude_posts_sql}
				AND p.post_status = 'publish'
				AND p.post_date_gmt < '".current_time('mysql')."'
				{$limit_days_sql}
				{$restrict_sql}
				GROUP BY tr.object_id
				ORDER BY {$order_by}
				{$limit_sql}");

			if ( $wp_object_cache->cache_enabled === true ) { // Use cache
				$cache[$key] = $results;
				wp_cache_set('related_posts', $cache, 'simpletags');
			}
		}

		if ( $format == 'object' || $format == 'array' ) {
			return $results;
		} elseif ( $results === false || empty($results) ) {
			return $this->outputContent( 'st-related-posts', $format, $title, $nopoststext, $copyright );
		}

		if ( empty($dateformat) ) {
			$dateformat = get_option('date_format');
		}

		$output = array();
		// Replace placeholders
		foreach ( (array) $results as $result ) {
			if ( ( $min_shared > 1 && ( count(explode(',', $result->terms_id)) < $min_shared ) ) || !is_object($result) ) {
				continue;
			}

			$element_loop = $xformat;
			$post_title = apply_filters( 'the_title', $result->post_title );
			$element_loop = str_replace('%post_date%', mysql2date($dateformat, $result->post_date), $element_loop);
			$element_loop = str_replace('%post_permalink%', get_permalink($result->ID), $element_loop);
			$element_loop = str_replace('%post_title%', $post_title, $element_loop);
			$element_loop = str_replace('%post_title_attribute%', wp_specialchars(strip_tags($post_title)), $element_loop);
			$element_loop = str_replace('%post_comment%', $result->comment_count, $element_loop);
			$element_loop = str_replace('%post_tagcount%', $result->counter, $element_loop);
			$element_loop = str_replace('%post_id%', $result->ID, $element_loop);
			$element_loop = str_replace('%post_relatedtags%', $this->getTagsFromID($result->terms_id), $element_loop);
			$element_loop = str_replace('%post_excerpt%', $this->getExcerptPost( $result->post_excerpt, $result->post_content, $result->post_password, $excerpt_wrap ), $element_loop);
			$output[] = $element_loop;
		}

		// Clean memory
		$results = array();
		unset($results, $result);

		return $this->outputContent( 'st-related-posts', $format, $title, $output, $copyright, $separator );
	}

	/**
	 * Build excerpt from post data with specific lenght
	 *
	 * @param string $excerpt
	 * @param string $content
	 * @param string $password
	 * @param integer $excerpt_length
	 * @return string
	 */
	function getExcerptPost( $excerpt = '', $content = '', $password = '', $excerpt_length = 55 ) {
		if ( !empty($password) ) { // if there's a password
			if ( $_COOKIE['wp-postpass_'.COOKIEHASH] != $password ) { // and it doesn't match the cookie
				return __('There is no excerpt because this is a protected post.', 'simpletags');
			}
		}

		if ( !empty($excerpt) ) {
			return apply_filters('get_the_excerpt', $excerpt);
		} else { // Fake excerpt
			$content = str_replace(']]>', ']]&gt;', $content);
			$content = strip_tags($content);

			$excerpt_length = (int) $excerpt_length;
			if ( $excerpt_length == 0 ) {
				$excerpt_length = 55;
			}

			$words = explode(' ', $content, $excerpt_length + 1);
			if ( count($words) > $excerpt_length ) {
				array_pop($words);
				array_push($words, '[...]');
				$content = implode(' ', $words);
			}
			return $content;
		}
	}

	/**
	 * Get and format tags from list ID (SQL Group Concat)
	 *
	 * @param array $terms
	 * @return string
	 */
	function getTagsFromID( $terms = '' ) {
		if ( empty($terms) ) {
			return '';
		}

		// Get tags since Term ID.
		$terms = (array) get_terms('post_tag', 'include='.$terms);
		if ( empty($terms) ) {
			return '';
		}

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $this->options['no_follow'] );

		$output = '';
		foreach ( $terms as $term ) {
			$output .= '<a href="'.get_tag_link($term->term_id).'" title="'.attribute_escape(sprintf( __ngettext('%d topic', '%d topics', $term->count, 'simpletags'), $term->count )).'" '.$rel.'>'.wp_specialchars($term->name).'</a>, ';
		}
		$output = substr($output, 0, strlen($output) - 2); // Remove latest ", "
		return $output;
	}
	
}
?>