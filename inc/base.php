<?php
class SimpleTagsBase {
	var $version = '1.7b1.1';

	var $info;
	var $options;
	var $default_options;
	var $db_options = 'simpletags';
	
	function SimpleTagsBase() {
	}
	
	function initBase() {
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
		
		if ( FORCE_SSL_ADMIN == true ) {
			$info['install_url'] = str_replace( 'http://', 'https://', $info['install_url'] );
		}

		// Set informations
		$this->info = array(
			'home' => get_option('home'),
			'siteurl' => $info['siteurl'],
			'install_url' => $info['install_url'],
			'install_dir' => $info['install_dir']
		);
		unset($info);
		
		// Options
		$default_options = array(
			// General
			'use_tag_pages' => 1,
			'allow_embed_tcloud' => 0,
			'no_follow' => 0,
			// Auto link
			'auto_link_tags' => 0,
			'auto_link_min' => 1,
			'auto_link_case' => 1,
			'auto_link_exclude' => '',
			'auto_link_max_by_post' => 20,
			// Administration
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
			'tt_notagstext' => __('No tags for this post.', 'simpletags'),
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
			'rt_notagstext' => __('No related tags found.', 'simpletags'),
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
		
		// Clean memory
		$default_options = array();
		$options_from_table = array();
		unset($default_options);
		unset($options_from_table);
		unset($default_options_value);
		
		// Activation
		register_activation_hook(__FILE__, array(&$this, 'installSimpleTags') );
		
		// Uninstall
		register_uninstall_hook (__FILE__, array(&$this, 'uninstall') );
	}
	
	/**
	 * Add initial ST options in DB
	 *
	 */
	function installSimpleTags() {
		$options_from_table = get_option( $this->db_options );
		if ( !$options_from_table ) {
			$this->resetToDefaultOptions();
		}
	}

	/**
	 * Remove ST options when user delete plugin (use WP API Uninstall)
	 *
	 */
	function uninstall() {
		delete_option($this->db_options, $this->default_options);
		delete_option('widget_stags_cloud');
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
	
	############## WP Options ##############
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
		update_option($this->db_options, $this->default_options);
		$this->options = $this->default_options;
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
?>