<?php
Class SimpleTags {
	var $version = '1.5.6 - 1.3.9.5';

	var $info;
	var $options;
	var $default_options;
	var $db_options = 'simpletags';
	var $dateformat;

	// Stock Post ID for current view
	var $posts = array();
	var $tags_currentposts = array();
	var $link_tags = 'null';
	
	// WP Object Cache
	var $use_cache = false;

	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {		
		// Determine installation path & url
		$path = str_replace('\\','/',dirname(__FILE__));		
		$path = substr($path, strpos($path, 'plugins') + 8, strlen($path));

		$info['siteurl'] = get_option('siteurl');
		if ( $this->isMuPlugin() ) {
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
	
		// Localization
		$locale = get_locale();
		if ( !empty( $locale ) ) {
			$mofile = str_replace('/2.3', '', $this->info['install_dir']).'/languages/simpletags-'.$locale.'.mo';
			load_textdomain('simpletags', $mofile);
		}
		
		// Options
		$default_options = array(
			// General
			'inc_page_tag_search' => 1,
			'allow_embed_tcloud' => 0,
			'auto_link_tags' => 0,
			'auto_link_min' => 1,
			'auto_link_case' => 1,
			'no_follow' => 0,
			// Administration
			'use_tag_pages' => 1,
			'use_click_tags' => 1,
			'use_suggested_tags' => 1,
			'use_autocompletion' => 1,
			// Embedded Tags			
			'use_embed_tags' => 0,
			'start_embed_tags' => '[tags]',
			'end_embed_tags' => '[/tags]',
			// Related Posts
			'rp_feed' => 0,
			'rp_embedded' => 'no',
			'rp_order' => 'count-desc',
			'rp_limit_qty' => 5,
			'rp_notagstext' => __('No related posts.', 'simpletags'),
			'rp_title' => __('<h4>Related posts</h4>', 'simpletags'),
			'rp_xformat' => __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags'),
			'rp_adv_usage' => '',
			// Tag cloud
			'cloud_selection' => 'count-desc',
			'cloud_sort' => 'random',
			'cloud_limit_qty' => 45,
			'cloud_notagstext' => __('No tags.', 'simpletags'),
			'cloud_title' => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'cloud_format' => 'flat',
			'cloud_xformat' => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'cloud_max_color' => '#000000',
			'cloud_min_color' => '#CCCCCC',
			'cloud_max_size' => 22,
			'cloud_min_size' => 8,
			'cloud_unit' => 'pt',
			'cloud_inc_cats' => 0,
			'cloud_adv_usage' => '',
			// The tags
			'tt_feed' => 0,
			'tt_embedded' => 'no',
			'tt_separator' => ', ',
			'tt_before' => __('Tags: ', 'simpletags'),
			'tt_after' => '<br />',
			'tt_notagstext' => __('No tag for this post.', 'simpletags'),
			'tt_number' => 0,
			'tt_inc_cats' => 0,
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
			'meta_autoheader' => 1,
			'meta_always_include' => '',
			'meta_keywords_qty' => 0,
			// Auto tags
			'use_auto_tags' => 0,
			'at_all' => 0,
			'at_empty' => 0,
			'auto_list' => ''
		);

		// Set class property for default options
		$this->default_options = $default_options;

		// Get options from WP options
		$options_from_table = get_option( $this->db_options );

		// Update default options by getting not empty values from options table
		foreach( (array) $default_options as $default_options_name => $default_options_value ) {
			if ( !is_null($options_from_table[$default_options_name]) ) {
				if ( is_int($default_options_value) ) {
					$default_options[$default_options_name] = (int) $options_from_table[$default_options_name];
				} else {
					$default_options[$default_options_name] = $options_from_table[$default_options_name];
				}
			}
		}

		// Set the class property and unset no used variable
		$this->options = $default_options;
		unset($default_options);
		unset($options_from_table);
		unset($default_options_value);
		
		// Use WP Object ? Or not ?
		global $wp_object_cache;
		$this->use_cache = ( $wp_object_cache->cache_enabled === true ) ? true : false;

		// Set date for class
		$this->dateformat = get_option('date_format');

		// Add pages in WP_Query
		if ( $this->options['use_tag_pages'] == 1 ) {
			// Remove default taxonomy
			global $wp_taxonomies;
			unset($wp_taxonomies['post_tag']);
			// Add the same taxonomy with an another callback who allow page and post
			register_taxonomy( 'post_tag', 'post', array('hierarchical' => false, 'update_count_callback' => array(&$this, '_update_post_and_page_term_count')) );
			add_filter('posts_where', array(&$this, 'prepareQuery'));
		}

		// Remove embedded tags in posts display
		if ( $this->options['use_embed_tags'] == 1 ) {
			add_filter('the_content', array(&$this, 'filterEmbedTags'), 0);
		}

		// Add related posts in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $this->options['tt_embedded'] != 'no' || $this->options['tt_feed'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlinePostTags'), 999992);
		}

		// Add post tags in post ( all / feedonly / blogonly / homeonly / singularonly / singleonly / pageonly /no )
		if ( $this->options['rp_embedded'] != 'no' || $this->options['rp_feed'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlineRelatedPosts'), 999993);
		}

		// Embedded tag cloud
		if ( $this->options['allow_embed_tcloud'] == 1 ) {
			add_filter('the_content', array(&$this, 'inlineTagCloud'));
		}

		// Stock Posts ID (useful for autolink and metakeywords)
		add_filter( 'the_posts', array(&$this, 'getPostIds') );

		// Add keywords to header
		if ( ( $this->options['meta_autoheader'] == 1 && !class_exists('All_in_One_SEO_Pack') && apply_filters('st_meta_header', true) ) ) {
			add_action('wp_head', array(&$this, 'displayMetaKeywords'));
		}

		// Auto link tags
		if ( $this->options['auto_link_tags'] == '1' ) {
			add_filter('the_content', array(&$this, 'autoLinkTags'), 9);
		}
		return true;
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
		foreach ( (array) $this->tags_currentposts as $term ) {
			if  ( $term->count >= $auto_link_min ) {
	 			$this->link_tags[$term->name] = clean_url(get_tag_link( $term->term_id ));
			}
		}
		return;
	}
		
	function autoLinkTags( $content = '' ) {
		// ST Prefix/Suffix content
		$content = '<div>'.$content.'</div>';
		
		// Get currents tags if no exists
		if ( $this->link_tags == 'null' ) {
			$this->prepareAutoLinkTags();
		}
		
		// HTML Rel (tag/no-follow)
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $this->options['no_follow'];
		if ( $no_follow == 1 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		// only continue if the database actually returned any links
		if ( isset($this->link_tags) && is_array($this->link_tags) && count($this->link_tags) > 0 ) {
			$must_tokenize  = TRUE;         // will perform basic tokenization
			$tokens         = NULL;         // two kinds of tokens: markup and text
			
			$case = ( $this->options['auto_link_case'] == 1 ) ? 'i' : '';
			
			foreach ( (array) $this->link_tags as $term_name => $term_link ) {
				$filtered     = "";           // will filter text token by token
				$match        = "/\b" . preg_quote($term_name, "/") . "\b/".$case;
				$substitute   = '<a href="'.$term_link.'" class="st_tag internal_tag" '.$rel.' title="'. attribute_escape( sprintf( __('Posts tagged with %s', 'simpletags'), $term_name ) )."\">$0</a>";
				
				 // for efficiency only tokenize if forced to do so
				if ( $must_tokenize ) {
					// this regexp is taken from PHP Markdown by Michel Fortin: http://www.michelf.com/projects/php-markdown/
					$comment                = '(?s:<!(?:--.*?--\s*)+>)|';
					$processing_instruction = '(?s:<\?.*?\?>)|';
					$tag = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';
					$markup         = $comment . $processing_instruction . $tag;
					$flags          = PREG_SPLIT_DELIM_CAPTURE;
					$tokens         = preg_split("{($markup)}", $content, -1, $flags);
					$must_tokenize  = FALSE;
				}
				
				// there should always be at least one token, but check just in case
				if ( isset($tokens) && is_array($tokens) && count($tokens) > 0 ) {
					$i = 0;
					foreach ($tokens as $token) {
						if (++$i % 2 && $token != '') { // this token is (non-markup) text						
							if ($anchor_level == 0) { // linkify if not inside anchor tags						
								if ( preg_match($match, $token) ) { // use preg_match for compatibility with PHP 4							
									$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
									$must_tokenize = TRUE;  // re-tokenize next time around
								}
							}
						}
						else { // this token is markup					
							if ( preg_match("#<\s*a\s+[^>]*>#i", $token) ) { // found <a ...>
								$anchor_level++;
							} elseif ( preg_match("#<\s*/\s*a\s*>#i", $token) ) { // found </a>
								$anchor_level--;
							}
						}
						$filtered .= $token; // this token has now been filtered
					}
					$content = $filtered; // filtering completed for this link
				}
			}
		}
		
		// Remove ST Prefix/Suffix content
		$content = substr($content, 5, strlen($content) - 11);
		
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

			if ( $this->use_cache === true ) { // Use cache
				// Generate key cache
				$key = md5(maybe_serialize($postlist));
	
				// Get cache if exist
				if ( $cache = wp_cache_get( 'generate_keywords', 'simpletags' ) ) {
					if ( isset( $cache[$key] ) ) {
						$this->tags_currentposts = $cache[$key];
						return;
					}
				}
			}

			// If cache not exist, get datas and set cache
			global $wpdb;			
			$results = $wpdb->get_results("
				SELECT t.name AS name, t.term_id AS term_id, tt.count AS count
				FROM {$wpdb->term_relationships} AS tr
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				INNER JOIN {$wpdb->terms} AS t ON (tt.term_id = t.term_id)
				WHERE tt.taxonomy = 'post_tag'
				AND ( tr.object_id IN ('{$postlist}') )
				GROUP BY t.term_id
				ORDER BY tt.count DESC");

			if ( $this->use_cache === true ) { // Use cache
				$cache[$key] = $results;
				wp_cache_set('generate_keywords', $cache, 'simpletags');
			}

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
		foreach ( (array) $this->tags_currentposts as $term ) {
			$results[] = $term->name;
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
		
		// Return if empty
		if ( empty($results) ) {
			return '';
		}
		
		// Limit to max quantity if set
		$number = (int) $this->options['meta_keywords_qty'];
		if ( $number != 0 && is_array($results) && !empty($results) && count($results) > 1 ) {
			$results = $this->randomArray($results); // Randomize keywords
			$results = array_slice( $results, 0, $number );
		}

		return strip_tags(implode(', ', $results));
	}

	/**
	 * Display meta keywords
	 *
	 */
	function displayMetaKeywords() {
		$terms_list = $this->generateKeywords();
		if ( !empty($terms_list) ) {
			echo "\n\t" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". '<meta name="keywords" content="' . $terms_list . '" />' ."\n";
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
			switch ( $this->options['tt_embedded'] ) {
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
			return ( $content . $this->extendedPostTags( '', false ) );
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
		$post_id = (int) $post_id;
		if ( $post_id == 0 ) {
			global $post;
			$object_id = (int) $post->ID;
		}

		// Get cache if exist
		$results = false;		
		if ( $this->use_cache === true ) { // Use cache
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
				SELECT p.post_title, p.comment_count, p.post_date, p.ID, COUNT(tr.object_id) AS counter {$select_excerpt} {$select_gp_concat}
				FROM {$wpdb->posts} AS p
				INNER JOIN {$wpdb->term_relationships} AS tr ON (p.ID = tr.object_id)
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id)
				WHERE tt.taxonomy = 'post_tag'
				AND (tt.term_id IN ({$tag_list}))
				{$exclude_posts_sql}
				AND p.post_status = 'publish'
				AND p.post_date_gmt < '".current_time('mysql')."'
				{$limit_days_sql}
				{$restrict_sql}
				GROUP BY tr.object_id
				ORDER BY {$order_by}
				{$limit_sql}");

			if ( $this->use_cache === true ) { // Use cache
				$cache[$key] = $results;
				wp_cache_set('related_posts', $cache, 'simpletags');
			}
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
			
			$post_title = apply_filters( 'the_title', $result->post_title );
			$element_loop = str_replace('%post_date%', mysql2date($dateformat, $result->post_date), $element_loop);
			$element_loop = str_replace('%post_permalink%', get_permalink($result->ID), $element_loop);			
			$element_loop = str_replace('%post_title%', $post_title, $element_loop);
			$element_loop = str_replace('%post_title_attribute%', wp_specialchars(strip_tags($post_title)), $element_loop);		
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
		$rel = '';
		
		global $wp_rewrite; 
		$rel .= ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		
		$no_follow = (int) $this->options['no_follow'];
		if ( $no_follow == 1 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}

		$output = '';
		foreach ( $terms as $term ) {
			$output .= '<a href="'.get_tag_link($term->term_id).'" title="'.attribute_escape(sprintf( __ngettext('%d topic', '%d topics', $term->count, 'simpletags'), $term->count )).'" '.$rel.'>'.wp_specialchars($term->name).'</a>, ';
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

		// Get cache if exist
		$related_tags = false;
		if ( $this->use_cache === true ) { // Use cache
			// Generate key cache
			$key = md5(maybe_serialize($user_args.$slugs));
			
			if ( $cache = wp_cache_get( 'related_tags', 'simpletags' ) ) {
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
			
			if ( $this->use_cache === true ) { // Use cache
				$cache[$key] = $related_tags;
				wp_cache_set('related_tags', $cache, 'simpletags');
			}
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
		if ( $no_follow == 1 ) { // No follow ?
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
			$element_loop = str_replace('%tag_name%', wp_specialchars( $tag->name ), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', wp_specialchars(strip_tags($tag->name)), $element_loop);			
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);			
			$element_loop = str_replace('%tag_link_add%', $this->getAddTagToLink( $current_slugs, $tag->slug, $url_tag_sep ), $element_loop);

			$output[] = $element_loop;
		}
		unset($related_tags, $tag, $element_loop);
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
		if ( $no_follow == 1 ) { // No follow ?
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
			$element_loop = str_replace('%tag_name%', wp_specialchars( $term->name ), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', wp_specialchars(strip_tags($term->name)), $element_loop);			
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
		return apply_filters('st_remove_tag_link', clean_url($taglink));
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
			'inc_cats' => 0,
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
		$defaults['inc_cats'] = $this->options['cloud_inc_cats'];		
		
		if ( empty($args) ) {				
			$args = $this->options['cloud_adv_usage'];
		}
		$args = wp_parse_args( $args, $defaults );

		// Get categories ?
		$inc_cats = (int) $args['inc_cats'];
		$taxonomy = ( $inc_cats == 0 ) ? 'post_tag' : array('post_tag', 'category');

		// Get terms
		$terms = $this->getTags( $args, $this->use_cache, $taxonomy );
		extract($args); // Params to variables

		if ( empty($terms) ) {
			return $this->outputContent( 'st-tag-cloud', $format, $title, $notagstext, $copyright );
		}

		$counts = $term_links = $term_ids = $taxonomies = array();
		foreach ( (array) $terms as $term ) {
			$counts[$term->name] = $term->count;
			if ( $term->taxonomy == 'post_tag' ) { // Tag
				$term_links[$term->name] = get_tag_link( $term->term_id );
			} else { // Category
				$term_links[$term->name] = get_category_link( $term->term_id );
			}
			$term_ids[$term->name] = $term->term_id;
			$taxonomies[$term->name] = $term->taxonomy;
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
		if ( $no_follow == 1 ) { // No follow ?
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

		// Order terms before output
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
		foreach ( (array) $counts as $term => $count ) {
			$scale_result = (int) (($count - $minval) * $scale + $minout);

			$element_loop = $xformat;

			$element_loop = str_replace('%tag_link%', clean_url($term_links[$term]), $element_loop);
			if ( $taxonomies[$term] == 'post_tag' ) { // Tag post
				$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($term_ids[$term])), $element_loop);
			} else { // Category
				$element_loop = str_replace('%tag_feed%', clean_url(get_category_rss_link(false, $term_ids[$term], '')), $element_loop);				
			}			
			$element_loop = str_replace('%tag_id%', $term_ids[$term], $element_loop);
			$element_loop = str_replace('%tag_count%', $count, $element_loop);
			$element_loop = str_replace('%tag_size%', 'font-size:'.round(($scale_result - $scale_min)*($largest-$smallest)/($scale_max - $scale_min) + $smallest, 2).$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%', 'color:'.$this->getColorByScale(round(($scale_result - $scale_min)*(100)/($scale_max - $scale_min), 2),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_name%', wp_specialchars( $term ), $element_loop);
			$element_loop = str_replace('%tag_name_attribute%', wp_specialchars(strip_tags($term)), $element_loop);			
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
			$element_loop = str_replace('%tag_scale%', $scale_result, $element_loop);
			$element_loop = str_replace('%tag_technorati%', $this->formatLink( 'technorati', $term ), $element_loop);
			$element_loop = str_replace('%tag_flickr%', $this->formatLink( 'flickr', $term ), $element_loop);
			$element_loop = str_replace('%tag_delicious%', $this->formatLink( 'delicious', $term ), $element_loop);

			$output[] = $element_loop;
		}
		unset($counts, $term_links, $term_ids, $taxonomies, $element_loop);
		return $this->outputContent( 'st-tag-cloud', $format, $title, $output, $copyright );
	}

	/**
	 * Randomize an array and keep association
	 *
	 * @param array $data
	 * @return array
	 */
	function randomArray( $data_in = array() ) {
		if ( empty($data_in) ) {
			return $data_in;
		}
		
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
	function formatLink( $type = '', $term_name = '' ) {
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
			'post_id' => 0,
			'no_follow' => 0,
			'inc_cats' => 0,
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
		$defaults['inc_cats'] = $this->options['tt_inc_cats'];		
		$defaults['xformat'] = $this->options['tt_xformat'];
		
		if ( empty($args) ) {
			$args = $this->options['tt_adv_usage'];
		}

		$args = wp_parse_args( $args, $defaults );
		extract($args);

		// Choose post ID
		$post_id = (int) $post_id;
		if ( $post_id != 0 ) {
			$id = (int) $post_id;
		} else {
			global $post;
			$id = (int) $post->ID;
		}
		
		// Get categories ?
		$inc_cats = (int) $args['inc_cats'];
		$taxonomy = ( $inc_cats == 0 ) ? 'post_tag' : array('post_tag', 'category');
	
		// Get terms	
		$terms = apply_filters( 'get_the_tags', wp_get_object_terms($id, $taxonomy) );

		// If no terms, return text nothing.
		if ( empty($terms) ) {
			return $notagtext;
		}

		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			$terms = $this->randomArray($terms); // Randomize terms
			$terms = array_slice( $terms, 0, $number );
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
		if ( $no_follow == 1 ) { // No follow ?
			$rel .= ( empty($rel) ) ? 'nofollow' : ' nofollow';	
		}
		
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		foreach ( (array) $terms as $term ) {
			$element_loop = $xformat;
			if ( $term->taxonomy == 'post_tag' ) { // Tag
				$element_loop = str_replace('%tag_link%', clean_url(get_tag_link($term->term_id)), $element_loop);
				$element_loop = str_replace('%tag_feed%', clean_url(get_tag_feed_link($term->term_id)), $element_loop);
			} else { // Category
				$element_loop = str_replace('%tag_link%', clean_url(get_category_link($term->term_id)), $element_loop);
				$element_loop = str_replace('%tag_feed%', clean_url(get_category_rss_link(false, $term->term_id, '')), $element_loop);
			}
			$element_loop = str_replace('%tag_id%', $term->term_id, $element_loop);
			$element_loop = str_replace('%tag_name%', wp_specialchars($term->name), $element_loop);
			$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
			$element_loop = str_replace('%tag_count%', $term->count, $element_loop);

			$element_loop = str_replace('%tag_technorati%', $this->formatLink( 'technorati', $term->name ), $element_loop);
			$element_loop = str_replace('%tag_flickr%', $this->formatLink( 'flickr', $term->name ), $element_loop);
			$element_loop = str_replace('%tag_delicious%', $this->formatLink( 'delicious', $term->name ), $element_loop);

			$output[] = $element_loop;
		}
		unset($terms, $term, $element_loop);
		$output = apply_filters( 'the_tags', implode($separator, $output) );

		if ( $copyright === true )
			return "\n\t" . '<!-- Generated by Simple Tags ' . $this->version . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". $before . $output . $after ."\n";
		else
			return "\n\t". $before . $output . $after ."\n";
	}

	/**
	 * Delete embedded tags
	 *
	 * @param string $content
	 * @return string
	 */
	function filterEmbedTags( $content ) {
		$tag_start = $this->options['start_embed_tags'];
		$tag_end = $this->options['end_embed_tags'];
		$len_tagend = strlen($tag_end);

		while ( strpos($content, $tag_start) != false && strpos($content, $tag_end) != false ) {
			$pos1 = strpos($content, $tag_start);
			$pos2 = strpos($content, $tag_end);
			$content = str_replace(substr($content, $pos1, ($pos2 - $pos1 + $len_tagend)), '', $content);
		}
		return $content;
	}

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
	 *  - Limit category
	 *  - Limit days
	 *  - Selection restrict
	 *  - Min usage
	 *
	 * @param string|array $taxonomies
	 * @param string $args
	 * @return array
	 */
	function getTerms( $taxonomies, $args = '', $skip_cache = false ) {
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

		if ( $skip_cache != true ) {
			// Get cache if exist
			$key = md5( serialize( $args ) . serialize( $taxonomies ) );
			if ( $cache = wp_cache_get( 'get_terms', 'terms' ) ) {
				if ( isset( $cache[$key] ) ) {
					return apply_filters('get_terms', $cache[$key], $taxonomies, $args);
				}
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

		if ( $skip_cache != true ) {
			$cache[$key] = $terms;
			wp_cache_set( 'get_terms', $cache, 'terms' );
		}

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
	
	/**
	 * Test if local installation is mu-plugin or a classic plugin
	 *
	 * @return boolean
	 */
	function isMuPlugin() {
		if ( strpos(dirname(__FILE__), 'mu-plugins') ) {
			return true;
		}
		return false;
	}
}

// Init ST
global $simple_tags;
function st_init() {
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
		$simple_tags_admin = new SimpleTagsAdmin( $simple_tags->default_options, $simple_tags->version, $simple_tags->info );
		
		// Installation
		register_activation_hook(__FILE__, array(&$simple_tags_admin, 'installSimpleTags') );
	}

	// Templates functions
	require(dirname(__FILE__).'/inc/simple-tags.functions.php');

	// Widgets
	require(dirname(__FILE__).'/inc/simple-tags.widgets.php');
}
add_action('plugins_loaded', 'st_init');
?>