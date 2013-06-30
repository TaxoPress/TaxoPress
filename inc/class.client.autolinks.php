<?php

class SimpleTags_Client_Autolinks {

	static $posts = array();
	static $link_tags = array();

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		$auto_link_priority = SimpleTags_Plugin::get_option_value('auto_link_priority');
		if ((int) $auto_link_priority == 0) {
			$auto_link_priority = 12;
		}

		// Auto link tags
		add_filter('the_posts', array(__CLASS__, 'the_posts'), 10, 2);

		if (SimpleTags_Plugin::get_option_value('auto_link_views') != 'no') {
			add_filter('the_content', array(__CLASS__, 'the_content'), $auto_link_priority);
		}
	}

	/**
	 * Stock posts ID as soon as possible
	 * TODO: test if post_type allow post_tag before keep post ID
	 *
	 * @param array $posts
	 * @return array
	 */
	public static function the_posts($posts, $query) {
		if (!empty($posts) && is_array($posts)) {
			foreach ((array) $posts as $post) {
				self::$posts[] = (int) $post->ID;
			}

			self::$posts = array_unique(self::$posts);
		}

		return $posts;
	}

	/**
	 * Get tags from current post views
	 *
	 * @return boolean
	 */
	public static function get_tags_from_current_posts() {
		if (is_array(self::$posts) && count(self::$posts) > 0) {
			// Generate SQL from post id
			$postlist = implode("', '", self::$posts);

			// Generate key cache
			$key = md5(maybe_serialize($postlist));

			$results = array();

			// Get cache if exist
			$cache = wp_cache_get('generate_keywords', 'simpletags');
			if ($cache === false) {
				foreach (self::$posts as $object_id) {
					// Get terms
					$terms = get_object_term_cache($object_id, 'post_tag');
					if (false === $terms) {
						$terms = wp_get_object_terms($object_id, 'post_tag');
					}

					if ($terms != false) {
						$results = array_merge($results, $terms);
					}
				}

				$cache[$key] = $results;
				wp_cache_set('generate_keywords', $cache, 'simpletags');
			} else {
				if (isset($cache[$key])) {
					return $cache[$key];
				}
			}

			return $results;
		}

		return array();
	}

	/**
	 * Get links for each tag for auto link feature
	 *
	 */
	public static function prepare_auto_link_tags() {
		$auto_link_min = (int) SimpleTags_Plugin::get_option_value('auto_link_min');
		if ($auto_link_min == 0) {
			$auto_link_min = 1;
		}

		foreach ((array) self::get_tags_from_current_posts() as $term) {
			if ($term->count >= $auto_link_min) {
				self::$link_tags[$term->name] = esc_url(get_term_link($term, $term->taxonomy));
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
	public static function the_content($content = '') {
		global $post;

		// Show only on singular view ? Check context
		if (SimpleTags_Plugin::get_option_value('auto_link_views') == 'singular' && !is_singular()) {
			return $content;
		}

		// user preference for this post ?
		$meta_value = get_post_meta($post->ID, '_exclude_autolinks', true);
		if (!empty($meta_value)) {
			return $content;
		}

		// Get currents tags if no exists
		self::prepare_auto_link_tags();

		// Shuffle array
		SimpleTags_Client::random_array(self::$link_tags);

		// HTML Rel (tag/no-follow)
		$rel = SimpleTags_Client::get_rel_attribut();

		// only continue if the database actually returned any links
		if (!isset(self::$link_tags) || !is_array(self::$link_tags) || empty(self::$link_tags)) {
			return $content;
		}

		// Case option ?
		$case = ( (int) SimpleTags_Plugin::get_option_value('auto_link_case') == 1 ) ? 'i' : '';
		$strpos_fnc = ($case == 'i') ? 'stripos' : 'strpos';

		// Prepare exclude terms array
		$excludes_terms = explode(',', SimpleTags_Plugin::get_option_value('auto_link_exclude'));
		if ($excludes_terms == false) {
			$excludes_terms = array();
		} else {
			$excludes_terms = array_filter($excludes_terms, '_delete_empty_element');
			$excludes_terms = array_unique($excludes_terms);
		}

		$z = 0;
		foreach ((array) self::$link_tags as $term_name => $term_link) {
			// Exclude terms ? next...
			if (in_array($term_name, (array) $excludes_terms)) {
				continue;
			}

			// Make a first test with PHP function, economize CPU with regexp
			if ($strpos_fnc($content, $term_name) === false) {
				continue;
			}
			
			if ( (int) SimpleTags_Plugin::get_option_value('auto_link_dom') == 1 && class_exists('DOMDocument') && class_exists('DOMXPath') ) {
				self::_replace_by_links_dom($content, $term_name, $term_link, $case, $rel);
			} else {
				self::_replace_by_links_regexp($content, $term_name, $term_link, $case, $rel);
			}
			
			$z++;
			if ($z > (int) SimpleTags_Plugin::get_option_value('auto_link_max_by_post'))
				break;
		}


		return $content;
	}

	/**
	 * Replace text by link, except HTML tag, and already text into link, use DOMdocument.
	 * Code take from : http://stackoverflow.com/questions/4044812/regex-domdocument-match-and-replace-text-not-in-a-link
	 * 
	 * @param string $content
	 * @param string $search
	 * @param string $replace
	 * @param string $case
	 * @param string $rel
	 */
	private static function _replace_by_links_dom(&$content, $search = '', $replace = '', $case = '', $rel = '') {
		$dom = new DOMDocument();
		
		// loadXml needs properly formatted documents, so it's better to use loadHtml, but it needs a hack to properly handle UTF-8 encoding
		$result = $dom->loadHtml(mb_convert_encoding($content, 'HTML-ENTITIES', "UTF-8"));
		if( $result === false ) {
			return false;
		}
		
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()[not(ancestor::a)]') as $node) {
			$substitute = '<a href="' . $replace . '" class="st_tag internal_tag" ' . $rel . ' title="' . esc_attr(sprintf(__('Posts tagged with %s', 'simpletags'), $search)) . "\">$search</a>";
			
			if ( $case == 'i' ) {
				$replaced = str_ireplace($search, $substitute, $node->wholeText);
			} else {
				$replaced = str_replace($search, $substitute, $node->wholeText);
			}
			
			$newNode = $dom->createDocumentFragment();
			$newNode->appendXML($replaced);
			$node->parentNode->replaceChild($newNode, $node);
		}

		// get only the body tag with its contents, then trim the body tag itself to get only the original content
		$content = mb_substr($dom->saveXML($xpath->query('//body')->item(0)), 6, -7, "UTF-8");
	}

	/**
	 * Replace text by link, except HTML tag, and already text into link, use PregEXP.
	 * 
	 * @param string $content
	 * @param string $search
	 * @param string $replace
	 * @param string $case
	 * @param string $rel
	 */
	private static function _replace_by_links_regexp(&$content, $search = '', $replace = '', $case = '', $rel = '') {
		$must_tokenize = true; // will perform basic tokenization
		$tokens = null; // two kinds of tokens: markup and text

		$j = 0;
		$filtered = ''; // will filter text token by token

		$match = '/(\PL|\A)(' . preg_quote($search, "/") . ')(\PL|\Z)/u' . $case;
		$substitute = '$1<a href="' . $replace . '" class="st_tag internal_tag" ' . $rel . ' title="' . esc_attr(sprintf(__('Posts tagged with %s', 'simpletags'), $search)) . "\">$2</a>$3";

		//$match = "/\b" . preg_quote($search, "/") . "\b/".$case;
		//$substitute = '<a href="'.$replace.'" class="st_tag internal_tag" '.$rel.' title="'. esc_attr( sprintf( __('Posts tagged with %s', 'simpletags'), $search ) )."\">$0</a>";
		// for efficiency only tokenize if forced to do so
		if ($must_tokenize) {
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
		if (isset($tokens) && is_array($tokens) && count($tokens) > 0) {
			$i = 0;
			foreach ($tokens as $token) {
				if (++$i % 2 && $token != '') { // this token is (non-markup) text
					if ($anchor_level == 0) { // linkify if not inside anchor tags
						if (preg_match($match, $token)) { // use preg_match for compatibility with PHP 4
							$j++;
							if ($j <= SimpleTags_Plugin::get_option_value('auto_link_max_by_tag') || SimpleTags_Plugin::get_option_value('auto_link_max_by_tag') == 0) {// Limit replacement at 1 by default, or options value !
								$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
							}
							$must_tokenize = true; // re-tokenize next time around
						}
					}
				} else { // this token is markup
					if (preg_match("#<\s*a\s+[^>]*>#i", $token)) { // found <a ...>
						$anchor_level++;
					} elseif (preg_match("#<\s*/\s*a\s*>#i", $token)) { // found </a>
						$anchor_level--;
					}
				}
				$filtered .= $token; // this token has now been filtered
			}
			$content = $filtered; // filtering completed for this link
		}
	}

}