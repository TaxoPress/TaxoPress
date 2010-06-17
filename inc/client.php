<?php
class SimpleTags {
	var $options;
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
		$this->options = (array) include( dirname(__FILE__) . '/default.options.php' );
		
		// Get options from WP options
		$options_from_table = get_option( STAGS_OPTIONS_NAME );
		
		// Update default options by getting not empty values from options table
		foreach( (array) $this->options as $key => $value ) {
			if ( isset($options_from_table[$key]) && !is_null($options_from_table[$key]) ) {
				$this->options[$key] = $options_from_table[$key];
			}
		}
		
		// Clean memory
		$options_from_table = array();
		unset($options_from_table, $value);
		
		// Set date for class
		$this->dateformat = get_option('date_format');
		
		// Add pages in WP_Query
		if ( $this->options['use_tag_pages'] == 1 ) {
			remove_action( 'init', 'create_initial_taxonomies' ); // highest priority
			add_action( 'init', array(&$this, 'createInitialTaxonomies'), 0 ); // Load this function instead initial register taxnomy
			
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
			add_shortcode( 'st_tag_cloud', array(&$this, 'inlineTagCloud') );
			add_shortcode( 'st-tag-cloud', array(&$this, 'inlineTagCloud') );
			add_filter(    'the_content' , array(&$this, 'old_inlineTagCloud'));
		}
		
		// Stock Posts ID (useful for autolink and metakeywords)
		add_filter( 'the_posts', array(&$this, 'getPostIds') );
		
		// Add keywords to header
		if ( ( $this->options['meta_autoheader'] == 1 && !class_exists('Platinum_SEO_Pack') && !class_exists('All_in_One_SEO_Pack') && apply_filters('st_meta_header', true) ) ) {
			add_action('wp_head', array(&$this, 'outputMetaKeywords'));
		}
		
		// Auto link tags
		if ( $this->options['auto_link_tags'] == '1' ) {
			add_filter('the_content', array(&$this, 'autoLinkTags'), 12);
		}
		return true;
	}
	
	/**
	 * Creates the initial taxonomies when 'init' action is fired.
	 */
	function createInitialTaxonomies() {
		register_taxonomy( 'category', 'post', array('hierarchical' => true, 'update_count_callback' => '_update_post_term_count', 'label' => __('Categories'), 'query_var' => false, 'rewrite' => false) ) ;
		register_taxonomy( 'post_tag', 'post', array('hierarchical' => false, 'update_count_callback' => array(&$this, '_update_post_and_page_term_count'), 'label' => __('Post Tags'), 'query_var' => false, 'rewrite' => false) ) ;
		register_taxonomy( 'link_category', 'link', array('hierarchical' => false, 'label' => __('Categories'), 'query_var' => false, 'rewrite' => false) ) ;
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
			if ( $term->count >= $auto_link_min ) {
				$this->link_tags[$term->name] = esc_url(get_tag_link( $term->term_id ));
			}
		}
		
		return true;
	}
	
	/**
	 * Replace text by link to tag
	 *
	 * @param string $content
	 * @return string
	 */
	function autoLinkTags( $content = '' ) {
		// Get currents tags if no exists
		if ( $this->link_tags == 'null' ) {
			$this->prepareAutoLinkTags();
		}
		
		// Shuffle array
		$this->randomArray($this->link_tags);

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $this->options['no_follow'] );

		// only continue if the database actually returned any links
		if ( isset($this->link_tags) && is_array($this->link_tags) && count($this->link_tags) > 0 ) {
			
			// Limit array
			if ( (int) $this->options['auto_link_max_by_post'] != 0 ) {
				$this->link_tags = array_slice($this->link_tags, 0, (int) $this->options['auto_link_max_by_post']);
			}
			
			$must_tokenize = TRUE; // will perform basic tokenization
			$tokens = NULL; // two kinds of tokens: markup and text
			
			// Case option ?
			$case = ( $this->options['auto_link_case'] == 1 ) ? 'i' : '';
			
			// Prepare exclude terms array
			$excludes_terms = explode( ',', $this->options['auto_link_exclude'] );
			if ( $excludes_terms == false ) {
				$excludes_terms = array();
			} else {
				$excludes_terms = array_filter($excludes_terms, array(&$this, 'deleteEmptyElement'));
				$excludes_terms = array_unique($excludes_terms);
			}
			
			foreach ( (array) $this->link_tags as $term_name => $term_link ) {
				if ( in_array( $term_name, (array) $excludes_terms ) ) {
					continue;
				}
				
				$filtered = ''; // will filter text token by token
				$match = "/\b" . preg_quote($term_name, "/") . "\b/".$case;
				$substitute = '<a href="'.$term_link.'" class="st_tag internal_tag" '.$rel.' title="'. esc_attr( sprintf( __('Posts tagged with %s', 'simpletags'), $term_name ) )."\">$0</a>";
				
				// for efficiency only tokenize if forced to do so
				if ( $must_tokenize ) {
					// this regexp is taken from PHP Markdown by Michel Fortin: http://www.michelf.com/projects/php-markdown/
					$comment = '(?s:<!(?:--.*?--\s*)+>)|';
					$processing_instruction = '(?s:<\?.*?\?>)|';
					$tag = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';
					
					$markup = $comment . $processing_instruction . $tag;
					$flags = PREG_SPLIT_DELIM_CAPTURE;
					$tokens = preg_split("{($markup)}", $content, -1, $flags);
					$must_tokenize = false;
				}
				
				// there should always be at least one token, but check just in case
				$anchor_level = 0;
				if ( isset($tokens) && is_array($tokens) && count($tokens) > 0 ) {
					$i = 0;
					foreach ($tokens as $token) {
						if (++$i % 2 && $token != '') { // this token is (non-markup) text
							if ($anchor_level == 0) { // linkify if not inside anchor tags
								if ( preg_match($match, $token) ) { // use preg_match for compatibility with PHP 4
									$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
									$must_tokenize = true; // re-tokenize next time around
								}
							}
						} else { // this token is markup
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
		
		return $content;
	}
	
	/**
	 * trim and remove empty element
	 *
	 * @param string $element
	 * @return string
	 */
	function deleteEmptyElement( &$element ) {
		$element = stripslashes($element);
		$element = trim($element);
		if ( !empty($element) ) {
			return $element;
		}
	}
	
	/**
	 * Replace marker by a tag cloud in post content
	 * Deprecated
	 *
	 * @param string $content
	 * @return string
	 */
	function old_inlineTagCloud( $content = '' ) {
		if ( strpos($content, '<!--st_tag_cloud-->') ) {
			$content = str_replace('<!--st_tag_cloud-->', $this->extendedTagCloud( '', false ), $content);
		}
		return $content;
	}
	
	/**
	 * Replace marker by a tag cloud in post content, use ShortCode
	 *
	 * @param array $atts
	 * @return string
	 */
	function inlineTagCloud( $atts ) {
		extract(shortcode_atts(array('param' => ''), $atts));
		
		return $this->extendedTagCloud( trim($param), false );
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
				$this->posts[] = (int) $post->ID;
			}
			
			$this->posts = array_unique( $this->posts );
		}
		return $posts;
	}
	
	/**
	 * Get tags from current post views
	 *
	 * @return boolean
	 */
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
					return true;
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
			
			$cache[$key] = $results;
			wp_cache_set('generate_keywords', $cache, 'simpletags');
			
			$this->tags_currentposts = $results;
			unset($results, $key);
		}
		return true;
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
			shuffle($results); // Randomize keywords
			$results = array_slice( $results, 0, $number );
		}
		
		return strip_tags(implode(', ', $results));
	}
	
	/**
	 * Display meta keywords
	 *
	 */
	function outputMetaKeywords() {
		$terms_list = $this->generateKeywords();
		if ( !empty($terms_list) ) {
			echo "\n\t" . '<!-- Generated by Simple Tags ' . STAGS_VERSION . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". '<meta name="keywords" content="' . $terms_list . '" />' ."\n";
			return true;
		}
		return false;
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
		if (!is_array($user_args)) $user_args = strtr($user_args, $markers);
		
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
		// Generate key cache
		$key = md5(maybe_serialize($user_args).'-'.$object_id);
		
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
			
			// Group Concat check if post_relatedtags is used by xformat...
			$select_gp_concat = '';
			if ( strpos($xformat,'%post_relatedtags%') || $min_shared > 1 ) {
				$select_gp_concat = ', GROUP_CONCAT(tt.term_id) as terms_id';
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
			
			$cache[$key] = $results;
			wp_cache_set('related_posts', $cache, 'simpletags');
		}
		
		if ( $format == 'object' || $format == 'array' ) {
			return $results;
		} elseif ( $results === false || empty($results) ) {
			return $this->outputContent( 'st-related-posts', $format, $title, $nopoststext, $copyright );
		}
		
		if ( empty($dateformat) ) {
			$dateformat = $this->dateformat;
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
			$element_loop = str_replace('%post_title_attribute%', esc_html(strip_tags($post_title)), $element_loop);
			$element_loop = str_replace('%post_comment%', (int) $result->comment_count, $element_loop);
			$element_loop = str_replace('%post_tagcount%', (int) $result->counter, $element_loop);
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
		foreach ( (array) $terms as $term ) {
			$output .= '<a href="'.get_tag_link($term->term_id).'" title="'.esc_attr(sprintf( _n('%d topic', '%d topics', (int) $term->count, 'simpletags'), $term->count )).'" '.$rel.'>'.esc_html($term->name).'</a>, ';
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
		// Generate key cache
		$key = md5(maybe_serialize($user_args).maybe_serialize($slugs));
		$cache = wp_cache_get( 'related_tags', 'simpletags' );
		if ( $cache ) {
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
			
			$cache[$key] = $related_tags;
			wp_cache_set('related_tags', $cache, 'simpletags');
		}
		
		if ( empty($related_tags) ) {
			return $this->outputContent( 'st-related-tags', $format, $title, $notagstext, true );
		} elseif ( $format == 'object' || $format == 'array' ) {
			return $related_tags;
		}
		
		// Limit to max quantity if set
		if ( (int) $number != 0 ) {
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
		
		$home_link = untrailingslashit( get_bloginfo('home') );
		if ( empty($taglink) ) { // No permalink
			$taglink = $home_link . '/?tag=' . $slugs;
		} else { // Custom permalink
			$taglink = $home_link . user_trailingslashit( str_replace('%tag%', $slugs, $taglink), 'category');
		}
		
		return apply_filters('st_add_tag_link', esc_url($taglink));
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
		$slugs = implode( $separator, (array) $current_slugs );
		
		global $wp_rewrite;
		$taglink = $wp_rewrite->get_tag_permastruct();
		
		$home_link = untrailingslashit( get_bloginfo( 'home' ) );
		if ( empty($taglink) ) { // No permalink
			$taglink = $home_link . '/?tag=' . $slugs;
		} else { // Custom permalink
			$taglink = $home_link . user_trailingslashit( str_replace('%tag%', $slugs, $taglink), 'category');
		}
		
		return apply_filters( 'st_remove_tag_link', esc_url($taglink) );
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
			'size'		  => 'true',
			'smallest' 	  => 8,
			'largest' 	  => 22,
			'unit' 		  => 'pt',
			'color' 	  => 'true',
			'maxcolor' 	  => '#000000',
			'mincolor' 	  => '#CCCCCC',
			'number' 	  => 45,
			'format' 	  => 'flat',
			'selectionby' => 'count',
			'selection'   => 'desc',
			'orderby'	  => 'random',
			'order'		  => 'asc',
			'exclude' 	  => '',
			'include' 	  => '',
			'no_follow'   => 0,
			'limit_days'  => 0,
			'min_usage'   => 0,
			'inc_cats' 	  => 0,
			'notagstext'  => __('No tags.', 'simpletags'),
			'xformat' 	  => __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'title' 	  => __('<h4>Tag Cloud</h4>', 'simpletags'),
			'category' 	  => 0
		);
		
		// Get values in DB
		$defaults['no_follow'] 	 = $this->options['no_follow'];
		$defaults['selectionby'] = $this->options['cloud_selectionby'];
		$defaults['selection'] 	 = $this->options['cloud_selection'];
		$defaults['orderby'] 	 = $this->options['cloud_orderby'];
		$defaults['order'] 		 = $this->options['cloud_order'];
		$defaults['number'] 	 = $this->options['cloud_limit_qty'];
		$defaults['notagstext']  = $this->options['cloud_notagstext'];
		$defaults['title'] 		 = $this->options['cloud_title'];
		$defaults['maxcolor'] 	 = $this->options['cloud_max_color'];
		$defaults['mincolor'] 	 = $this->options['cloud_min_color'];
		$defaults['largest'] 	 = $this->options['cloud_max_size'];
		$defaults['smallest'] 	 = $this->options['cloud_min_size'];
		$defaults['unit'] 		 = $this->options['cloud_unit'];
		$defaults['xformat'] 	 = $this->options['cloud_xformat'];
		$defaults['format'] 	 = $this->options['cloud_format'];
		$defaults['inc_cats'] 	 = $this->options['cloud_inc_cats'];
		
		if ( empty($args) ) {
			$args = $this->options['cloud_adv_usage'];
		}
		$args = wp_parse_args( $args, $defaults );
		
		// Get categories ?
		$taxonomy = ( (int) $args['inc_cats'] == 0 ) ? 'post_tag' : array('post_tag', 'category');
		
		// Get terms
		$terms = $this->getTags( $args, $taxonomy );
		extract($args); // Params to variables
		
		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
		
		// Clean memory
		$args = array();
		$defaults = array();
		unset($args, $defaults);
		
		if ( empty($terms) ) {
			return $this->outputContent( 'st-tag-cloud', $format, $title, $notagstext, $copyright );
		}
		
		$counts = $terms_data = array();
		foreach ( (array) $terms as $term ) {
			$counts[$term->name] = $term->count;
			$terms_data[$term->name] = $term;
		}
		
		// Remove temp data from memory
		$terms = array();
		unset($terms);
		
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
		$rel = $this->buildRel( $no_follow );
		
		// Remove color marquer if color = false
		if ( $color == 'false' ) {
			$xformat = str_replace('%tag_color%', '', $xformat);
		}
		
		// Remove size marquer if size = false
		if ( $size == 'false' ) {
			$xformat = str_replace('%tag_size%', '', $xformat);
		}
		
		// Order terms before output
		// count, name, rand | asc, desc
		
		$orderby = strtolower($orderby);
		if ( $orderby == 'count' ) {
			asort($counts);
		} elseif ( $orderby == 'name' ) {
			uksort($counts, array( &$this, 'uksortByName'));
		} else { // rand
			$this->randomArray($counts);
		}
		
		$order = strtolower($order);
		if ( $order == 'desc' && $orderby != 'random' ) {
			$counts = array_reverse($counts);
		}
		
		$output = array();
		foreach ( (array) $counts as $term_name => $count ) {
			if ( !is_object($terms_data[$term_name]) ) {
				continue;
			}
			
			$term = $terms_data[$term_name];
			$scale_result = (int) (($term->count - $minval) * $scale + $minout);
			$output[] = $this->formatInternalTag( $xformat, $term, $rel, $scale_result, $scale_max, $scale_min, $largest, $smallest, $unit, $maxcolor, $mincolor );
		}
		
		// Remove unused variables
		$counts = array();
		$terms = array();
		unset($counts, $terms, $element_loop, $term);
		
		return $this->outputContent( 'st-tag-cloud', $format, $title, $output, $copyright );
	}
	
	/**
	 * Randomize an array and keep association
	 *
	 * @param array $array
	 * @return boolean
	 */
	function randomArray( &$array ) {
		if ( !is_array($array) || empty($array) ) {
			return false;
		}
		
		$keys = array_keys($array);
		shuffle($keys);
		foreach( (array) $keys as $key ) {
			$new[$key] = $array[$key];
		}
		$array = $new;

		return true;
	}
	
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
			$element_loop = str_replace('%tag_link%', esc_url(get_tag_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', esc_url(get_tag_feed_link($term->term_id)), $element_loop);
		} else { // Category
			$element_loop = str_replace('%tag_link%', esc_url(get_category_link($term->term_id)), $element_loop);
			$element_loop = str_replace('%tag_feed%', esc_url(get_category_rss_link(false, $term->term_id, '')), $element_loop);
		}
		$element_loop = str_replace('%tag_name%', esc_html( $term->name ), $element_loop);
		$element_loop = str_replace('%tag_name_attribute%', esc_html(strip_tags($term->name)), $element_loop);
		$element_loop = str_replace('%tag_id%', $term->term_id, $element_loop);
		$element_loop = str_replace('%tag_count%', (int) $term->count, $element_loop);
		
		// Need rel
		$element_loop = str_replace('%tag_rel%', $rel, $element_loop);
		
		// Need max/min/scale and other :)
		if ( $scale_result !== null ) {
			$element_loop = str_replace('%tag_size%', 'font-size:'.$this->round(($scale_result - $scale_min)*($largest-$smallest)/($scale_max - $scale_min) + $smallest, 2).$unit.';', $element_loop);
			$element_loop = str_replace('%tag_color%', 'color:'.$this->getColorByScale($this->round(($scale_result - $scale_min)*(100)/($scale_max - $scale_min), 2),$mincolor,$maxcolor).';', $element_loop);
			$element_loop = str_replace('%tag_scale%', $scale_result, $element_loop);
		}
		
		// External link
		$element_loop = str_replace('%tag_technorati%', $this->formatExternalTag( 'technorati', $term->name ), $element_loop);
		$element_loop = str_replace('%tag_flickr%', $this->formatExternalTag( 'flickr', $term->name ), $element_loop);
		$element_loop = str_replace('%tag_delicious%', $this->formatExternalTag( 'delicious', $term->name ), $element_loop);
		
		return $element_loop;
	}
	
	/**
	 * Extend the round PHP function for force a dot for all locales instead the comma.
	 *
	 * @param string $value 
	 * @param string $approximation 
	 * @return void
	 * @author Amaury Balmer
	 */
	function round( $value, $approximation ) {
		$value = round( $value, $approximation );
		$value = str_replace( ',', '.', $value ); // Fixes locale comma
		$value = str_replace( ' ', '' , $value ); // No space
		return $value;
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
		
		$term_name = esc_html($term_name);
		switch ( $type ) {
			case 'technorati':
				return '<a class="tag_technorati" href="'.esc_url('http://technorati.com/tag/'.str_replace(' ', '+', $term_name)).'" rel="tag">'.$term_name.'</a>';
				break;
			case 'flickr':
				return '<a class="tag_flickr" href="'.esc_url('http://www.flickr.com/photos/tags/'.preg_replace('/[^a-zA-Z0-9]/', '', strtolower($term_name)).'/').'" rel="tag">'.$term_name.'</a>';
				break;
			case 'delicious':
				return '<a class="tag_delicious" href="'.esc_url('http://del.icio.us/popular/'.strtolower(str_replace(' ', '', $term_name))).'" rel="tag">'.$term_name.'</a>';
				break;
			default:
				return '';
				break;
		}
	}
	
	/**
	 * Generate current post tags
	 *
	 * @param string $args
	 * @return string
	 */
	function extendedPostTags( $args = '', $copyright = true ) {
		$defaults = array(
			'before' 	=> __('Tags: ', 'simpletags'),
			'separator' => ', ',
			'after' 	=> '<br />',
			'post_id' 	=> 0,
			'no_follow' => 0,
			'inc_cats' 	=> 0,
			'xformat' 	=> __('<a href="%tag_link%" title="%tag_name_attribute%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'notagtext' => __('No tag for this post.', 'simpletags'),
			'number' 	=> 0,
			'format' 	=> ''
		);
		
		// Get values in DB
		$defaults['before'] = $this->options['tt_before'];
		$defaults['separator'] = $this->options['tt_separator'];
		$defaults['after'] = $this->options['tt_after'];
		$defaults['no_follow'] = (int) $this->options['no_follow'];
		$defaults['inc_cats'] = $this->options['tt_inc_cats'];
		$defaults['xformat'] = $this->options['tt_xformat'];
		$defaults['notagtext'] = $this->options['tt_notagstext'];
		$defaults['number'] = (int) $this->options['tt_number'];
		if ( empty($args) ) {
			$args = $this->options['tt_adv_usage'];
		}
		
		// Extract data in variables
		$args = wp_parse_args( $args, $defaults );
		extract($args);
		
		// If empty use default xformat !
		if ( empty($xformat) ) {
			$xformat = $defaults['xformat'];
		}
		
		// Clean memory
		$args = array();
		$defaults = array();
		
		// Choose post ID
		$object_id = (int) $post_id;
		if ( $object_id == 0 ) {
			global $post;
			$object_id = (int) $post->ID;
			if ( $object_id == 0 ) {
				return false;
			}
		}
		
		// Get categories ?
		$taxonomy = ( (int) $inc_cats == 0 ) ? 'post_tag' : array('post_tag', 'category');
		// Get terms
		$terms = apply_filters( 'get_the_tags', wp_get_object_terms($object_id, $taxonomy) );
		
		// Limit to max quantity if set
		$number = (int) $number;
		if ( $number != 0 ) {
			shuffle($terms); // Randomize terms
			$terms = array_slice( $terms, 0, $number );
		}
		
		// Return for object format
		if ( $format == 'object' ) {
			return $terms;
		}
		
		// If no terms, return text nothing.
		if ( empty($terms) ) {
			return $notagtext;
		}
		
		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $no_follow );
		
		// Prepare output
		foreach ( (array) $terms as $term ) {
			if ( !is_object($term) ) {
				continue;
			}
			
			$output[] = $this->formatInternalTag( $xformat, $term, $rel, null );
		}
		
		// Clean memory
		$terms = array();
		unset($terms, $term);
		
		
		// Array to string
		if ( is_array($output) && !empty($output) ) {
			$output = implode($separator, $output);
		} else {
			$output = $notagtext;
		}
		
		// Add container
		$output = $before . $output . $after;
		
		return $this->outputContent( '', 'string', '', $output, $copyright );
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
	function prepareQuery( $where = '' ) {
		if ( is_tag() ) {
			$where = str_replace( 'post_type = \'post\'', 'post_type IN(\'page\', \'post\')', $where );
		}
		return $where;
	}
	
	/**
	 * Extended get_tags function that use getTerms function
	 *
	 * @param string $args
	 * @return array
	 */
	function getTags( $args = '', $taxonomy = 'post_tag' ) {
		$key = md5(maybe_serialize($args).$taxonomy);
		
		// Get cache if exist
		if ( $cache = wp_cache_get( 'st_get_tags', 'simpletags' ) ) {
			if ( isset( $cache[$key] ) ) {
				return apply_filters('get_tags', $cache[$key], $args);
			}
		}
		
		// Get tags
		$terms = $this->getTerms( $taxonomy, $args );
		if ( empty($terms) ) {
			return array();
		}
		
		$cache[$key] = $terms;
		wp_cache_set( 'st_get_tags', $cache, 'simpletags' );
		
		$terms = apply_filters('st_get_tags', $terms, $args);
		return $terms;
	}
	
	/**
	 * Helper function for keep compatibility with old options simple tags widgets
	 *
	 * @param string $old_value 
	 * @param string $key 
	 * @return string
	 * @author Amaury Balmer
	 */
	function compatOldOrder( $old_value = '', $key = '' ) {
		$return = array();
		
		switch ( strtolower($old_value) ) { // count-asc/count-desc/name-asc/name-desc/random
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
		
		if ( empty($return) || !isset($return[$key]) ) {
			return $old_value;
		}
		
		return $return[$key];
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
	function getTerms( $taxonomies, $args = '' ) {
		global $wpdb;
		$empty_array = array();
		$join_relation = false;
		
		$single_taxonomy = false;
		if ( !is_array($taxonomies) ) {
			$single_taxonomy = true;
			$taxonomies = array($taxonomies);
		}
		
		foreach ( (array) $taxonomies as $taxonomy ) {
			if ( ! taxonomy_exists($taxonomy) ) {
				$error = & new WP_Error('invalid_taxonomy', __('Invalid Taxonomy'));
				return $error;
			}
		}
		$in_taxonomies = "'" . implode("', '", $taxonomies) . "'";
		
		$defaults = array('orderby' => 'name', 'order' => 'ASC',
			'hide_empty' => true, 'exclude' => array(), 'exclude_tree' => array(), 'include' => array(),
			'number' => '', 'fields' => 'all', 'slug' => '', 'parent' => '',
			'hierarchical' => true, 'child_of' => 0, 'get' => '', 'name__like' => '',
			'pad_counts' => false, 'offset' => '', 'search' => '',
			// Simple tags added
			'limit_days' => 0, 'category' => 0, 'min_usage' => 0, 'st_name__like' => '' );
		
		$args = wp_parse_args( $args, $defaults );
		
		// Translate selection order
		$args['orderby'] = $this->compatOldOrder( $args['selectionby'], 'orderby' );
		$args['order']   = $this->compatOldOrder( $args['selection'], 'order' );
		
		$args['number'] 	= absint( $args['number'] );
		$args['offset'] 	= absint( $args['offset'] );
		$args['limit_days'] = absint( $args['limit_days'] );
		$args['min_usage'] 	= absint( $args['min_usage'] );
		
		if ( !$single_taxonomy || !is_taxonomy_hierarchical($taxonomies[0]) ||
			'' !== $args['parent'] ) {
			$args['child_of'] 		= 0;
			$args['hierarchical'] 	= false;
			$args['pad_counts'] 	= false;
		}
		
		if ( 'all' == $args['get'] ) {
			$args['child_of'] 		= 0;
			$args['hide_empty'] 	= 0;
			$args['hierarchical'] 	= false;
			$args['pad_counts'] 	= false;
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
		
		// $args can be whatever, only use the args defined in defaults to compute the key
		$filter_key = ( has_filter('list_terms_exclusions') ) ? serialize($GLOBALS['wp_filter']['list_terms_exclusions']) : '';
		$key = md5( serialize( compact(array_keys($defaults)) ) . serialize( $taxonomies ) . $filter_key );
		$last_changed = wp_cache_get('last_changed', 's-terms');
		if ( !$last_changed ) {
			$last_changed = time();
			wp_cache_set('last_changed', $last_changed, 's-terms');
		}
		$cache_key = "get_terms:$key:$last_changed";
		$cache = wp_cache_get( $cache_key, 's-terms' );
		if ( false !== $cache ) {
			$cache = apply_filters('get_terms', $cache, $taxonomies, $args);
			return $cache;
		}
		
		$_orderby = strtolower($orderby);
		if ( 'count' == $_orderby )
			$orderby = 'tt.count';
		if ( 'random' == $_orderby )
			$orderby = 'RAND()';
		else if ( 'name' == $_orderby )
			$orderby = 't.name';
		else if ( 'slug' == $_orderby )
			$orderby = 't.slug';
		else if ( 'term_group' == $_orderby )
			$orderby = 't.term_group';
		elseif ( empty($_orderby) || 'id' == $_orderby )
			$orderby = 't.term_id';
		$orderby = apply_filters( 'get_terms_orderby', $orderby, $args );
		
		if ( !empty($orderby) )
			$orderby = "ORDER BY $orderby";
		else
			$order = '';
		
		$where = '';
		$inclusions = '';
		if ( !empty($include) ) {
			$exclude = '';
			$exclude_tree = '';
			$interms = wp_parse_id_list($include);
			foreach ( $interms as $interm ) {
				if ( empty($inclusions) )
					$inclusions = ' AND ( t.term_id = ' . intval($interm) . ' ';
				else
					$inclusions .= ' OR t.term_id = ' . intval($interm) . ' ';
			}
		}

		if ( !empty($inclusions) )
			$inclusions .= ')';
		$where .= $inclusions;
		
		$exclusions = '';
		if ( !empty( $exclude_tree ) ) {
			$excluded_trunks = wp_parse_id_list($exclude_tree);
			foreach ( $excluded_trunks as $extrunk ) {
				$excluded_children = (array) get_terms($taxonomies[0], array('child_of' => intval($extrunk), 'fields' => 'ids'));
				$excluded_children[] = $extrunk;
				foreach( $excluded_children as $exterm ) {
					if ( empty($exclusions) )
						$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
					else
						$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
				}
			}
		}
		
		if ( !empty($exclude) ) {
			$exterms = wp_parse_id_list($exclude);
			foreach ( $exterms as $exterm ) {
				if ( empty($exclusions) )
					$exclusions = ' AND ( t.term_id <> ' . intval($exterm) . ' ';
				else
					$exclusions .= ' AND t.term_id <> ' . intval($exterm) . ' ';
			}
		}
		
		if ( !empty($exclusions) )
			$exclusions .= ')';
		$exclusions = apply_filters('list_terms_exclusions', $exclusions, $args );
		$where .= $exclusions;
		
		// ST Features : Restrict category
		if ( $category != 0 ) {
			if ( !is_array($taxonomies) )
				$taxonomies = array($taxonomies);
			
			$incategories = wp_parse_id_list($category);
			
			$taxonomies 	= "'" . implode("', '", $taxonomies  ) . "'";
			$incategories 	= "'" . implode("', '", $incategories) . "'";
			
			$where .= " AND tr.object_id IN ( ";
				$where .= "SELECT tr.object_id FROM $wpdb->term_relationships AS tr INNER JOIN $wpdb->term_taxonomy AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN $wpdb->posts as p ON tr.object_id=p.ID WHERE tt.term_id IN ($incategories) AND p.post_status='publish'";
			$where .= " ) ";
			
			$join_relation = true;
			unset($incategories, $category);
		}
		
		// ST Features : Limit posts date
		if ( $limit_days != 0 ) {
			$where .= " AND tr.object_id IN ( ";
				$where .= "SELECT DISTINCT ID FROM $wpdb->posts AS p WHERE p.post_status='publish' AND ".(( $this->options['use_tag_pages'] == '1' ) ? "p.post_type IN('page', 'post')" : "post_type = 'post'")." AND p.post_date_gmt > '" .date( 'Y-m-d H:i:s', time() - $limit_days * 86400 ). "'";
			$where .= " ) ";
			
			$join_relation = true;
			unset($limit_days);
		}
		
		if ( !empty($slug) ) {
			$slug = sanitize_title($slug);
			$where .= " AND t.slug = '$slug'";
		}

		if ( !empty($name__like) )
			$where .= " AND t.name LIKE '{$name__like}%'";

		if ( '' !== $parent ) {
			$parent = (int) $parent;
			$where .= " AND tt.parent = '$parent'";
		}
		
		// ST Features : Another way to search
		if ( strpos($st_name__like, ' ') !== false ) {
			
			$st_terms_formatted = array();
			$st_terms = preg_split('/[\s,]+/', $st_name_like);
			foreach ( (array) $st_terms as $st_term ) {
				if ( empty($st_term) )
					continue;
				$st_terms_formatted[] = "t.name LIKE '%".like_escape($st_term)."%'";
			}
			
			$where .= " AND ( " . explode( ' OR ', $st_terms_formatted ) . " ) ";
			unset( $st_term, $st_terms_formatted, $st_terms );
		
		} elseif ( !empty($st_name__like) ) {
			
			$where .= " AND t.name LIKE '%{$st_name__like}%'";
		
		}
		
		// ST Features : Add min usage
		if ( $hide_empty && !$hierarchical ) {
			if ( $min_usage == 0 )
				$where .= ' AND tt.count > 0';
			else
				$where .= $wpdb->prepare( ' AND tt.count >= %d', $min_usage );
		}
		
		// don't limit the query results when we have to descend the family tree
		if ( ! empty($number) && ! $hierarchical && empty( $child_of ) && '' === $parent ) {
			if ( $offset )
				$limit = 'LIMIT ' . $offset . ',' . $number;
			else
				$limit = 'LIMIT ' . $number;
		} else {
			$limit = '';
		}
		
		if ( !empty($search) ) {
			$search = like_escape($search);
			$where .= " AND (t.name LIKE '%$search%')";
		}
		
		$selects = array();
		switch ( $fields ) {
	 		case 'all':
	 			$selects = array('t.*', 'tt.*');
	 			break;
	 		case 'ids':
			case 'id=>parent':
	 			$selects = array('t.term_id', 'tt.parent', 'tt.count');
	 			break;
	 		case 'names':
	 			$selects = array('t.term_id', 'tt.parent', 'tt.count', 't.name');
	 			break;
	 		case 'count':
				$orderby = '';
				$order = '';
	 			$selects = array('COUNT(*)');
	 	}
	    $select_this = implode(', ', apply_filters( 'get_terms_fields', $selects, $args ));
		
		// Add inner to relation table ?
		$join_relation = $join_relation == false ? '' : "INNER JOIN $wpdb->term_relationships AS tr ON tt.term_taxonomy_id = tr.term_taxonomy_id";
		
		$query = "SELECT $select_this 
			FROM $wpdb->terms AS t 
			INNER JOIN $wpdb->term_taxonomy AS tt ON t.term_id = tt.term_id
			$join_relation
			WHERE tt.taxonomy IN ($in_taxonomies) 
			$where 
			$orderby $order 
			$limit";
			// GROUP BY t.term_id
		
		if ( 'count' == $fields ) {
			$term_count = $wpdb->get_var($query);
			return $term_count;
		}

		$terms = $wpdb->get_results($query);
		if ( 'all' == $fields ) {
			update_term_cache($terms);
		}
		
		if ( empty($terms) ) {
			wp_cache_add( $cache_key, array(), 's-terms' );
			$terms = apply_filters('get_terms', array(), $taxonomies, $args);
			return $terms;
		}

		if ( $child_of ) {
			$children = _get_term_hierarchy($taxonomies[0]);
			if ( ! empty($children) )
				$terms = & _get_term_children($child_of, $terms, $taxonomies[0]);
		}
		
		// Update term counts to include children.
		if ( $pad_counts && 'all' == $fields )
			_pad_term_counts($terms, $taxonomies[0]);

		// Make sure we show empty categories that have children.
		if ( $hierarchical && $hide_empty && is_array($terms) ) {
			foreach ( $terms as $k => $term ) {
				if ( ! $term->count ) {
					$children = _get_term_children($term->term_id, $terms, $taxonomies[0]);
					if ( is_array($children) )
						foreach ( $children as $child )
							if ( $child->count )
								continue 2;
								
					// It really is empty
					unset($terms[$k]);
				}
			}
		}
		reset ( $terms );
		
		$_terms = array();
		if ( 'id=>parent' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[$term->term_id] = $term->parent;
			$terms = $_terms;
		} elseif ( 'ids' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->term_id;
			$terms = $_terms;
		} elseif ( 'names' == $fields ) {
			while ( $term = array_shift($terms) )
				$_terms[] = $term->name;
			$terms = $_terms;
		}
		
		if ( 0 < $number && intval(@count($terms)) > $number ) {
			$terms = array_slice($terms, $offset, $number);
		}
		
		wp_cache_add( $cache_key, $terms, 's-terms' );

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
					$output = '<ul class="'.$html_class.'">'. "\n\t".'<li>' . implode("</li>\n\t<li>", $content) . "</li>\n</ul>\n";
					break;
				default :
					$output = '<div class="'.$html_class.'">'. "\n\t" . implode("{$separator}\n", $content) . "</div>\n";
					break;
			}
		} else {
			$content = trim($content);
			switch ( $format ) {
				case 'string' :
					$output = $content;
					break;
				case 'list' :
					$output = '<ul class="'.$html_class.'">'. "\n\t" . '<li>'.$content."</li>\n\t" . "</ul>\n";
					break;
				default :
					$output = '<div class="'.$html_class.'">'. "\n\t" . $content . "</div>\n";
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
		return "\n" . '<!-- Generated by Simple Tags ' . STAGS_VERSION . ' - http://wordpress.org/extend/plugins/simple-tags -->' ."\n\t". $title . $output. "\n";
		else
		return "\n\t". $title . $output. "\n";
	}
	
	/**
	 * Update taxonomy counter for post AND page
	 *
	 * @param array $terms
	 */
	function _update_post_and_page_term_count( $terms ) {
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type IN ('page', 'post') AND term_taxonomy_id = %d", $term ) );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		}
		return true;
	}
	
	/**
	 * Escape string so that it can used in Regex. E.g. used for [tags]...[/tags]
	 *
	 * @param string $content
	 * @return string
	 */
	function regexEscape( $content ) {
		return strtr($content, array("\\" => "\\\\", "/" => "\\/", "[" => "\\[", "]" => "\\]"));
	}
}
?>