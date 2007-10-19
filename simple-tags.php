<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://www.herewithme.fr/wordpress-plugins/simple-tags
Description: Simple Tags : Extended Tagging with WordPress 2.3
Version: 1.1.1
Author: Amaury BALMER
Author URI: http://www.herewithme.fr

© Copyright 2007  Amaury BALMER (balmer.amaury@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

Contributors:
Kévin Drouvin (kevin.drouvin@gmail.com - http://inside-dev.net/)
*/

Class SimpleTags {
	var $version = '1.1.1';

	var $info;
	var $options;
	var $default_options;
	var $db_options = 'simpletags';
	var $dateformat;

	// Stock Post ID for current view
	var $posts;

	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {
		// Options
		$defaultopt = array(
			// General 
			'inc_page_tag_search' => '1',
			// Administration
			'use_tag_pages' => '1',
			'use_tag_links' => '0',	
			'admin_max_suggest' => '100',
			'admin_tag_suggested' => '1',
			'admin_tag_sort' => 'popular',
			// Embedded Tags
			'use_embed_tags' => '0',
			'start_embed_tags' => '[tags]',
			'end_embed_tags' => '[/tags]',			
			// Related Posts
			'rp_embedded' => 'no',
			'rp_sortorderby' => 'DESC',
			'rp_sortorder' => 'counter',
			'rp_limit_qty' => '5',
			'rp_notagstext' => __('No related posts.', 'simpletags'),
			'rp_title' => __('<h4>Related posts</h4>', 'simpletags'),
			'rp_adv_usage' => '',
			// Tag cloud
			'cloud_sortorder' => 'ASC',
			'cloud_sortorderby' => 'name',
			'cloud_limit_qty' => '45',
			'cloud_notagstext' => __('No tags.', 'simpletags'),
			'cloud_title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'cloud_max_color' => '#000000',
			'cloud_min_color' => '#CCCCCC',
			'cloud_max_size' => '22',
			'cloud_min_size' => '8',
			'cloud_unit' => 'pt',
			'cloud_adv_usage' => '',
			// The tags
			'tt_embedded' => 'no',
			'tt_separator' => ', ',
			'tt_before' => __('Tags: ', 'simpletags'),
			'tt_after' => '<br />',
			'tt_notagstext' => __('No tag for this post.', 'simpletags'),
			'tt_adv_usage' => '',
			// Meta keywords
			'meta_autoheader' => '1', 
			'meta_always_include' => ''		
		);

		// Set class property for default options
		$this->default_options = $defaultopt;

		// Get options from WP options
		$optionsFromTable = get_option( $this->db_options );
		if ( !$optionsFromTable ) {
			$this->resetToDefaultOptions();
		}

		// Update default options by getting not empty values from options table
		foreach( (array) $defaultopt as $def_optname => $def_optval ) {
			$defaultopt[$def_optname] = $optionsFromTable[$def_optname];
		}

		// Set the class property and unset no used variable
		$this->options = $defaultopt;
		unset($defaultopt);
		unset($optionsFromTable);

		// Determine installation path & url
		$path = basename(str_replace('\\','/',dirname(__FILE__)));
		$info['siteurl'] = get_option('siteurl');
		$info['install_url'] = $info['siteurl'] . '/wp-content/plugins';
		$info['install_dir'] = ABSPATH . 'wp-content/plugins';
		if ( $path != 'plugins' ) {
			$info['install_url'] .= '/' . $path;
			$info['install_dir'] .= '/' . $path;
		}

		// Set informations
		$this->info = array(
			'siteurl' 			=> $info['siteurl'],
			'install_url'		=> $info['install_url'],
			'install_dir'		=> $info['install_dir']
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

		// Hide embed tags in posts
		if ( $this->options['use_embed_tags'] == '1' ) {
			add_filter('the_content', array(&$this, 'filterEmbedTags'), 99);
		}
		
		// Add related posts in post ( all / feedonly / blogonly / no )
		if ( $this->options['tt_embedded'] != 'no' ) {
			add_filter('the_content', array(&$this, 'inlinePostTags'), 98);
		}	
	
		// Add related posts in post ( all / feedonly / blogonly / no )
		if ( $this->options['rp_embedded'] != 'no' ) {
			add_filter('the_content', array(&$this, 'inlineRelatedPosts'), 99);
		}

		// Add keywords to header
		if ( $this->options['meta_autoheader'] == '1' ) {
			add_filter('the_posts', array(&$this, 'getPostIds'), 90);
			add_action('wp_head', array(&$this, 'displayMetaKeywords'), 99);
		}
		return;
	}

	/**
	 * Stock posts ID as soon as possible
	 *
	 * @param array $posts
	 * @return array
	 */
	function getPostIds( $posts ) {
		if ( !is_null($posts) && is_array($posts) ) {
			foreach( (array) $posts as $post) {
				$this->posts[] = $post->ID;
			}
		}
		return $posts;
	}

	/**
	 * Generate keywords for meta data
	 *
	 * @return unknown
	 */
	function generateKeywords() {
		if ( is_array($this->posts) && count($this->posts) > 0 ) {
			$postlist = implode( "', '", $this->posts );
			$results = wp_cache_get( crypt($postlist), 'simpletags');
			if (false === $results) {
				global $wpdb;   
				$results = $wpdb->get_col("
		          	SELECT DISTINCT terms.name
					FROM {$wpdb->posts} AS posts
					INNER JOIN {$wpdb->term_relationships} AS term_relationships ON (posts.ID = term_relationships.object_id)
					INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON (term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id) 
					INNER JOIN {$wpdb->terms} AS terms ON (term_taxonomy.term_id = terms.term_id) 
					WHERE term_taxonomy.taxonomy = 'post_tag'
		          	AND ( posts.ID IN ('{$postlist}') )");
				
				wp_cache_set( crypt($postlist), $results, 'simpletags');
			}
			
			$always_list = trim($this->options['meta_always_include']);
			$always_array = explode(',', $always_list);
			foreach ( (array) $always_array as $keyword ) {
				$results[] = trim($keyword);
			}
			unset($always_list, $always_array);
			
			// Unique keywords
			$results = array_unique($results);		
			
			return strip_tags(implode(', ', $results));
		}
		return '';
	}
	
	/**
	 * Display meta keywords
	 *
	 */
	function displayMetaKeywords() {
		$tags_list = $this->generateKeywords();
		if ( !empty($tags_list) ) {
			echo "\n\t" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://www.herewithme.fr/wordpress-plugins/simple-tags -->' ."\n\t". '<meta name="keywords" content="' . $tags_list . '" />' ."\n";
		}
		return;
	}

	/**
	 * Auto add related posts to post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlineRelatedPosts( $content ) {
 		$marker = false;
 		if ( $this->options['rp_embedded'] == 'all' ) {
 			$marker = true;
 		} elseif ( $this->options['rp_embedded'] == 'blogonly' &&  is_feed() == false ) {
			$marker = true;
 		} elseif ( $this->options['rp_embedded'] == 'feedonly' &&  is_feed() == true ) {
			$marker = true;
 		}

		if ( $marker == true ) {
			$content .= $this->relatedPosts();
		}
		return $content;
	}
	
	/**
	 * Auto add current tags post to post content
	 *
	 * @param string $content
	 * @return string
	 */
	function inlinePostTags( $content ) {
 		$marker = false;
 		if ( $this->options['tt_embedded'] == 'all' ) {
 			$marker = true;
 		} elseif ( $this->options['tt_embedded'] == 'blogonly' &&  is_feed() == false ) {
			$marker = true;
 		} elseif ( $this->options['tt_embedded'] == 'feedonly' &&  is_feed() == true ) {
			$marker = true;
 		}

		if ( $marker == true ) {
			$content .= $this->extendedPostTags();
		}
		return $content;
	}	
	

	/**
	 * Generate related posts
	 *
	 * @param string $user_args
	 * @return string|array
	 */
	function relatedPosts( $user_args = '' ) {
		$defaults = array(
			'number' => 5,
			'orderby' => 'counter',
			'order' => 'DESC',
			'format' => 'list',
			'include_page' => 'true',
			'exclude_posts' => '',
			'exclude_tags' => '',
			'post_id' => '',
			'limit_days' => '0',
			'title' => __('<h4>Related posts</h4>', 'simpletags'),
			'nopoststext' => __('No related posts.', 'simpletags'),
			'dateformat' => $this->dateformat,
			'xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags')
		);
		
		// If empty user_args, use user option in DB.
		if( empty($user_args) ) {
			$defaults['number'] = $this->options['rp_limit_qty'];
			$defaults['order'] = $this->options['rp_sortorderby'];
			$defaults['orderby'] = $this->options['rp_sortorder'];
			$defaults['nopoststext'] = $this->options['rp_notagstext'];
			$defaults['title'] = $this->options['rp_title'];
			$user_args = $this->options['rp_adv_usage'];
		}

		$args = wp_parse_args( $user_args, $defaults );
		extract($args);
		
		// Get current post data
		$post_id = (int) $post_id;
		if ( $post_id == 0 ) {
			global $post;
			$post_id = (int) $post->ID;
		}

		// Start Eventual Cache
		$results = wp_cache_get( crypt($user_args.'-'.$post_id), 'simpletags');
		if ( $results === false ) {
			// Get get tags
			$current_tags = get_the_tags($post_id);
			
			if ( $current_tags == false || $post_id == 0 ) {
				return $this->outputRelatedPosts( $format, $title, $nopoststext );
			}
			
			// Number - Limit
			$number = (int) $number;
			if ( $number == 0 ) {
				$number = 5;
			} elseif( $number > 30 ) {
				$number = 30;
			}
			$limit_sql = 'LIMIT 0, '.$number;
			unset($number);

			// Order
			$order = strtolower($order);
			if ( $order == 'asc' ) {
				$order = 'ASC';
			} else {
				$order = 'DESC';
			}

			// Order by
			$orderby = strtolower($orderby);
			if ( $orderby == 'counter' ) {
				$order_sql = "ORDER BY counter {$order}, posts.post_date DESC";
			} elseif ( $orderby == 'post_date' ) {
				$order_sql = "ORDER BY posts.post_date {$order}";
			} elseif ( $orderby == 'random' ) {
				$order_sql = "ORDER BY RAND()";
			} else {
				$order_sql = "ORDER BY posts.post_title {$order}, posts.post_date DESC";
			}
			unset($orderby, $order);
			 
			// Limit days - 86400 seconds = 1 day
			$limit_days = (int) $limit_days;
			$limitdays_sql = '';
			if ( $limit_days != 0 ) {
				$limitdays_sql = 'AND posts.post_date > "' .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). '"';
			}
			unset($limit_days);

			// Include_page
			$include_page = strtolower($include_page);
			if ( $include_page == 'true' ) {
				$restrict_sql = "AND posts.post_type IN ('page', 'post')";
			} else {
				$restrict_sql = "AND posts.post_type = 'post'";
			}
			unset($include_page);

			// Restrict posts
			$exclude_posts_sql = '';
			if ( $exclude_posts != '' ) {
				$exclude_posts = explode(',', $exclude_posts);
				$exclude_posts_sql = "AND posts.ID NOT IN (";
				foreach ( (array) $exclude_posts as $value ) {
					$value = (int) $value;
					if( $value != 0 ) {
						$exclude_posts_sql .= '"'.$value.'", ';
					}
				}
				$exclude_posts_sql .= '"'.$post_id.'")';
			} else {
				$exclude_posts_sql = "AND posts.ID != {$post_id}";
			}
			unset($exclude_posts);

			// Restricts tags
			$tags_to_exclude = array();
			if ( $exclude_tags != '' ) {
				$exclude_tags = explode(',', $exclude_tags);
				foreach ( (array) $exclude_tags as $value ) {
					$tags_to_exclude[] = trim($value);
				}
			}
			unset($exclude_tags);

			// SQL Tags list
			$taglist = '';
			foreach ( (array) $current_tags as $tag ) {
				if ( !in_array($tag->name, $tags_to_exclude) ) {
					$taglist .= '"'.(int) $tag->term_id.'", ';
				}
			}
			
			// If empty return no posts text
			if ( empty($taglist) ) {
				return $this->outputRelatedPosts( $format, $title, $nopoststext );
			}
			$taglist = substr($taglist, 0, strlen($taglist) - 2); // Remove latest ", "
			
			// Group Concat only for MySQL > 4.1
			if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
				$group_concat_sql = ', GROUP_CONCAT(term_taxonomy.term_id) as terms_id';
			}

			// Posts: title, comments_count, date, permalink, post_id, counter
			global $wpdb;
			$results = $wpdb->get_results("
		        SELECT DISTINCT posts.post_title, posts.comment_count, posts.post_date, posts.ID, COUNT(term_relationships.object_id) as counter {$group_concat_sql}
		        FROM {$wpdb->posts} AS posts
		        INNER JOIN {$wpdb->term_relationships} AS term_relationships ON (posts.ID = term_relationships.object_id)
		        INNER JOIN {$wpdb->term_taxonomy} AS term_taxonomy ON (term_relationships.term_taxonomy_id = term_taxonomy.term_taxonomy_id) 
		        WHERE term_taxonomy.taxonomy = 'post_tag'
		        AND (term_taxonomy.term_id IN ({$taglist}))
		        {$exclude_posts_sql}
		        AND posts.post_status = 'publish'
		        AND posts.post_date < '".current_time('mysql')."'
		        {$limitdays_sql}
		        {$restrict_sql}
		        GROUP BY term_relationships.object_id
		        {$order_sql}
		        {$limit_sql}");
			
			wp_cache_set( crypt($user_args.'-'.$post_id), $results, 'simpletags');
		}

		if ( !$results ) {
			return $this->outputRelatedPosts( $format, $title, $nopoststext );
		}
		
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
		
		if ( empty($dateformat) ) {
			$dateformat = $this->dateformat;
		}
		
		if ( !version_compare(mysql_get_server_info(), '4.1.0', '>=') ) {
			$xformat = str_replace('%post_relatedtags%', '', $xformat);
		}

		foreach ( (array) $results as $result ) {
			// Replace placeholders
			$element_loop = $xformat;
			
			// Keep for series 1.0.x / To delete for 1.2/1.3
			$element_loop = str_replace('%date%', mysql2date($dateformat, $result->post_date), $element_loop);
			$element_loop = str_replace('%permalink%', get_permalink($result->ID), $element_loop);
			$element_loop = str_replace('%title%', $result->post_title, $element_loop);
			$element_loop = str_replace('%commentcount%', $result->comment_count, $element_loop);
			$element_loop = str_replace('%tagcount%', $result->counter, $element_loop);
			$element_loop = str_replace('%postid%', $result->ID, $element_loop);
			
			// New markers
			$element_loop = str_replace('%post_date%', mysql2date($dateformat, $result->post_date), $element_loop);
			$element_loop = str_replace('%post_permalink%', get_permalink($result->ID), $element_loop);
			$element_loop = str_replace('%post_title%', $result->post_title, $element_loop);
			$element_loop = str_replace('%post_comment%', $result->comment_count, $element_loop);
			$element_loop = str_replace('%post_tagcount%', $result->counter, $element_loop);
			$element_loop = str_replace('%post_id%', $result->ID, $element_loop);
			$element_loop = str_replace('%post_relatedtags%', $this->getTagsFromID($result->terms_id), $element_loop);
			
			$output[] = $element_loop;
		}
		return $this->outputRelatedPosts( $format, $title, $output );
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
			$output .= '<a href="'.get_tag_link($tag->term_id).'" title="'.attribute_escape(sprintf( __ngettext('%d topic','%d topics',$tag->count), $tag->count )).'" '.$rel.'>'.str_replace(' ', '&nbsp;', wp_specialchars($tag->name)).'</a>, ';
		}
		$output = substr($output, 0, strlen($output) - 2); // Remove latest ", "
		return $output;
	}

	/**
	 * Format related posts before display
	 *
	 * @param string $format
	 * @param string $title
	 * @param string|array $content
	 * @return string
	 */
	function outputRelatedPosts( $format = 'list', $title = '', $content = '' ) {
		if ( is_array($content) ) {
			switch ( $format ) {
				case 'array' :
					$return =& $content;
					break;
				case 'list' :
					$return = "<ul class='st-related-posts'>\n\t<li>";
					$return .= join("</li>\n\t<li>", $content);
					$return .= "</li>\n</ul>\n";
					break;
				default :
					$return = join("\n", $content);
					break;
			}
		} else {
			switch ( $format ) {
				case 'list' :
					$return = "<ul class='st-related-posts'>\n\t";
					$return .= '<li>'.$content."</li>\n\t";
					$return .= "</ul>\n";
					break;
				default :
					$return = $content;
					break;
			}
		}
		
		if ( strtolower($title) == 'false' ) {
			$title = '';
		}
		
		return "\n" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://www.herewithme.fr/wordpress-plugins/simple-tags -->' ."\n\t". $title ."\n\t". $return. "\n";
	}

	/**
	 * Generate extended tag cloud
	 *
	 * @param string $args
	 * @return string|array
	 */
	function extendedTagCloud( $args = '' ) {
		$defaults = array(
			'smallest' => 8,
			'largest' => 22,
			'unit' => 'pt',
			'number' => 45,
			'format' => 'flat',
			'orderby' => 'name',
			'order' => 'ASC',
			'exclude' => '',
			'include' => '',
			'limit_days' => 0,
			'notagstext' => __('No tags.', 'simpletags'),
			'xformat' => __('<a href="%tag_link%" class="tag-link-%tag_id%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'color' => 'true',
			'maxcolor' => '#000000',
			'mincolor' => '#CCCCCC',
			'title' => __('<h4>Tag Cloud</h4>', 'simpletags')
		);

		if ( empty($args) ) {
			$defaults['order'] = $this->options['cloud_sortorder'];
			$defaults['orderby'] = $this->options['cloud_sortorderby'];
			$defaults['number'] = $this->options['cloud_limit_qty'];
			$defaults['notagstext'] = $this->options['cloud_notagstext'];
			$defaults['title'] = $this->options['cloud_title'];
			$defaults['maxcolor'] = $this->options['cloud_max_color'];
			$defaults['mincolor'] = $this->options['cloud_min_color'];
			$defaults['largest'] = $this->options['cloud_max_size'];
			$defaults['smallest'] = $this->options['cloud_min_size'];
			$defaults['unit'] = $this->options['cloud_unit'];
			$args = $this->options['cloud_adv_usage'];
		}
		$args = wp_parse_args( $args, $defaults );

		// Get tags
		$tags = $this->get_tags( $args );
		extract($args);

		if ( empty($tags) ) {
			return $this->outputExtendedTagCloud( $format, $title, $output );
		}

		$counts = $tag_links = array();
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

		// Largest must be superior to smallest !
		if ( $smallest == $largest ) {
			$largest = $smallest + 1;
		}
		
		// Scaling - Hard value for the moment
		$scale_min = 1;
		$scale_max = 10;

		$minval = min($counts);
		$maxval = max($counts);;
		
		$minout = max($scale_min, 0);
		$maxout = max($scale_max, $minout);
		
		$scale = ($maxval > $minval) ? (($maxout - $minout) / ($maxval - $minval)) : 0;

		// Rel or not ?
		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'rel="tag"' : '';

		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
		
		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace('%tag_color%', '', $xformat);
		}

		$output = array();
		foreach ( (array) $counts as $tag => $count ) {
			$scaleResult = (int) (($count - $minval) * $scale + $minout);
						
			$element_loop = $xformat;
			$element_loop = str_replace('%tag_link%'	, clean_url($tag_links[$tag]), $element_loop);
			$element_loop = str_replace('%tag_feed%'	, clean_url(get_tag_feed_link($tag_ids[$tag])), $element_loop);
			$element_loop = str_replace('%tag_id%'		, $tag_ids[$tag], $element_loop);
			$element_loop = str_replace('%tag_count%'	, $count, $element_loop);
			$element_loop = str_replace('%tag_size%'	, 'font-size:'.round(($scaleResult - $scale_min)*($largest-$smallest)/($scale_max - $scale_min) + $smallest, 1).$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%'	, 'color:'.$this->getColorByScale(round(($scaleResult - $scale_min)*(100)/($scale_max - $scale_min), 1),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_name%'	, str_replace(' ', '&nbsp;', wp_specialchars( $tag )), $element_loop);
			$element_loop = str_replace('%tag_rel%'		, $rel, $element_loop);
			
			// Add in version 1.1
			$element_loop = str_replace('%tag_scale%'		, $scaleResult, $element_loop);
			$element_loop = str_replace('%tag_technorati%'	, $this->formatLink( 'technorati', $tag ), $element_loop);
			$element_loop = str_replace('%tag_flickr%'		, $this->formatLink( 'flickr', $tag ), $element_loop);
			$element_loop = str_replace('%tag_delicious%'	, $this->formatLink( 'delicious', $tag ), $element_loop);
			
			$output[] = $element_loop;
		}

		return $this->outputExtendedTagCloud( $format, $title, $output );
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
	 * Format tag cloud ouput before display
	 *
	 * @param string $format
	 * @param string $title
	 * @param string|array $content
	 * @return string
	 */
	function outputExtendedTagCloud( $format = 'list', $title = '', $content = '' ) {
		if ( is_array($content) ) {
			switch ( $format ) {
				case 'array' :
					$return =& $content;
					break;
				case 'list' :
					$return = "<ul class='st-tag-cloud'>\n\t<li>";
					$return .= join("</li>\n\t<li>", $content);
					$return .= "</li>\n</ul>\n";
					break;
				default :
					$return = join("\n", $content);
					break;
			}
		} else {
			switch ( $format ) {
				case 'list' :
					$return = "<ul class='st-tag-cloud'>\n\t";
					$return .= '<li>'.$content."</li>\n\t";
					$return .= "</ul>\n";
					break;
				default :
					$return = $content;
					break;
			}
		}

		if ( strtolower($title) == 'false' ) {
			$title = '';
		}
		
		return "\n" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://www.herewithme.fr/wordpress-plugins/simple-tags -->' ."\n\t". $title ."\n\t". $return ."\n";
	}
	
	/**
	 * Generate current post tags
	 *
	 * @param string $args
	 * @return string
	 */
	function extendedPostTags( $args = '' ) {
		$defaults = array(
			'before' => __('Tags: ', 'simpletags'),
			'separator' => ', ',
			'after' => '<br />',
			'post_id' => '',
			'xformat' => __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'notagtext' => __('No tag for this post.', 'simpletags'),
		);
		
		if ( empty($args) ) {
			$defaults['before'] = $this->options['tt_before'];
			$defaults['separator'] = $this->options['tt_separator'];
			$defaults['after'] = $this->options['tt_after'];
			$defaults['notagtext'] = $this->options['tt_notagstext'];
			$args = $this->options['tt_adv_usage'];
		}

		$args = wp_parse_args( $args, $defaults );
		extract($args);
		
		$post_id = (int) $post_id;
		$tags = get_the_tags( $post_id );
		
		if ( empty($tags) ) {
			return $notagtext;
		}
		
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
				
		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'rel="tag"' : '';
		
		foreach ( (array) $tags as $tag ) {
			$element_loop = $xformat;
			$element_loop = str_replace('%tag_link%'	, clean_url(get_tag_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%'	, clean_url(get_tag_feed_link($tag->term_id)), $element_loop);
			$element_loop = str_replace('%tag_id%'		, $tag->term_id, $element_loop);		
			$element_loop = str_replace('%tag_name%'	, str_replace(' ', '&nbsp;', wp_specialchars($tag->name)), $element_loop);
			$element_loop = str_replace('%tag_rel%'		, $rel, $element_loop);
			
			$element_loop = str_replace('%tag_technorati%'	, $this->formatLink( 'technorati', $tag->name ), $element_loop);
			$element_loop = str_replace('%tag_flickr%'		, $this->formatLink( 'flickr', $tag->name ), $element_loop);
			$element_loop = str_replace('%tag_delicious%'	, $this->formatLink( 'delicious', $tag->name ), $element_loop);
			
			$tag_links[] = $element_loop;
		}

		$tag_list = apply_filters( 'the_tags', join( $separator, $tag_links ) );	
		return $before . $tag_list . $after;	
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
	 * This is pretty filthy.  Doing math in hex is much too weird.  It's more likely to work,  this way!
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
		global $wp_query;
		if ( $wp_query->is_tag ) {
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
	
		
	function get_tags( $args = '' ) {
		global $wpdb;
		
		$key = md5( serialize( $args ) );
		if ( $cache = wp_cache_get( 'st_get_tags', 'category' ) ) {
			if ( isset( $cache[ $key ] ) ) {
				return apply_filters('get_tags', $cache[$key], $args);
			}
		}
		
		$tags = $this->get_terms('post_tag', $args);
		if ( empty($tags) ) {
			return array();
		}
		
		$cache[ $key ] = $tags;
		wp_cache_set( 'st_get_tags', $cache, 'category' );
		
		$tags = apply_filters('get_tags', $tags, $args);
		return $tags;
	}	
	
	function get_terms( $taxonomies, $args = '' ) {
		global $wpdb;
	
		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}
	
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! is_taxonomy($taxonomy) ) {
				return new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
			}
		}
	
		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";
	
		$defaults = array(
			'orderby' => 'name',
			'order' => 'ASC',
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
			'pad_counts' => false,
			'limit_days' => 0
		);
			
		$args = wp_parse_args( $args, $defaults );
		$args['number'] = $this->absint( $args['number'] );
		
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
	
		$key = md5( serialize( $args ) . serialize( $taxonomies ) );
		if ( $cache = wp_cache_get( 'get_terms', 'terms' ) ) {
			if ( isset( $cache[ $key ] ) ) {
				return apply_filters('get_terms', $cache[$key], $taxonomies, $args);
			}
		}
	
		if ( $orderby == 'count' ) {
			$orderby = 'tt.count';
		} elseif ( $orderby == 'random' ) {
			$orderby = 'RAND()';
		} else {
			$orderby = 't.name';
		}
		
		// Order
		$order = strtolower($order);
		if ( $order == 'asc' ) {
			$order = 'ASC';
		} else {
			$order = 'DESC';
		}
	
		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$interms = preg_split('/[\s,]+/',$include);
			if ( count($interms) ) {
				foreach ( $interms as $interm ) {
					if (empty($inclusions)) {
						$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
					} else {
						$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
					}
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
			if ( count($exterms) ) {
				foreach ( $exterms as $exterm ) {
					if (empty($exclusions)) {
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					} else {
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
					}
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
	
		if ( '' != $parent ) {
			$parent = (int) $parent;
			$where .= " AND tt.parent = '$parent'";
		}
	
		if ( $hide_empty && !$hierarchical ) {
			$where .= ' AND tt.count > 0';
		}
	
		if ( !empty($number) ) {
			$number = 'LIMIT ' . $number;
		} else {
			$number = '';
		}
	
		if ( 'all' == $fields ) {
			$select_this = 't.*, tt.*';
		} else if ( 'ids' == $fields ) {
			$select_this = 't.term_id';
		} else if ( 'names' == $fields ) {
			$select_this == 't.name';
		}
		
		$limit_days = (int) $limit_days;
		$inner_posts = $limit_days_sql = '';
		if ( $limit_days != 0 ) {
			$inner_posts = "
				INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id 
				INNER JOIN $wpdb->posts AS p ON tr.object_id = p.ID";
			$limitdays_sql = 'AND p.post_date > "' .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). '"';
		}

		$query = "
			SELECT {$select_this}
			FROM {$wpdb->terms} AS t 
			INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id 
			{$inner_posts}
			WHERE tt.taxonomy IN ( {$in_taxonomies} )
			{$limitdays_sql}
			{$where}
			ORDER BY {$orderby} {$order}
			{$number}";
	
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
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = _get_term_children($term->term_id, $terms, $taxonomies[0]);
					foreach ( $children as $child ) {
						if ( $child->count ) {
							continue 2;
						}
					}
	
					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset ( $terms );
	
		$cache[ $key ] = $terms;
		wp_cache_set( 'get_terms', $cache, 'terms' );
	
		$terms = apply_filters('get_terms', $terms, $taxonomies, $args);
		return $terms;
	}
	
	/**
	 * Converts input to an absolute integer
	 * @param mixed $maybeint data you wish to have convered to an absolute integer
	 * @return int an absolute integer
	 */
	function absint( $maybeint ) {
		return abs( intval( $maybeint ) );
	}
}

global $simple_tags;
$simple_tags = new SimpleTags();

// Admin and XML-RPC
if ( is_admin() || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ) {
	require(dirname(__FILE__).'/inc/simple-tags.admin.php');
	$simple_tags_admin = new SimpleTagsAdmin();
}

// Templates functions
require(dirname(__FILE__).'/inc/simple-tags.functions.php');

// Widgets
require(dirname(__FILE__).'/inc/simple-tags.widgets.php');

// Compatibily old STP and UTW(future)
//require(dirname(__FILE__).'/inc/simple-tags.compatibility.php');
?>