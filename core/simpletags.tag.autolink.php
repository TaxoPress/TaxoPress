<?php
// Todo : Options (auto_link_min + no_follow + auto_link_case)

Class SimpleTags_AutoLink {
	var $options = array();
	var $link_tags = 'null';
	
	function SimpleTags_AutoLink() {
		// Load default options
		$this->options = array(
			'auto_link_tags' => 0,
			'auto_link_min' => 1,
			'auto_link_case' => 1
		);
		
		add_filter('the_content', array(&$this, 'autoLinkTags'), 9);
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
				$this->link_tags[$term->name] = clean_url(get_tag_link( $term->term_id ));
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

		// HTML Rel (tag/no-follow)
		$rel = $this->buildRel( $this->options['no_follow'] );

		// only continue if the database actually returned any links
		if ( isset($this->link_tags) && is_array($this->link_tags) && count($this->link_tags) > 0 ) {
			$must_tokenize = TRUE; // will perform basic tokenization
			$tokens = NULL; // two kinds of tokens: markup and text

			$case = ( $this->options['auto_link_case'] == 1 ) ? 'i' : '';

			foreach ( (array) $this->link_tags as $term_name => $term_link ) {
				$filtered = ""; // will filter text token by token
				$match = "/\b" . preg_quote($term_name, "/") . "\b/".$case;
				$substitute = '<a href="'.$term_link.'" class="st_tag internal_tag" '.$rel.' title="'. attribute_escape( sprintf( __('Posts tagged with %s', 'simpletags'), $term_name ) )."\">$0</a>";

				// for efficiency only tokenize if forced to do so
				if ( $must_tokenize ) {
					// this regexp is taken from PHP Markdown by Michel Fortin: http://www.michelf.com/projects/php-markdown/
					$comment = '(?s:<!(?:--.*?--\s*)+>)|';
					$processing_instruction = '(?s:<\?.*?\?>)|';
					$tag = '(?:<[/!$]?[-a-zA-Z0-9:]+\b(?>[^"\'>]+|"[^"]*"|\'[^\']*\')*>)';

					$markup = $comment . $processing_instruction . $tag;
					$flags = PREG_SPLIT_DELIM_CAPTURE;
					$tokens = preg_split("{($markup)}", $content, -1, $flags);
					$must_tokenize = FALSE;
				}

				// there should always be at least one token, but check just in case
				if ( isset($tokens) && is_array($tokens) && count($tokens) > 0 ) {
					$i = 0;
					foreach ($tokens as $token) {
						if (++$i % 2 && $token != '') { // this token is (non-markup) text
							if ($anchor_level == 0) { // linkify if not inside anchor tags
								if ( preg_match($match, $token) ) { // use preg_match for compatibility with PHP 4
									$token = preg_replace($match, $substitute, $token); // only PHP 5 supports calling preg_replace with 5 arguments
									$must_tokenize = TRUE; // re-tokenize next time around
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

		return $content;
	}
}