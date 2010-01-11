<?php
class SimpleTagsBase {
	var $version = '1.7.1-rc1.1';
	var $options;

	function SimpleTagsBase() {
		return true;
	}
	
	function initOptions() {
		// Options
		$this->options = $this->getDefaultOptions(); 

		// Get options from WP options
		$options_from_table = get_option( STAGS_OPTIONS_NAME );

		// Update default options by getting not empty values from options table
		foreach( (array) $this->options as $key => $value ) {
			if ( !is_null($options_from_table[$key]) ) {
				$this->options[$key] = $options_from_table[$key];
			}
		}

		// Clean memory
		$options_from_table = array();
		unset($options_from_table, $value);
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
	
	/**
	 * Add initial ST options in DB
	 *
	 */
	function installSimpleTags() {
		$options_from_table = get_option( STAGS_OPTIONS_NAME );
		if ( $options_from_table == false ) {
			$this->resetToDefaultOptions();
		}
	}

	/**
	 * Remove ST options when user delete plugin (use WP API Uninstall)
	 *
	 */
	function uninstall() {
		delete_option( STAGS_OPTIONS_NAME );
		delete_option( 'widget_stags_cloud' );
	}
	
	/**
	 * Set an option value  -- note that this will NOT save the options.
	 *
	 * @param string $optname
	 * @param string $optval
	 */
	function setOption( $optname = '', $optval = '') {
		$this->options[$optname] = $optval;
	}

	/**
	 * Save all current options
	 *
	 */
	function saveOptions() {
		return update_option(STAGS_OPTIONS_NAME, $this->options);
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		$this->options = $this->getDefaultOptions();
		return update_option( STAGS_OPTIONS_NAME, $this->options );
	}
	
	/* 
	 * Get all array options.
	 *
	 * */
	function getDefaultOptions() {
		return array(
			// General
			'use_tag_pages' 		=> 1,
			'allow_embed_tcloud' 	=> 0,
			'no_follow' 			=> 0,
		
			// Auto link
			'auto_link_tags' 		=> 0,
			'auto_link_min' 		=> 1,
			'auto_link_case' 		=> 1,
			'auto_link_exclude' 	=> '',
			'auto_link_max_by_post' => 10,
			
			// Administration
			'use_click_tags' 	 => 1,
			'use_suggested_tags' => 1,
			'use_autocompletion' => 1,
			
			// Embedded Tags
			'use_embed_tags' 	=> 0,
			'start_embed_tags' 	=> '[tags]',
			'end_embed_tags' 	=> '[/tags]',
			
			// Related Posts
			'rp_feed' 		=> 0,
			'rp_embedded' 	=> 'no',
			'rp_order' 		=> 'count-desc',
			'rp_limit_qty' 	=> 5,
			'rp_notagstext' => __('No related posts.', 'simpletags'),
			'rp_title' 		=> __('<h4>Related posts</h4>', 'simpletags'),
			'rp_xformat' 	=> __('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a> (%post_comment%)', 'simpletags'),
			'rp_adv_usage' 	=> '',
			
			// Tag cloud
			'cloud_selectionby' => 'count',
			'cloud_selection' 	=> 'desc',
			'cloud_orderby' 	=> 'random', 
			'cloud_order' 		=> 'asc',
			'cloud_limit_qty' 	=> 45,
			'cloud_notagstext' 	=> __('No tags.', 'simpletags'),
			'cloud_title' 		=> __('<h4>Tag Cloud</h4>', 'simpletags'),
			'cloud_format' 		=> 'flat',
			'cloud_xformat' 	=> __('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>', 'simpletags'),
			'cloud_max_color' 	=> '#000000',
			'cloud_min_color' 	=> '#CCCCCC',
			'cloud_max_size' 	=> 22,
			'cloud_min_size' 	=> 8,
			'cloud_unit' 		=> 'pt',
			'cloud_inc_cats' 	=> 0,
			'cloud_adv_usage' 	=> '',
			
			// The tags
			'tt_feed' 		=> 0,
			'tt_embedded' 	=> 'no',
			'tt_separator' 	=> ', ',
			'tt_before' 	=> __('Tags: ', 'simpletags'),
			'tt_after' 		=> '<br />',
			'tt_notagstext' => __('No tags for this post.', 'simpletags'),
			'tt_number' 	=> 0,
			'tt_inc_cats' 	=> 0,
			'tt_xformat' 	=> __('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>', 'simpletags'),
			'tt_adv_usage' 	=> '',
			
			// Related tags
			'rt_number' 	=> 5,
			'rt_order' 		=> 'count-desc',
			'rt_separator' 	=> ' ',
			'rt_format' 	=> 'list',
			'rt_method' 	=> 'OR',
			'rt_title' 		=> __('<h4>Related tags</h4>', 'simpletags'),
			'rt_notagstext' => __('No related tags found.', 'simpletags'),
			'rt_xformat' 	=> __('<span>%tag_count%</span> <a href="%tag_link_add%">+</a> <a href="%tag_link%">%tag_name%</a>', 'simpletags'),
			
			// Remove related tags
			'rt_remove_separator' 	=> ' ',
			'rt_remove_format' 		=> 'list',
			'rt_remove_notagstext' 	=> ' ',
			'rt_remove_xformat' 	=> __('&raquo; <a href="%tag_link_remove%" title="Remove %tag_name_attribute% from search">Remove %tag_name%</a>', 'simpletags'),
			
			// Meta keywords
			'meta_autoheader' 		=> 1,
			'meta_always_include' 	=> '',
			'meta_keywords_qty' 	=> 0,
			
			// Auto tags
			'use_auto_tags' => 0,
			'at_all' 		=> 0,
			'at_empty' 		=> 0,
			'auto_list' 	=> ''
		);
	}
}
?>