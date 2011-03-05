<?php
class SimpleTags_Client_Autolinks extends SimpleTags_Client {
	var $posts 		= array();
	var $link_tags 	= array();
	
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function SimpleTags_Client_Autolinks() {
		$options = get_option( STAGS_OPTIONS_NAME );
		if ( !isset($options['auto_link_priority']) || (int) $options['auto_link_priority'] == 0 )
			$options['auto_link_priority'] = 12;
			
		// Auto link tags
		add_filter( 'the_posts', 	array(&$this, 'getPostIds') );
		add_filter( 'the_content', 	array(&$this, 'autoLinkTags'), $options['auto_link_priority'] );
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
		global $wpdb;
		
		if ( is_array($this->posts) && count($this->posts) > 0 ) {
			// Generate SQL from post id
			$postlist = implode( "', '", $this->posts );
			
			// Generate key cache
			$key = md5(maybe_serialize($postlist));
			
			// Get cache if exist
			if ( $cache = wp_cache_get( 'generate_keywords', 'simpletags' ) ) {
				if ( isset( $cache[$key] ) ) {
					return $cache[$key];
				}
			}
			
			// If cache not exist, get datas and set cache
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
			
			return $results;
		}
		
		return array();
	}
	
	/**
	 * Get links for each tag for auto link feature
	 *
	 */
	function prepareAutoLinkTags() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		$auto_link_min = (int) $options['auto_link_min'];
		if ( $auto_link_min == 0 ) {
			$auto_link_min = 1;
		}
		
		foreach ( (array) $this->getTagsFromCurrentPosts() as $term ) {
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
		global $post;
		
		// user preference for this post ?
		$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
		if ( !empty($meta_value) )
			return $content;
		
		// Get currents tags if no exists
		$this->prepareAutoLinkTags();
		
		// Shuffle array
		$this->randomArray($this->link_tags);
		
		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel();
		
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// only continue if the database actually returned any links
		if ( isset($this->link_tags) && is_array($this->link_tags) && count($this->link_tags) > 0 ) {
			// Case option ?
			$case = ( $options['auto_link_case'] == 1 ) ? 'i' : '';
			$strpos_fnc = $options['auto_link_case'] ? 'stripos' : 'strpos';
			
			// Prepare exclude terms array
			$excludes_terms = explode( ',', $options['auto_link_exclude'] );
			if ( $excludes_terms == false ) {
				$excludes_terms = array();
			} else {
				$excludes_terms = array_filter($excludes_terms, '_delete_empty_element');
				$excludes_terms = array_unique($excludes_terms);
			}
			
			$z = 0;
			foreach ( (array) $this->link_tags as $term_name => $term_link ) {
				// Exclude terms ? next...
				if ( in_array( $term_name, (array) $excludes_terms ) ) {
					continue;
				}
				
				// Make a first test with PHP function, economize CPU with regexp
				if ( $strpos_fnc( $content, $term_name ) === false ) {
					continue;
				}
				
				$must_tokenize = true; // will perform basic tokenization
				$tokens = null; // two kinds of tokens: markup and text
				
				$j = 0;
				$filtered = ''; // will filter text token by token
				
				$match = '/(\PL|\A)(' . preg_quote($term_name, "/") . ')(\PL|\Z)/u'.$case;
				$substitute = '$1<a href="'.$term_link.'" class="st_tag internal_tag" '.$rel.' title="'. esc_attr( sprintf( __('Posts tagged with %s', 'simpletags'), $term_name ) )."\">$2</a>$3";
				
				//$match = "/\b" . preg_quote($term_name, "/") . "\b/".$case;
				//$substitute = '<a href="'.$term_link.'" class="st_tag internal_tag" '.$rel.' title="'. esc_attr( sprintf( __('Posts tagged with %s', 'simpletags'), $term_name ) )."\">$0</a>";
				
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
									$j++;
									if ( $j <= $options['auto_link_max_by_tag'] ) {// Limit replacement at 1 by default, or options value !
										$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
									}
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
				
				$z++;
				if ( $z > (int) $options['auto_link_max_by_post'] )
					break;
			}
		}
		
		return $content;
	}
}
?>