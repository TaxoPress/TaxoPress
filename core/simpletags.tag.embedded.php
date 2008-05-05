<?php
Class SimpleTags_Embedded {
	var $options = array();
	
	function SimpleTags_Embedded() {
		// Load default options
		$this->options = array(
			'start_embed_tags' => '[tags]',
			'end_embed_tags' => '[/tags]'
		);
		
		add_filter('the_content', array(&$this, 'filterEmbedTags'), 0);
		
		if ( is_admin() ) {
			add_action('save_post', array(&$this, 'saveEmbedTags'));
			add_action('publish_post', array(&$this, 'saveEmbedTags'));
			add_action('post_syndicated_item', array(&$this, 'saveEmbedTags'));
		}
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
	 * Save embedded tags
	 *
	 * @param integer $post_id
	 * @param array $post_data
	 */
	function saveEmbedTags( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return false;
		}

		// Return Tags
		$matches = $tags = array();
		preg_match_all('/(' . $this->regexEscape($this->options['start_embed_tags']) . '(.*?)' . $this->regexEscape($this->options['end_embed_tags']) . ')/is', $object->post_content, $matches);

		foreach ( $matches[2] as $match) {
			foreach( (array) explode(',', $match) as $tag) {
				$tags[] = $tag;
			}
		}

		if( !empty($tags) ) {
			// Remove empty and duplicate elements
			$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));
			$tags = array_unique($tags);

			wp_set_post_tags( $post_id, $tags, true ); // Append tags

			// Clean cache
			if ( 'page' == $object->post_type ) {
				clean_page_cache($post_id);
			} else {
				clean_post_cache($post_id);
			}
			
			return true;
		}
		return false;
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