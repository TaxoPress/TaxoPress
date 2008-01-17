<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://wordpress.org/extend/plugins/simple-tags
Description: Simple Tags : Extended Tagging for WordPress 2.3. Autocompletion, Suggested Tags, Tag Cloud Widgets, Related Posts, Mass edit tags !
Version: 1.3.1
Author: Amaury BALMER
Author URI: http://www.herewithme.fr

Copyright 2007 Amaury BALMER (balmer.amaury@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Contributors:
- Kevin Drouvin (kevin.drouvin@gmail.com - http://inside-dev.net)
- Martin Modler (modler@webformatik.com - http://www.webformatik.com)
*/

Class SimpleTags {
	var $version = '1.3';

	var $info;
	var $options;
	var $default_options;
	var $db_options = 'simpletags';
	var $dateformat;

	// Stock Post ID for current view
	var $posts = array();
	var $tags_currentposts = array();
	var $link_tags = 'null';

	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {
		// Options
		$default_options = array(
			// General
			'inc_page_tag_search' => '1',
			'allow_embed_tcloud' => '0',
			'auto_link_tags' => '0',
			'auto_link_min' => '1',
			'no_follow' => 0,
			// Administration
			'use_tag_pages' => '1',
			'use_click_tags' => '1',
			'use_suggested_tags' => '1',
			// Embedded Tags			
			'use_embed_tags' => '0',
			'start_embed_tags' => '[tags]',
			'end_embed_tags' => '[/tags]',
			// Related Posts
			'rp_feed' => '0',
			'rp_embedded' => 'no',
			'rp_order' => 'count-desc',
			'rp_limit_qty' => '5',
			'rp_notagstext' => __('No related posts.', 'simpletags'),
			'rp_title' => __('<h4>Related posts</h4>', 'simpletags'),
			'rp_xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags'),
			'rp_adv_usage' => '',
			// Tag cloud
			'cloud_selection' => 'count-desc',
			'cloud_sort' => 'random',
			'cloud_limit_qty' => '45',
			'cloud_notagstext' => __('No tags.', 'simpletags'),
			'cloud_title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'cloud_format' => 'flat',
			'cloud_xformat' => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'cloud_max_color' => '#000000',
			'cloud_min_color' => '#CCCCCC',
			'cloud_max_size' => '22',
			'cloud_min_size' => '8',
			'cloud_unit' => 'pt',
			'cloud_adv_usage' => '',
			// The tags
			'tt_feed' => '0',
			'tt_embedded' => 'no',
			'tt_separator' => ', ',
			'tt_before' => __('Tags: ', 'simpletags'),
			'tt_after' => '<br />',
			'tt_notagstext' => __('No tag for this post.', 'simpletags'),
			'tt_number' => '0',
			'tt_xformat' => __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'tt_adv_usage' => '',
			// Related tags
			'rt_number' => 5,
			'rt_order' => 'count-desc',
			'rt_separator' => ' ',
			'rt_format' => 'list',
			'rt_method' => 'OR',
			'rt_title' => __('<h4>Related tags</h4>', 'simpletags'),
			'rt_notagstext' => __('No related tag found.', 'simpletags'),
			'rt_xformat' => __('<span>%tag_count%</span> <a href="%tag_link_add%">+</a> <a href="%tag_link%">%tag_name%</a>', 'simpletags'),
			// Remove related tags
			'rt_remove_separator' => ' ',
			'rt_remove_format' => 'list',
			'rt_remove_notagstext' => ' ',
			'rt_remove_xformat' => __('&raquo; <a href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags'),
			// Meta keywords
			'meta_autoheader' => '1',
			'meta_always_include' => '',
			// Auto tags
			'use_auto_tags' => '0',
			'auto_list' => ''
		);			

		// Set class property for default options
		$this->default_options = $default_options;

		// Get options from WP options
		$options_from_table = get_option( $this->db_options );
		if ( !$options_from_table ) {
			$this->resetToDefaultOptions();
		}

		// Update default options by getting not empty values from options table
		foreach( (array) $default_options as $default_options_name => $default_options_value ) {
			if ( !is_null($options_from_table[$default_options_name]) ) {				
				$default_options[$default_options_name] = $options_from_table[$default_options_name];
			}
		}

		// Set the class property and unset no used variable
		$this->options = $default_options;
		unset($default_options);
		unset($options_from_table);

		// Determine installation path & url
		$path = basename(str_replace('\\','/',dirname(__FILE__)));

		$info['siteurl'] = get_option('siteurl');
		if ( strpos($path, 'mu-plugins') ) {
			$info['install_url'] = $info['siteurl'] . '/wp-content/mu-plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/mu-plugins';

			if ( $path != 'mu-plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		} else {
			$info['install_url'] = $info['siteurl'] . '/wp-content/plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/plugins';

			if ( $path != 'plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		}

		// Set informations
		$this->info = array(
			'home' => get_option('home'),
			'siteurl' => $info['siteurl'],
			'install_url' => $info['install_url'],
			'install_dir' => $info['install_dir']
		);
		unset($info);

		// Set date for class
		$this->dateformat = get_option('date_format');

		// Localization.
		$locale = get_locale();
		if ( !empty( $locale ) ) {
			$mofile = $this->info['install_dir'].'/languages/simpletags-'.$locale.'.mo';
			load_textdomain('simpletags', $mofile);
		}

		// Add pages in WP_Query
		if ( $this->options['use_tag_pages'] == '1' ) {
			// Remove default taxonomy
			global $wp_taxonomies;
			unset($wp_taxonomies['post_tag']);
			// Add the same taxonomy with an another callback who allow page and post
			register_taxonomy( 'post_tag', 'post', array('hierarchical' => false, 'update_count_callback' => array(&$this, '_update_post_and_page_term_count')) );
			add_filter('posts_where', array(&$this, 'prepareQuery'));
		}

		// Remove embedded tags in posts display
		if ( $this->options['use_embed_tags'] == '1' ) {
			add_filter('the_content', array(&$this, 'filterEmbedTags'), 95);
		}

		// Add related posts in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $this->options['tt_embedded'] != 'no' || $this->options['tt_feed'] != 'no' ) {
			add_filter('the_content', array(&$this, 'inlinePostTags'), 97);
		}

		// Add post tags in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $this->options['rp_embedded'] != 'no' || $this->options['rp_feed'] != 'no' ) {
			add_filter('the_content', array(&$this, 'inlineRelatedPosts'), 98);
		}

		// Embedded tag cloud
		if ( $this->options['allow_embed_tcloud'] != '1' ) {
			add_filter('the_content', array(&$this, 'inlineTagCloud'), 99);
		}

		// Stock Posts ID (useful for autolink and metakeywords
		add_filter('the_posts', array(&$this, 'getPostIds'), 90);

		// Add keywords to header
		if ( $this->options['meta_autoheader'] == '1' && !class_exists('All_in_One_SEO_Pack') ) {
			add_action('wp_head', array(&$this, 'displayMetaKeywords'), 99);
		}

		// Auto link tags
		if ( $this->options['auto_link_tags'] == '1' ) {
			add_filter('the_content', array(&$this, 'autoLinkTags'), 96);
		}
		return;
	}

	/**
	 * Get links for each tag for auto link feature
	 *
	 */
	function prepareAutoLinkTags() {
		$this->getTagsFromCurrentPosts();
		
		$auto_link_min = (int) $this->options['auto_link_min'];
		if ( $auto_link_min == 0 ) {
			$auto_link_min = 1;		
		}

		$this->link_tags = array();
		foreach ( (array) $this->tags_currentposts as $tag ) {
			if  ( $tag->count >= $auto_link_min ) {
	 			$this->link_tags[$tag->name] = get_tag_link( $tag->term_id );
			}
		}
		return;
	}
	
	function autoLinkTags( $content = '' ) {
		if ( $this->link_tags == 'null' ) {
			$this->prepareAutoLinkTags();
		}

		// Rel or not ?
		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'rel="tag" ' : '';

		foreach ( (array) $this->link_tags as $tag_name => $tag_link ) {
			$content = $this->replaceTextByTagLink( $content, $tag_name, '<a href="'.$tag_link.'" class="st_tag internal_tag" '.$rel.'title="'. attribute_escape( sprintf( __('Posts tagged with %s', 'simpletags'), $tag_name ) ).'">', '</a>' );
		}
		return $content;
	}

	function replaceTextByTagLink( $content = '', $word = '', $pre = '', $after = '' ) {
		// Add first conteneur
		$content = '<temp_st>'.$content.'</temp_st>';

		// Put all anchor text into nonsense <##..##> tags
		$content = preg_replace("|(<a([^>]+)>)(.*?)(<\/a>)|is", "$1<##$3##>$4", $content);

		// Escape |
		$word = str_replace('|', '\|', $word);

		// Replace keywords not between <..> tags. <##..##> should be skipped too
		// Todo fix. Remplace only by full term
		$content = preg_replace("|(>)([^<]*)([^#a-z]*)($word)([^#a-z]*)|i", "\$1\$2\$3$pre \$4 $after\$5", $content);

		// Get rid of <##..##>
		$content = str_replace('<##', '', $content);
		$content = str_replace('##>', '', $content);

		// Remove conteneur
		$content = str_replace('<temp_st>', '', $content);
		$content = str_replace('</temp_st>', '', $content);

		return $content;
	}

	/**
	 * Replace marker by a tag cloud in post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlineTagCloud( $content = '' ) {
		if ( strpos($content, '<!--st_tag_cloud-->') ) {
			$content = str_replace('<!--st_tag_cloud-->', $this->extendedTagCloud( '', false ), $content);
		}
		return $content;
	}

	/**
	 * Stock posts ID as soon as possible
	 *
	 * @param array $posts
	 * @return array
	 */
	function getPostIds( $posts = array() ) {
		if ( !empty($posts) && is_array($posts) ) {
			foreach( (array) $posts as $post) {
				$this->posts[] = $post->ID;
			}
		}
		return $posts;
	}

	function getTagsFromCurrentPosts() {
		if ( is_array($this->posts) && count($this->posts) > 0 ) {
			// Generate SQL from post id
			$postlist = implode( "', '", $this->posts );

			// Generate key cache
			$key = md5(maybe_serialize($postlist));

			// Get cache if exist
			if ( $cache = wp_cache_get( 'generate_keywords', 'simpletags' ) ) {
				if ( isset( $cache[$key] ) ) {
					$this->tags_currentposts = $cache[$key];
					return;
				}
			}

			// If cache not exist, get datas and set cache
			global $wpdb;
			$results = $wpdb->get_results("
				SELECT DISTINCT t.name AS name, t.term_id AS term_id, tt.count AS count
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
				WHERE tt.taxonomy = 'post_tag'
				AND ( p.ID IN ('{$postlist}') )
				ORDER BY tt.count DESC");

			$cache[$key] = $results;
			wp_cache_set('generate_keywords', $cache, 'simpletags');

			$this->tags_currentposts = $results;
			unset($results, $key);
		}
	}

	/**
	 * Generate keywords for meta data
	 *
	 * @return string
	 */
	function generateKeywords() {
		// Get tags for current posts
		if ( empty($this->tags_currentposts) ) {
			$this->getTagsFromCurrentPosts();
		}

		$results = array();
		foreach ( (array) $this->tags_currentposts as $tag ) {
			$results[] = $tag->name;
		}
		unset($this->tags_currentposts);

		$always_list = trim($this->options['meta_always_include']); // Static keywords
		$always_array = (array) explode(',', $always_list);

		// Trim
		foreach ( $always_array as $keyword ) {
			if ( empty($keyword) ) {
				continue;
			}
			$results[] = trim($keyword);
		}
		unset($always_list, $always_array);

		// Unique keywords
		$results = array_unique($results);

		return strip_tags(implode(', ', $results));
	}

	/**
	 * Display meta keywords
	 *
	 */
	function displayMetaKeywords() {
		$tags_list = $this->generateKeywords();
		if ( !empty($tags_list) ) {
			echo "\n\t" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". '<meta name="keywords" content="' . $tags_list . '" />' ."\n";
		}
		return;
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
			if ( $this->options['rp_embedded'] == 'all' ) {
				$marker = true;
			} elseif ( $this->options['rp_embedded'] == 'blogonly' && is_feed() == false ) {
				$marker = true;
			} elseif ( $this->options['rp_embedded'] == 'homeonly' && is_home() == true ) {
				$marker = true;
			} elseif ( $this->options['rp_embedded'] == 'singularonly' && is_singular() == true ) {
				$marker = true;
			} elseif ( $this->options['rp_embedded'] == 'singleonly' && is_single() == true ) {
				$marker = true;
			} elseif ( $this->options['rp_embedded'] == 'pageonly' && is_page() == true ) {
				$marker = true;
			}
		}

		if ( $marker === true ) {
			$content .= $this->relatedPosts( '', false );
		}
		return $content;
	}

	/**
	 * Auto add current tags post to post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlinePostTags( $content = '' ) {
		$marker = false;
		if ( is_feed() ) {
			if ( $this->options['tt_feed'] == '1' ) {
				$marker = true;
			}
		} else {
			if ( $this->options['tt_embedded'] == 'all' ) {
				$marker = true;
			} elseif ( $this->options['tt_embedded'] == 'blogonly' && is_feed() == false ) {
				$marker = true;
			} elseif ( $this->options['tt_embedded'] == 'homeonly' && is_home() == true ) {
				$marker = true;
			} elseif ( $this->options['tt_embedded'] == 'singularonly' && is_singular() == true ) {
				$marker = true;
			} elseif ( $this->options['tt_embedded'] == 'singleonly' && is_single() == true ) {
				$marker = true;
			} elseif ( $this->options['tt_embedded'] == 'pageonly' && is_page() == true ) {
				$marker = true;
			}
		}

		if ( $marker === true ) {
			$content .= $this->extendedPostTags( '', false );
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
			'include_page' => 'true',
			'exclude_posts' => '',
			'exclude_tags' => '',
			'post_id' => '',
			'except_wrap' => 55,
			'limit_days' => 0,
			'min_shared' => 1,
			'title' => __('<h4>Related posts</h4>', 'simpletags'),
			'nopoststext' => __('No related posts.', 'simpletags'),
			'dateformat' => $this->dateformat,
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

		// Get current post data
		if ( (int) $post_id != 0 ) {
			$object_id = (int) $post_id;
		} else {
			global $post;
			$object_id = (int) $post->ID;
		}

		// Generate key cache
		$key = md5(maybe_serialize($user_args.'-'.$object_id));

		// Get cache if exist
		$results = false;
		if ( $cache = wp_cache_get( 'related_posts', 'simpletags' ) ) {
			if ( isset( $cache[$key] ) ) {
				$results = $cache[$key];
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
				$exclude_posts_sql = "AND p.ID NOT IN (";
				foreach ( $exclude_posts as $value ) {
					$value = (int) $value;
					if( $value != 0 ) {
						$exclude_posts_sql .= '"'.$value.'", ';
					}
				}
				$exclude_posts_sql .= '"'.$post_id.'")';
			} else {
				$exclude_posts_sql = "AND p.ID <> {$object_id}";
			}
			unset($exclude_posts);

			// Restricts tags
			$tags_to_exclude = array();
			if ( $exclude_tags != '' ) {
				$exclude_tags = (array) explode(',', $exclude_tags);
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

			// If empty use default xformat !
			if ( empty($xformat) ) {
				$xformat = $defaults['xformat'];
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
			global $wpdb;
			$results = $wpdb->get_results("
				SELECT DISTINCT p.post_title, p.comment_count, p.post_date, p.ID, COUNT(tr.object_id) AS counter {$select_excerpt} {$select_gp_concat}
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				WHERE tt.taxonomy = 'post_tag'
				AND (tt.term_id IN ({$tag_list}))
				{$exclude_posts_sql}
				AND p.post_status = 'publish'
				AND p.post_date < '".current_time('mysql')."'
				{$limit_days_sql}
				{$restrict_sql}
				GROUP BY tr.object_id
				ORDER BY {$order_by}
				{$limit_sql}");

			$cache[$key] = $results;
			wp_cache_set('related_posts', $cache, 'simpletags');
		}

		if ( $results === false || empty($results) ) {
			return $this->outputContent( 'st-related-posts', $format, $title, $nopoststext, $copyright );
		} elseif ( $format == 'array' ) {
			return $this->outputContent( 'st-related-posts', 'array', '', $results, $copyright );
		}

		if ( empty($dateformat) ) {
			$dateformat = $this->dateformat;
		}

		$output = array();
		// Replace placeholders
		foreach ( (array) $results as $result ) {
			if ( $min_shared > 1 && ( count(explode(',', $result->terms_id)) < $min_shared ) ) {
				continue;
			}

			$element_loop = $xformat;
			
			$title = apply_filters( 'the_title', $result->post_title );
			$element_loop = str_replace('%post_date%', mysql2date($dateformat, $result->post_date), $element_loop);
			$element_loop = str_replace('%post_permalink%', get_permalink($result->ID), $element_loop);			
			$element_loop = str_replace('%post_title%', $title, $element_loop);
			$element_loop = str_replace('%post_title_attribute%', attribute_escape(strip_tags($title)), $element_loop);		
			$element_loop = str_replace('%post_comment%', $result->comment_count, $element_loop);
			$element_loop = str_replace('%post_tagcount%', $result->counter, $element_loop);
			$element_loop = str_replace('%post_id%', $result->ID, $element_loop);
			$element_loop = str_replace('%post_relatedtags%', $this->getTagsFromID($result->terms_id), $element_loop);
			$element_loop = str_replace('%post_excerpt%', $this->getExcerptPost( $result->post_excerpt, $result->post_content, $result->post_password, $except_wrap ), $element_loop);

			$output[] = $element_loop;
		}
		unset($results, $result);
		return $this->outputContent( 'st-related-posts', $format, $title, $output, $copyright );
	}

	function getExcerptPost( $excerpt = '', $content = '', $password = '', $excerpt_length = 55 ) {
		if ( !empty($password) ) { // if there's a password
			if ( $_COOKIE['wp-postpass_'.COOKIEHASH] != $password ) {  // and it doesn't match the cookie
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

	//Get and format tags from list ID (SQL Group Concat)
	function getTagsFromID( $tags = '' ) {
		if ( empty($tags) ) {
			return '';
		}

		// Get tags since Term ID.
		$tags = get_terms('post_tag', 'include='.$tags);
		if ( empty($tags) ) {
			return '';
		}

		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'rel="tag"' : '';

		$output = '';
		foreach ( (array) $tags as $tag ) {
			$output .= '<a href="'.get_tag_link($tag->term_id).'" title="'.attribute_escape(sprintf( __ngettext('%d topic', '%d topics', $tag->count, 'simpletags'), $tag->count )).'" '.$rel.'>'.str_replace(' ', '&nbsp;', wp_specialchars($tag->name)).'</a>, ';
		}
		$output = substr($output, 0, strlen($output) - 2); // Remove latest ", "
		return $output;
	}

	/**
	 * Check is page is a tag view, even if tags haven't post
	 *
	 * @return boolean
	 */
	function isTag() {
		$slugs = get_query_var('tag');	
		
		if ( empty($slugs) ) {
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

		if ( !is_tag() && !$this->isTag() ) {
			return $this->outputContent( 'st-related-tags', $format, $title, '', true );
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
		
		// Generate key cache
		$key = md5(maybe_serialize($user_args.$slugs));

		// Get cache if exist
		$related_tags = false;
		if ( $cache = wp_cache_get( 'related_tags', 'simpletags' ) ) {
			if ( isset( $cache[$key] ) ) {
				$related_tags = $cache[$key];
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
				SELECT DISTINCT tr.object_id
				FROM {$wpdb->term_relationships} AS tr 
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
				INNER JOIN {$wpdb->terms} AS t ON tt.term_id = t.term_id
				WHERE tt.taxonomy = 'post_tag' 
				AND t.slug IN ({$terms}) 
				ORDER BY tr.object_id ASC");
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
			
			$cache[$key] = $related_tags;
			wp_cache_set('related_tags', $cache, 'simpletags');
		}
		
		if ( empty($related_tags) ) {
			return $this->outputContent( 'st-related-tags', $format, $title, $notagstext );
		} elseif ( $format == 'array' ) {
			return $this->outputContent( 'st-related-tags', 'array', '', $related_tags );
		}

		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			$related_tags = array_slice( $related_tags, 0, $number );
		}
				
		// HTML Rel (tag/no-follow)
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $no_follow;
		if ( $no_follow != 0 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Build outpout
		$output = array();
		foreach( $related_tags as $tag ) {
			$element_loop = $xformat;
			
			$element_loop = str_replace('%tag_link%', clean_url(get_tag_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_id%', $tag->term_id, $element_loop);
			$element_loop = str_replace('%tag_count%', $tag->count, $element_loop);
			$element_loop = str_replace('%tag_name%', str_replace(' ', '&nbsp;', attribute_escape( $tag->name )), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', attribute_escape(strip_tags($tag->name)), $element_loop);			
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);			
			$element_loop = str_replace('%tag_link_add%', $this->getAddTagToLink( $current_slugs, $tag->slug, $url_tag_sep ), $element_loop);

			$output[] = $element_loop;
		}
		unset($related_tags, $tag);
		return $this->outputContent( 'st-related-tags', $format, $title, $output );
	}
	
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
		return apply_filters('st_add_tag_link', $taglink, $tag_id);
	}

	/**
	 * Get tags to remove in related tags
	 *
	 * @param string $user_args
	 * @return string|array
	 */
	function removeRelatedTags( $user_args = '' ) {
		$defaults = array(
			'separator' => ' ',
			'format' => 'list',
			'notagstext' => ' ',
			'no_follow' => 0,
			'xformat' => __('&raquo; <a %tag_rel% href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags')
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

		if ( !is_tag() && !$this->isTag() ) {
			return $this->outputContent( 'st-remove-related-tags', $format, '', '', true );
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
		
		if ( $format == 'array' ) {
			return $this->outputContent( 'st-remove-related-tags', 'array', '', $current_slugs, true );
		}
						
		// HTML Rel (tag/no-follow)
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $no_follow;
		if ( $no_follow != 0 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		foreach ( $current_slugs as $slug ) {
			// Get term by slug
			$term = get_term_by('slug', $slug, 'post_tag');
			
			$element_loop = $xformat;
			
			$element_loop = str_replace('%tag_link%', clean_url(get_tag_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_id%', $term->term_id, $element_loop);
			$element_loop = str_replace('%tag_count%', $term->count, $element_loop);
			$element_loop = str_replace('%tag_name%', str_replace(' ', '&nbsp;', attribute_escape( $term->name )), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', attribute_escape(strip_tags($term->name)), $element_loop);			
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
					
			$element_loop = str_replace('%tag_link_remove%', $this->getRemoveTagToLink( $current_slugs, $term->slug, $url_tag_sep ), $element_loop);

			$output[] = $element_loop;			
		}
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
		return apply_filters('st_remove_tag_link', $taglink, $tag_id);
	}

	/**
	 * Sort an array without accent for naturel order :)
	 *
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	function uksortByName( $a = '', $b = '' ) {
	    return strnatcasecmp( remove_accents($a), remove_accents($b) );
	}

	/**
	 * Generate extended tag cloud
	 *
	 * @param string $args
	 * @return string|array
	 */
	function extendedTagCloud( $args = '', $copyright = true ) {
		$defaults = array(
			'size' => 'true',
			'smallest' => 8,
			'largest' => 22,
			'unit' => 'pt',
			'color' => 'true',
			'maxcolor' => '#000000',
			'mincolor' => '#CCCCCC',
			'number' => 45,
			'format' => 'flat',
			'cloud_selection' => 'count-desc',
			'cloud_sort' => 'random',
			'exclude' => '',
			'include' => '',
			'no_follow' => 0,
			'limit_days' => 0,
			'min_usage' => 0,
			'notagstext' => __('No tags.', 'simpletags'),
			'xformat' => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'category' => 0
		);

		// Get values in DB
		$defaults['no_follow'] = $this->options['no_follow'];
		$defaults['cloud_selection'] = $this->options['cloud_selection'];
		$defaults['cloud_sort'] = $this->options['cloud_sort'];
		$defaults['number'] = $this->options['cloud_limit_qty'];
		$defaults['notagstext'] = $this->options['cloud_notagstext'];
		$defaults['title'] = $this->options['cloud_title'];
		$defaults['maxcolor'] = $this->options['cloud_max_color'];
		$defaults['mincolor'] = $this->options['cloud_min_color'];
		$defaults['largest'] = $this->options['cloud_max_size'];
		$defaults['smallest'] = $this->options['cloud_min_size'];
		$defaults['unit'] = $this->options['cloud_unit'];
		$defaults['xformat'] = $this->options['cloud_xformat'];		
		$defaults['format'] = $this->options['cloud_format'];
		
		if ( empty($args) ) {				
			$args = $this->options['cloud_adv_usage'];
		}
		$args = wp_parse_args( $args, $defaults );


		// Get tags
		$tags = $this->getTags( $args );
		extract($args); // Params to variables

		if ( empty($tags) ) {
			return $this->outputContent( 'st-tag-cloud', $format, $title, $notagstext, $copyright );
		}

		$counts = $tag_links = $tag_ids =array();
		foreach ( (array) $tags as $tag ) {
			$counts[$tag->name] = $tag->count;
			$tag_links[$tag->name] = get_tag_link( $tag->term_id );
			$tag_ids[$tag->name] = $tag->term_id;
		}

		// Use full RBG code
		if ( strlen($maxcolor) == 4 ) {
			$maxcolor = $maxcolor . substr($maxcolor, 1, strlen($maxcolor));
		}
		if ( strlen($mincolor) == 4 ) {
			$mincolor = $mincolor . substr($mincolor, 1, strlen($mincolor));
		}

		// Check as smallest inferior or egal to largest
		if ( $smallest > $largest ) {
			$smallest = $largest;
		}

		// Scaling - Hard value for the moment
		$scale_min = 1;
		$scale_max = 10;

		$minval = min($counts);
		$maxval = max($counts);;

		$minout = max($scale_min, 0);
		$maxout = max($scale_max, $minout);

		$scale = ($maxval > $minval) ? (($maxout - $minout) / ($maxval - $minval)) : 0;

		// HTML Rel (tag/no-follow)
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $no_follow;
		if ( $no_follow != 0 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace('%tag_color%', '', $xformat);
		}

		// Remove size marquer if size = false
		if ( $size == 'false' ) {
			$xformat = str_replace('%tag_size%', '', $xformat);
		}

		// Order tags before output
		// count-asc/count-desc/name-asc/name-desc/random
		$cloud_sort = strtolower($cloud_sort);
		switch ( $cloud_sort ) {
			case 'count-asc':
				asort($counts);
				break;
			case 'count-desc':
				arsort($counts);
				break;
			case 'name-asc':
				uksort($counts, array( &$this, 'uksortByName'));
				break;
			case 'name-desc':
				uksort($counts, array( &$this, 'uksortByName'));
				array_reverse($counts);
				break;
			default: // random
				$counts = $this->randomArray($counts);
				break;
		}

		$output = array();
		foreach ( (array) $counts as $tag => $count ) {
			$scaleResult = (int) (($count - $minval) * $scale + $minout);

			$element_loop = $xformat;
			
			$element_loop = str_replace('%tag_link%', clean_url($tag_links[$tag]), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($tag_ids[$tag])), $element_loop);
			$element_loop = str_replace('%tag_id%', $tag_ids[$tag], $element_loop);
			$element_loop = str_replace('%tag_count%', $count, $element_loop);
			$element_loop = str_replace('%tag_size%', 'font-size:'.round(($scaleResult - $scale_min)*($largest-$smallest)/($scale_max - $scale_min) + $smallest, 2).$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%', 'color:'.$this->getColorByScale(round(($scaleResult - $scale_min)*(100)/($scale_max - $scale_min), 2),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_name%', str_replace(' ', '&nbsp;', attribute_escape( $tag )), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', attribute_escape(strip_tags($tag)), $element_loop);			
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
			$element_loop = str_replace('%tag_scale%', $scaleResult, $element_loop);
			$element_loop = str_replace('%tag_technorati%', $this->formatLink( 'technorati', $tag ), $element_loop);
			$element_loop = str_replace('%tag_flickr%', $this->formatLink( 'flickr', $tag ), $element_loop);
			$element_loop = str_replace('%tag_delicious%', $this->formatLink( 'delicious', $tag ), $element_loop);

			$output[] = $element_loop;
		}
		unset($counts, $tag_links, $tag_ids);
		return $this->outputContent( 'st-tag-cloud', $format, $title, $output, $copyright );
	}

	/**
	 * Randomize an array and keep association
	 *
	 * @param array $data
	 * @return array
	 */
	function randomArray( $data_in ) {
		srand( (float) microtime() * 1000000 ); // For PHP < 4.2
		$rand_keys = array_rand($data_in, count($data_in));

		foreach( (array) $rand_keys as $key ) {
			$data_out[$key] = $data_in[$key];
		}

		return $data_out;
	}

	/**
	 * Format nice URL depending service
	 *
	 * @param string $type
	 * @param string $tag_name
	 * @return string
	 */
	function formatLink( $type = '', $tag_name = '' ) {
		if ( empty($tag_name) ) {
			return '';
		}

		switch ( $type ) {
			case 'technorati':
				return '<a class="tag_technorati" href="http://technorati.com/tag/'.str_replace(' ', '+', $tag_name).'" rel="tag">'.$tag_name.'</a>';
				break;
			case 'flickr':
				return '<a class="tag_flickr" href="http://www.flickr.com/photos/tags/'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($tag_name)).'/" rel="tag">'.$tag_name.'</a>';
				break;
			case 'delicious':
				return '<a class="tag_delicious" href="http://del.icio.us/popular/'.strtolower(str_replace(' ', '', $tag_name)).'" rel="tag">'.$tag_name.'</a>';
				break;
		}
		return '';
	}

	/**
	 * Generate current post tags
	 *
	 * @param string $args
	 * @return string
	 */
	function extendedPostTags( $args = '', $copyright = true ) {
		$defaults = array(
			'before' => __('Tags: ', 'simpletags'),
			'separator' => ', ',
			'after' => '<br />',
			'post_id' => '',
			'no_follow' => 0,
			'xformat' => __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'notagtext' => __('No tag for this post.', 'simpletags'),
			'number' => 0
		);

		// Get values in DB
		$defaults['no_follow'] = $this->options['no_follow'];
		$defaults['before'] = $this->options['tt_before'];
		$defaults['separator'] = $this->options['tt_separator'];
		$defaults['after'] = $this->options['tt_after'];
		$defaults['notagtext'] = $this->options['tt_notagstext'];
		$defaults['number'] = $this->options['tt_number'];
		$defaults['xformat'] = $this->options['tt_xformat'];
		
		if ( empty($args) ) {
			$args = $this->options['tt_adv_usage'];
		}

		$args = wp_parse_args( $args, $defaults );
		extract($args);

		$post_id = (int) $post_id;
		$tags = get_the_tags( $post_id );

		// If no tags, return text nothing.
		if ( empty($tags) ) {
			return $notagtext;
		}

		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			$tags = $this->randomArray($tags); // Randomize tags
			$tags = array_slice( $tags, 0, $number );
		}

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}

		// HTML Rel (tag/no-follow)
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $no_follow;
		if ( $no_follow != 0 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		foreach ( (array) $tags as $tag ) {
			$element_loop = $xformat;

			$element_loop = str_replace('%tag_link%', clean_url(get_tag_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_id%', $tag->term_id, $element_loop);
			$element_loop = str_replace('%tag_name%', str_replace(' ', '&nbsp;', wp_specialchars($tag->name)), $element_loop);
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
			$element_loop = str_replace('%tag_count%', $tag->count, $element_loop);

			$element_loop = str_replace('%tag_technorati%', $this->formatLink( 'technorati', $tag->name ), $element_loop);
			$element_loop = str_replace('%tag_flickr%', $this->formatLink( 'flickr', $tag->name ), $element_loop);
			$element_loop = str_replace('%tag_delicious%', $this->formatLink( 'delicious', $tag->name ), $element_loop);

			$tag_links[] = $element_loop;
		}
		unset($tags, $tag);
		$tag_list = apply_filters( 'the_tags', join( $separator, $tag_links ) );

		if ( $copyright === true )
			return "\n\t" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". $before . $tag_list . $after ."\n";
		else
			return "\n\t". $before . $tag_list . $after ."\n";
	}

	/**
	 * Delete embedded tags
	 *
	 * @param string $content
	 * @return string
	 */
	function filterEmbedTags( $content ) {
		$tagstart = $this->options['start_embed_tags'];
		$tagend = $this->options['end_embed_tags'];
		$len_tagend = strlen($tagend);

		while ( strpos($content, $tagstart) != false && strpos($content, $tagend) != false ) {
			$pos1 = strpos($content, $tagstart);
			$pos2 = strpos($content, $tagend);
			$content = str_replace(substr($content, $pos1, ($pos2 - $pos1 + $len_tagend)), '', $content);
		}
		return $content;
	}

	// Tags functions
	/**
	 * This is pretty filthy. Doing math in hex is much too weird. It's more likely to work, this way!
	 * Provided from UTW. Thanks.
	 *
	 * @param integer $scale_color
	 * @param string $min_color
	 * @param string $max_color
	 * @return string
	 */
	function getColorByScale($scale_color, $min_color, $max_color) {
		$scale_color = $scale_color / 100;

		$minr = hexdec(substr($min_color, 1, 2));
		$ming = hexdec(substr($min_color, 3, 2));
		$minb = hexdec(substr($min_color, 5, 2));

		$maxr = hexdec(substr($max_color, 1, 2));
		$maxg = hexdec(substr($max_color, 3, 2));
		$maxb = hexdec(substr($max_color, 5, 2));

		$r = dechex(intval((($maxr - $minr) * $scale_color) + $minr));
		$g = dechex(intval((($maxg - $ming) * $scale_color) + $ming));
		$b = dechex(intval((($maxb - $minb) * $scale_color) + $minb));

		if (strlen($r) == 1) $r = '0'.$r;
		if (strlen($g) == 1) $g = '0'.$g;
		if (strlen($b) == 1) $b = '0'.$b;

		return '#'.$r.$g.$b;
	}

	/**
	 * Add page in tag search
	 *
	 * @param string $where
	 * @return string
	 */
	function prepareQuery( $where ) {
		if ( is_tag() ) {
			$where = str_replace('post_type = \'post\'', 'post_type IN(\'page\', \'post\')', $where);
		}
		return $where;
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		update_option($this->db_options, $this->default_options);
		$this->options = $this->default_options;
	}

	/**
	 * Update taxonomy counter for post AND page
	 *
	 * @param array $terms
	 */
	function _update_post_and_page_term_count( $terms ) {
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type IN ('page', 'post') AND term_taxonomy_id = '$term'");
			$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = '$count' WHERE term_taxonomy_id = '$term'");
		}
	}

	/**
	 * Extended get_tags function that use getTerms function
	 *
	 * @param string $args
	 * @return array
	 */
	function getTags( $args = '', $skip_cache = false ) {
		$key = md5(serialize($args));

		if ( $skip_cache == true ) {
			$tags = $this->getTerms('post_tag', $args);
		}
		else {
			// Get cache if exist
			if ( $cache = wp_cache_get( 'st_get_tags', 'simpletags' ) ) {
				if ( isset( $cache[$key] ) ) {
					return apply_filters('get_tags', $cache[$key], $args);
				}
			}

			// Get tags
			$tags = $this->getTerms('post_tag', $args);

			if ( empty($tags) ) {
				return array();
			}

			$cache[$key] = $tags;
			wp_cache_set( 'st_get_tags', $cache, 'simpletags' );
		}

		$tags = apply_filters('get_tags', $tags, $args);
		return $tags;
	}

	/**
	 * Extended get_terms function support
	 *  - Limit category
	 *  - Limit days
	 *  - Selection restrict
	 *  - Min usage
	 *
	 * @param string|array $taxonomies
	 * @param string $args
	 * @return array
	 */
	function getTerms( $taxonomies, $args = '' ) {
		global $wpdb;

		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}

		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( !is_taxonomy($taxonomy) ) {
				return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
			}
		}

		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";

		$defaults = array(
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
			'limit_days' => 0,
			'category' => 0,
			'min_usage' => 0
		);

		$args = wp_parse_args( $args, $defaults );

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
			if ( !isset($hierarchy[$child_of]) ) {
				return array();
			}
		}

		if ( $parent ) {
			$hierarchy = _get_term_hierarchy($taxonomies[0]);
			if ( !isset($hierarchy[$parent]) ) {
				return array();
			}
		}

		// Get cache if exist
		$key = md5( serialize( $args ) . serialize( $taxonomies ) );
		if ( $cache = wp_cache_get( 'get_terms', 'terms' ) ) {
			if ( isset( $cache[$key] ) ) {
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

		if ( 'all' == $fields ) {
			$select_this = 't.*, tt.*';
		} else if ( 'ids' == $fields ) {
			$select_this = 't.term_id';
		} else if ( 'names' == $fields ) {
			$select_this == 't.name';
		}

		// Limit posts date
		$limitdays_sql = '';
		$limit_days = (int) $limit_days;
		if ( $limit_days != 0 ) {
			$limitdays_sql = 'AND p.post_date > "' .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). '"';
		}

		// Join posts ?
		$inner_posts = '';
		if ( !empty($limitdays_sql) | !empty($category_sql) ) {
			$inner_posts = "
				INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
				INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID";
		}

		$query = "SELECT DISTINCT {$select_this}
			FROM {$wpdb->terms} AS t
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
			{$inner_posts}
			WHERE tt.taxonomy IN ( {$in_taxonomies} )
			{$limitdays_sql}
			{$category_sql}
			{$where}
			{$restict_usage}
			ORDER BY {$order_by}
			{$number_sql}";

		if ( 'all' == $fields ) {
			$terms = $wpdb->get_results($query);
			update_term_cache($terms);
		} elseif ( 'ids' == $fields ) {
			$terms = $wpdb->get_col($query);
		}

		if ( empty($terms) ) {
			return array();
		}

		if ( $child_of || $hierarchical ) {
			$children = _get_term_hierarchy($taxonomies[0]);
			if ( ! empty($children) ) {
				$terms = & _get_term_children($child_of, $terms, $taxonomies[0]);
			}
		}

		// Update term counts to include children.
		if ( $pad_counts ) {
			_pad_term_counts($terms, $taxonomies[0]);
		}

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty ) {
			foreach ( (array) $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = _get_term_children($term->term_id, $terms, $taxonomies[0]);
					foreach ( (array) $children as $child ) {
						if ( $child->count ) {
							continue 2;
						}
					}

					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset($terms);

		$cache[$key] = $terms;
		wp_cache_set( 'get_terms', $cache, 'terms' );

		$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
		return $terms;
	}
	
	function outputContent( $html_class= '', $format = 'list', $title = '', $content = '', $copyright = true ) {
		if ( empty($content) ) {
			return ''; // return nothing
		}

		if ( is_array($content) ) {
			switch ( $format ) {
				case 'array' :
					$output =& $content;
					break;
				case 'list' :
					$output = "<ul class='{$html_class}'>\n\t<li>";
					$output .= join("</li>\n\t<li>", $content);
					$output .= "</li>\n</ul>\n";
					break;
				default :
					$output = "<div class='{$html_class}'>\n\t";
					$output .= join("\n", $content);
					$output .= "</div>\n";
					break;
			}
		} else {
			$content = trim($content);
			switch ( $format ) {
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

// Check version.
global $wp_version;
if ( version_compare($wp_version, '2.3', '<') ) {
	echo 'Plugin compatible with WordPress 2.3 or higher only.';
	return false;
}

// Init ST
global $simple_tags;
$simple_tags = new SimpleTags();

// Old method for is_admin function (fix for WP 2.3.2 and sup !)
if ( !function_exists('is_admin_old') ) {
	function is_admin_old() {
		return (stripos($_SERVER['REQUEST_URI'], 'wp-admin/') !== false);
	}
}

// Admin and XML-RPC
if ( is_admin() || is_admin_old() || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ) {
	require(dirname(__FILE__).'/inc/simple-tags.admin.php');
	$simple_tags_admin = new SimpleTagsAdmin( $simple_tags->default_options, $simple_tags->version );
}

// Templates functions
require(dirname(__FILE__).'/inc/simple-tags.functions.php');

// Widgets
require(dirname(__FILE__).'/inc/simple-tags.widgets.php');
?>