<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://www.herewithme.fr/wordpress-plugins/simple-tags
Description: Simple Tags : Extended Tagging with WordPress 2.3
Version: 1.1
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
	var $version = '1.1';

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
			'use_tag_pages' => '1',
			'inc_page_tag_search' => '1',
			'use_tag_links' => '0',
			'use_embed_tags' => '0',
			'start_embed_tags' => '[tags]',
			'end_embed_tags' => '[/tags]',
			'related_posts_feed' => '1',
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
		foreach( $defaultopt as $def_optname => $def_optval ) {
			if ( $optionsFromTable[$def_optname] != '' ) {
				$defaultopt[$def_optname] = $optionsFromTable[$def_optname];
			}
		}

		// Set the class property and unset no used variable
		$this->options = $defaultopt;
		unset($defaultopt);
		unset($optionsFromTable);

		// Determine installation path & url
		$path = basename(dirname(__FILE__));
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

		// Add related posts in feed
		if ( $this->options['related_posts_feed'] == '1' ) {
			add_filter('the_content', array(&$this, 'feedRelatedPosts'), 99);
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
			foreach($posts as $post) {
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
				$query = "
		          SELECT DISTINCT terms.name
		          FROM {$wpdb->term_taxonomy} term_taxonomy, {$wpdb->term_relationships} term_relationships, {$wpdb->posts} posts, {$wpdb->terms} terms
		          WHERE term_taxonomy.taxonomy = 'post_tag'
		          AND term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
		          AND term_relationships.object_id  = posts.ID
		          AND term_taxonomy.term_id = terms.term_id
		          AND (posts.ID IN ('{$postlist}'))";        
				$results = $wpdb->get_col( $query );
				wp_cache_set( crypt($postlist), $results, 'simpletags');
			}
			
			$always_list = trim($this->options['meta_always_include']);
			$always_array = explode(',', $always_list);
			foreach ( $always_array as $keyword ) {
				$results[] = trim($keyword);
			}
			unset($always_list, $always_array);
			
			// Unique keywords
			$results = array_unique($results);		
			
			return htmlentities(utf8_decode(strip_tags(implode(', ', $results))));
		}
		return false;
	}
	
	/**
	 * Display meta keywords
	 *
	 */
	function displayMetaKeywords() {
		$tags_list = $this->generateKeywords();
		if ( $tags_list ) {
			echo "\n\t" . '<meta name="keywords" content="' . $tags_list . '" />' . "\n";
		}
	}

	/**
	 * Add related posts to post content in feeds
	 *
	 * @param string $content
	 * @return string
	 */
	function feedRelatedPosts( $content ) {
		if ( is_feed() ) {
			$content .= $this->relatedPosts();
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
			'title' => __('<h4>Related posts</h4>', 'simpletags'),
			'nopoststext' => __('No related posts.', 'simpletags'),
			'dateformat' => $this->dateformat,
			'xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags')
		);

		$args = wp_parse_args( $user_args, $defaults );
		extract($args);
		
		// Get current post data
		global $post;
		$post_id = (int) $post->ID;

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
			if ( strtolower($order) == 'asc' ) {
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
				$order_sql = "ORDER BY posts.post_title {$order}";
			}
			unset($orderby, $order);

			// include_page
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

			// Posts: title, comments_count, date, permalink, post_id, counter
			global $wpdb;
			$query = "
		        SELECT DISTINCT posts.post_title, posts.comment_count, posts.post_date, posts.ID, COUNT(term_relationships.object_id) as counter
		        FROM {$wpdb->term_taxonomy} term_taxonomy, {$wpdb->term_relationships} term_relationships, {$wpdb->posts} posts
		        WHERE term_taxonomy.taxonomy = 'post_tag'
		        AND term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
		        AND term_relationships.object_id  = posts.ID
		        AND (term_taxonomy.term_id IN ({$taglist}))
		        {$exclude_posts_sql}
		        AND posts.post_status = 'publish'
		        {$restrict_sql}
		        GROUP BY term_relationships.object_id
		        {$order_sql}
		        {$limit_sql}";     

			$results = $wpdb->get_results( $query );
			wp_cache_set( crypt($user_args.'-'.$post_id), $results, 'simpletags');
		}

		if ( !$results ) {
			return $this->outputRelatedPosts( $format, $title, $nopoststext );
		}

		if ( $xformat == '' ) {
			$xformat = __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags');
		}
		
		if ( $dateformat == '' ) {
			$dateformat = $this->dateformat;
		}

		foreach ( (array) $results as $result ) {
			// Replace placeholders
			$element_loop = $xformat;
			
			// Keep for series 1.0.x / To delete for 1.1
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
			
			$output[] = $element_loop;
		}
		return $this->outputRelatedPosts( $format, $title, $output );
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
			'notagstext' => __('No tags.', 'simpletags'),
			'xformat' => __('<a href="%tag_link%" class="tag-link-%tag_id%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'color' => true,
			'maxcolor' => '#000000',
			'mincolor' => '#CCCCCC',
			'title' => __('<h4>Tag Cloud</h4>', 'simpletags')
		);

		$args = wp_parse_args( $args, $defaults );
		$tags = get_tags( array_merge($args, array('orderby' => 'count', 'order' => 'DESC')) ); // Always query top tags
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

		// Calcul font step
		$min_count = min($counts);
		$spread = max($counts) - $min_count;
		if ( $spread <= 0 ) {
			$spread = 1;
		}
		$font_spread = $largest - $smallest;
		if ( $font_spread <= 0 ) {
			$font_spread = 1;
		}
		$font_step = $font_spread / $spread;

		// SQL cannot save you; this is a second (potentially different) sort on a subset of data.
		if ( 'name' == $orderby ) {
			uksort($counts, 'strnatcasecmp');
		} elseif ( 'random' == $orderby ) {
			$counts = $this->shuffleArray($counts);
		} else {
			asort($counts);
		}

		if ( 'DESC' == $order ) {
			$counts = array_reverse( $counts, true );
		}

		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? ' rel="tag"' : '';

		if ( empty($xformat) ) {
			$xformat = '<a href="%tag_link%" class="tag-link-%tag_id%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>';
		}

		$output = array();
		foreach ( $counts as $tag => $count ) {
			$font_size = round( ( $smallest + (($count-$min_count) * $font_step)), 2 );

			$element_loop = $xformat;
			$element_loop = str_replace('%tag_link%'	, clean_url($tag_links[$tag]), $element_loop);
			$element_loop = str_replace('%tag_feed%'	, clean_url(get_tag_feed_link($tag_ids[$tag])), $element_loop);
			$element_loop = str_replace('%tag_id%'		, $tag_ids[$tag], $element_loop);
			$element_loop = str_replace('%tag_count%'	, $count, $element_loop);
			$element_loop = str_replace('%tag_size%'	, 'font-size:'.$font_size.$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%'	, 'color:'.$this->getColorByScale((($font_size - $smallest)*100) / ($largest - $smallest),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_name%'	, str_replace(' ', '&nbsp;', wp_specialchars( $tag )), $element_loop);
			$element_loop = str_replace('%tag_rel%'		, $rel, $element_loop);
			$output[] = $element_loop;
		}

		return $this->outputExtendedTagCloud( $format, $title, $output );
	}
	
	function shuffleArray( $shuffle_me ) {
		$randomized_keys = array_rand( $shuffle_me, count($shuffle_me) );
		foreach( $randomized_keys as $current_key ) {
			$shuffled_me[$current_key] = $shuffle_me[$current_key];
		}
		return $shuffled_me;
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
		return "\n" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://www.herewithme.fr/wordpress-plugins/simple-tags -->' ."\n\t". $title ."\n\t". $return ."\n";
	}

	/**
	 * Delete embedded tags
	 *
	 * @param string $content
	 * @return string
	 */
	function filterEmbedTags( $content ) {
		$tagstart = $this->options['start_embed_tags'];
		$len_tagstart = strlen($tagstart);

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
	 * Generate related links (future)
	 *
	 * @param string $args
	 */
	function relatedLinks( $args = '' ) {
		// Links number
	}

	/**
	 * Update an option value  -- note that this will NOT save the options.
	 *
	 * @param string $optname
	 * @param string $optval
	 */
	function setOption($optname, $optval) {
		$this->options[$optname] = $optval;
	}

	/**
	 * Save all current options
	 *
	 */
	function saveOptions() {
		update_option($this->db_options, $this->options);
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		// write option values to database
		update_option($this->db_options, $this->default_options);
		// set class options
		$this->options = $this->default_options;
	}

	/**
	 * Update taxonomy counter for post AND page
	 *
	 * @param array $terms
	 */
	function _update_post_and_page_term_count( $terms ) {
		global $wpdb;

		foreach ( $terms as $term ) {
			$count = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type IN ('page', 'post') AND term_taxonomy_id = '$term'");
			$wpdb->query("UPDATE $wpdb->term_taxonomy SET count = '$count' WHERE term_taxonomy_id = '$term'");
		}
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