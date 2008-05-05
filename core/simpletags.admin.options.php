<?php
Class SimpleTags_Options {
	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {	
		add_options_page( __('Simple Tags: Options', 'simpletags'), __('Simple Tags', 'simpletags'), 'admin_simple_tags', 'st_options', array(&$this, 'pageOptions'));
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
		update_option($this->db_options, $this->default_options);
		$this->options = $this->default_options;
	}

	/**
	 * Delete Simple Tags options from DB.
	 *
	 */
	function deleteAllOptions() {
		delete_option($this->db_options, $this->default_options);
		delete_option('widget_stags_cloud');
	}
	
	/**
	 * Ouput formatted options
	 *
	 * @param array $option_data
	 * @return string
	 */
	function printOptions( $option_data ) {
		// Get actual options
		$option_actual = (array) $this->options;

		// Generate output
		$output = '';
		foreach( $option_data as $section => $options) {
			$output .= "\n" . '<div id="'. sanitize_title($section) .'"><fieldset class="options"><legend>' . $this->getNiceTitleOptions($section) . '</legend><table class="form-table">' . "\n";
			foreach((array) $options as $option) {
				// Helper
				if (  $option[2] == 'helper' ) {
						$output .= '<tr style="vertical-align: middle;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>' . "\n";
						continue;
				}

				switch ( $option[2] ) {
					case 'checkbox':
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . htmlspecialchars($option[3]) . '" ' . ( ($option_actual[ $option[0] ]) ? 'checked="checked"' : '') . ' />' . "\n";
						break;

					case 'dropdown':
						$selopts = explode('/', $option[3]);
						$seldata = '';
						foreach( (array) $selopts as $sel) {
							$seldata .= '<option value="' . $sel . '" ' .(($option_actual[ $option[0] ] == $sel) ? 'selected="selected"' : '') .' >' . ucfirst($sel) . '</option>' . "\n";
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>' . "\n";
						break;	
					
					case 'text-color':
						$input_type = '<input type="text" ' . (($option[3]>50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . htmlspecialchars($option_actual[ $option[0] ]) . '" size="' . $option[3] .'" /><div class="box_color ' . $option[0] . '"></div>' . "\n";
						break;

					case 'text':
					default:
						$input_type = '<input type="text" ' . (($option[3]>50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . htmlspecialchars($option_actual[ $option[0] ]) . '" size="' . $option[3] .'" />' . "\n";
						break;
				}

				// Additional Information
				$extra = '';
				if( !empty($option[4]) ) {
					$extra = '<div class="stpexplan">' . __($option[4]) . '</div>' . "\n";
				}

				// Output
				$output .= '<tr style="vertical-align: top;"><th scope="row"><label for="'.$option[0].'">' . __($option[1]) . '</label></th><td>' . $input_type . '	' . $extra . '</td></tr>' . "\n";
			}
			$output .= '</table>' . "\n";
			$output .= '</fieldset></div>' . "\n";
		}
		return $output;
	}
	
	/**
	 * Get nice title for tabs title option
	 *
	 * @param string $id
	 * @return string
	 */
	function getNiceTitleOptions( $id = '' ) {
		switch ( $id ) {
			case 'general':
				return __('General', 'simpletags');
				break;
			case 'administration':
				return __('Administration', 'simpletags');
				break;
			case 'metakeywords':
				return __('Meta Keyword', 'simpletags');
				break;
			case 'embeddedtags':
				return __('Embedded Tags', 'simpletags');
				break;
			case 'tagspost':
				return __('Tags for Current Post', 'simpletags');
				break;
			case 'relatedposts':
				return __('Related Posts', 'simpletags');
				break;
			case 'relatedtags':
				return __('Related Tags', 'simpletags');
				break;
			case 'tagcloud':
				return __('Tag cloud', 'simpletags');
				break;
		}
		return '';
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
	 * WP Page - Tags options
	 *
	 */
	function pageOptions() {
		$option_data = array(
			'general' => array(
				array('use_tag_pages', __('Active tags for page:', 'simpletags'), 'checkbox', '1',
					__('This feature allow page to be tagged. This option add pages in tags search. Also this feature add tag management in write page.', 'simpletags')),
				array('allow_embed_tcloud', __('Allow tag cloud in post/page content:', 'simpletags'), 'checkbox', '1',
					__('Enabling this will allow Wordpress to look for tag cloud marker <code>&lt;!--st_tag_cloud--&gt;</code> when displaying posts. WP replace this marker by a tag cloud.', 'simpletags')),
				array('auto_link_tags', __('Active auto link tags into post content:', 'simpletags'), 'checkbox', '1',
					__('Example: You have a tag called "WordPress" and your post content contains "wordpress", this feature will replace "wordpress" by a link to "wordpress" tags page. (http://myblog.net/tag/wordpress/)', 'simpletags')),
				array('auto_link_min', __('Min usage for auto link tags:', 'simpletags'), 'text', 10,
					__('This parameter allows to fix a minimal value of use of tags. Default: 1.', 'simpletags')),
				array('auto_link_case', __('Ignore case for auto link feature ?', 'simpletags'), 'checkbox', '1',
					__('Example: If you ignore case, auto link feature will replace the word "wordpress" by the tag link "WordPress".', 'simpletags')),
				array('no_follow', __('Add the rel="nofollow" on each tags link ?', 'simpletags'), 'checkbox', '1',
					__("Nofollow is a non-standard HTML attribute value used to instruct search engines that a hyperlink should not influence the link target's ranking in the search engine's index.",'simpletags'))
			),
			'administration' => array(
				array('use_click_tags', __('Activate click tags feature:', 'simpletags'), 'checkbox', '1',
					__('This feature add a link allowing you to display all the tags of your database. Once displayed, you can click over to add tags to post.', 'simpletags')),
				array('use_autocompletion', __('Activate autocompletion feature:', 'simpletags'), 'checkbox', '1',
					__('This feature displays a visual help allowing to enter tags more easily.', 'simpletags')),
				array('use_suggested_tags', __('Activate suggested tags feature: (Yahoo! Term Extraction API, Tag The Net, Local DB)', 'simpletags'), 'checkbox', '1',
					__('This feature add a box allowing you get suggested tags, by comparing post content and various sources of tags. (external and internal)', 'simpletags'))					
			),
			'metakeywords' => array(
				array('meta_autoheader', __('Automatically include in header:', 'simpletags'), 'checkbox', '1',
					__('Includes the meta keywords tag automatically in your header (most, but not all, themes support this). These keywords are sometimes used by search engines.<br /><strong>Warning:</strong> If the plugin "All in One SEO Pack" is installed and enabled. This feature is automatically disabled.', 'simpletags')),
				array('meta_always_include', __('Always add these keywords:', 'simpletags'), 'text', 80),
				array('meta_keywords_qty', __('Max keywords display:', 'simpletags'), 'text', 10,
					__('You must set zero (0) for display all keywords in HTML header.', 'simpletags')),				
			),
			'embeddedtags' => array(
				array('use_embed_tags', __('Use embedded tags:', 'simpletags'), 'checkbox', '1',
					__('Enabling this will allow Wordpress to look for embedded tags when saving and displaying posts. Such set of tags is marked <code>[tags]like this, and this[/tags]</code>, and is added to the post when the post is saved, but does not display on the post.', 'simpletags')),
				array('start_embed_tags', __('Prefix for embedded tags:', 'simpletags'), 'text', 40),
				array('end_embed_tags', __('Suffix for embedded tags:', 'simpletags'), 'text', 40)
			),
			'tagspost' => array(
				array('tt_feed', __('Automatically display tags list into feeds', 'simpletags'), 'checkbox', '1'),
				array('tt_embedded', __('Automatically display tags list into post content:', 'simpletags'), 'dropdown', 'no/all/blogonly/feedonly/homeonly/singularonly/pageonly/singleonly',
					'<ul>
						<li>'.__('<code>no</code> &ndash; Nowhere (default)', 'simpletags').'</li>
						<li>'.__('<code>all</code> &ndash; On your blog and feeds.', 'simpletags').'</li>
						<li>'.__('<code>blogonly</code> &ndash; Only on your blog.', 'simpletags').'</li>
						<li>'.__('<code>homeonly</code> &ndash; Only on your home page.', 'simpletags').'</li>
						<li>'.__('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simpletags').'</li>
						<li>'.__('<code>singleonly</code> &ndash; Only on your single view.', 'simpletags').'</li>
						<li>'.__('<code>pageonly</code> &ndash; Only on your page view.', 'simpletags').'</li>
					</ul>'),
				array('tt_separator', __('Post tag separator string:', 'simpletags'), 'text', 10),				
				array('tt_before', __('Text to display before tags list:', 'simpletags'), 'text', 40),
				array('tt_after', __('Text to display after tags list:', 'simpletags'), 'text', 40),
				array('tt_number', __('Max tags display:', 'simpletags'), 'text', 10,
					__('You must set zero (0) for display all tags.', 'simpletags')),
				array('tt_inc_cats', __('Include categories in result ?', 'simpletags'), 'checkbox', '1'),
				array('tt_xformat', __('Tag link format:', 'simpletags'), 'text', 80,
					__('You can find markers and explanations <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">in the online documentation.</a>', 'simpletags')),
				array('tt_notagstext', __('Text to display if no tags found:', 'simpletags'), 'text', 80),
				array('tt_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_the_tags()</code> function to customize display. See <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">documentation</a> for more details.', 'simpletags'))
			),
			'relatedposts' => array(
				array('rp_feed', __('Automatically display related posts into feeds', 'simpletags'), 'checkbox', '1'),
				array('rp_embedded', __('Automatically display related posts into post content', 'simpletags'), 'dropdown', 'no/all/blogonly/feedonly/homeonly/singularonly/pageonly/singleonly',
					'<ul>
						<li>'.__('<code>no</code> &ndash; Nowhere (default)', 'simpletags').'</li>
						<li>'.__('<code>all</code> &ndash; On your blog and feeds.', 'simpletags').'</li>
						<li>'.__('<code>blogonly</code> &ndash; Only on your blog.', 'simpletags').'</li>
						<li>'.__('<code>homeonly</code> &ndash; Only on your home page.', 'simpletags').'</li>
						<li>'.__('<code>singularonly</code> &ndash; Only on your singular view (single & page).', 'simpletags').'</li>
						<li>'.__('<code>singleonly</code> &ndash; Only on your single view.', 'simpletags').'</li>
						<li>'.__('<code>pageonly</code> &ndash; Only on your page view.', 'simpletags').'</li>
					</ul>'),
				array('rp_order', __('Related Posts Order:', 'simpletags'), 'dropdown', 'count-asc/count-desc/date-asc/date-desc/name-asc/name-desc/random',
					'<ul>
						<li>'.__('<code>date-asc</code> &ndash; Older Entries.', 'simpletags').'</li>
						<li>'.__('<code>date-desc</code> &ndash; Newer Entries.', 'simpletags').'</li>
						<li>'.__('<code>count-asc</code> &ndash; Least common tags between posts', 'simpletags').'</li>
						<li>'.__('<code>count-desc</code> &ndash; Most common tags between posts (default)', 'simpletags').'</li>
						<li>'.__('<code>name-asc</code> &ndash; Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>random</code> &ndash; Random.', 'simpletags').'</li>
					</ul>'),
				array('rp_xformat', __('Post link format:', 'simpletags'), 'text', 80,
					__('You can find markers and explanations <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">in the online documentation.</a>', 'simpletags')),
				array('rp_limit_qty', __('Maximum number of related posts to display: (default: 5)', 'simpletags'), 'text', 10),
				array('rp_notagstext', __('Enter the text to show when there is no related post:', 'simpletags'), 'text', 80),
				array('rp_title', __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'), 'text', 80),
				array('rp_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_related_posts()</code>function to customize display. See <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">documentation</a> for more details.', 'simpletags'))
			),
			'relatedtags' => array(
				array('rt_number', __('Maximum number of related tags to display: (default: 5)', 'simpletags'), 'text', 10),
				array('rt_order', __('Order related tags:', 'simpletags'), 'dropdown', 'count-asc/count-desc/name-asc/name-desc/random',
					'<ul>
						<li>'.__('<code>count-asc</code> &ndash; Least used.', 'simpletags').'</li>
						<li>'.__('<code>count-desc</code> &ndash; Most popular. (default)', 'simpletags').'</li>
						<li>'.__('<code>name-asc</code> &ndash; Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>random</code> &ndash; Random.', 'simpletags').'</li>
					</ul>'),
				array('rt_format', __('Related tags type format:', 'simpletags'), 'dropdown', 'list/flat',
					'<ul>
						<li>'.__('<code>list</code> &ndash; Display a formatted list (ul/li).', 'simpletags').'</li>
						<li>'.__('<code>flat</code> &ndash; Display inline (no list, just a div)', 'simpletags').'</li>
					</ul>'),
				array('rt_method', __('Method of tags intersections and unions used to build related tags link:', 'simpletags'), 'dropdown', 'OR/AND',
					'<ul>
						<li>'.__('<code>OR</code> &ndash; Fetches posts with either the "Tag1" <strong>or</strong> the "Tag2" tag. (default)', 'simpletags').'</li>
						<li>'.__('<code>AND</code> &ndash; Fetches posts with both the "Tag1" <strong>and</strong> the "Tag2" tag.', 'simpletags').'</li>
					</ul>'),
				array('rt_xformat', __('Related tags link format:', 'simpletags'), 'text', 80,
					__('You can find markers and explanations <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">in the online documentation.</a>', 'simpletags')),
				array('rt_separator', __('Related tags separator:', 'simpletags'), 'text', 10,
					__('Leave empty for list format.', 'simpletags')),
				array('rt_notagstext', __('Enter the text to show when there is no related tags:', 'simpletags'), 'text', 80),
				array('rt_title', __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'), 'text', 80),
				array('rt_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_related_tags()</code>function to customize display. See <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">documentation</a> for more details.', 'simpletags')),
				// Remove related tags
				array('text_helper', 'text_helper', 'helper', '', '<h3>'.__('Remove related Tags', 'simpletags').'</h3>'),
				array('rt_format', __('Remove related Tags type format:', 'simpletags'), 'dropdown', 'list/flat',
					'<ul>
						<li>'.__('<code>list</code> &ndash; Display a formatted list (ul/li).', 'simpletags').'</li>
						<li>'.__('<code>flat</code> &ndash; Display inline (no list, just a div)', 'simpletags').'</li>
					</ul>'),
				array('rt_remove_separator', __('Remove related tags separator:', 'simpletags'), 'text', 10,
					__('Leave empty for list format.', 'simpletags')),
				array('rt_remove_notagstext', __('Enter the text to show when there is no remove related tags:', 'simpletags'), 'text', 80),
				array('rt_remove_xformat', __('Remove related tags  link format:', 'simpletags'), 'text', 80,
					__('You can find markers and explanations <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">in the online documentation.</a>', 'simpletags')),		
			),
			'tagcloud' => array(
				array('text_helper', 'text_helper', 'helper', '', __('Which difference between <strong>&#8216;Order tags selection&#8217;</strong> and <strong>&#8216;Order tags display&#8217;</strong> ?<br />', 'simpletags')
					. '<ul style="list-style:square;">
						<li>'.__('<strong>&#8216;Order tags selection&#8217;</strong> is the first step during tag\'s cloud generation, corresponding to collect tags.', 'simpletags').'</li>
						<li>'.__('<strong>&#8216;Order tags display&#8217;</strong> is the second. Once tags choosen, you can reorder them before display.', 'simpletags').'</li>
					</ul>'.
					__('<strong>Example:</strong> You want display randomly the 100 tags most popular.<br />', 'simpletags').
					__('You must set &#8216;Order tags selection&#8217; to <strong>count-desc</strong> for retrieve the 100 tags most popular and &#8216;Order tags display&#8217; to <strong>random</strong> for randomize cloud.', 'simpletags')),
				array('cloud_selection', __('Order tags selection:', 'simpletags'), 'dropdown', 'count-asc/count-desc/name-asc/name-desc/random',
					'<ul>
						<li>'.__('<code>count-asc</code> &ndash; Least used.', 'simpletags').'</li>
						<li>'.__('<code>count-desc</code> &ndash; Most popular. (default)', 'simpletags').'</li>
						<li>'.__('<code>name-asc</code> &ndash; Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>random</code> &ndash; Random.', 'simpletags').'</li>
					</ul>'),
				array('cloud_sort', __('Order tags display:', 'simpletags'), 'dropdown', 'count-asc/count-desc/name-asc/name-desc/random',
					'<ul>
						<li>'.__('<code>count-asc</code> &ndash; Least used.', 'simpletags').'</li>
						<li>'.__('<code>count-desc</code> &ndash; Most popular.', 'simpletags').'</li>
						<li>'.__('<code>name-asc</code> &ndash; Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>name-desc</code> &ndash; Inverse Alphabetical.', 'simpletags').'</li>
						<li>'.__('<code>random</code> &ndash; Random. (default)', 'simpletags').'</li>
					</ul>'),
				array('cloud_inc_cats', __('Include categories in tag cloud ?', 'simpletags'), 'checkbox', '1'),
				array('cloud_format', __('Tags cloud type format:', 'simpletags'), 'dropdown', 'list/flat',
					'<ul>
						<li>'.__('<code>list</code> &ndash; Display a formatted list (ul/li).', 'simpletags').'</li>
						<li>'.__('<code>flat</code> &ndash; Display inline (no list, just a div)', 'simpletags').'</li>
					</ul>'),
				array('cloud_xformat', __('Tag link format:', 'simpletags'), 'text', 80,
					__('You can find markers and explanations <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">in the online documentation.</a>', 'simpletags')),
				array('cloud_limit_qty', __('Maximum number of tags to display: (default: 45)', 'simpletags'), 'text', 10),
				array('cloud_notagstext', __('Enter the text to show when there is no tag:', 'simpletags'), 'text', 80),
				array('cloud_title', __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'), 'text', 80),
				array('cloud_max_color', __('Most popular color:', 'simpletags'), 'text-color', 10,
					__("The colours are hexadecimal colours,  and need to have the full six digits (#eee is the shorthand version of #eeeeee).", 'simpletags')),
				array('cloud_min_color', __('Least popular color:', 'simpletags'), 'text-color', 10),
				array('cloud_max_size', __('Most popular font size:', 'simpletags'), 'text', 10,
					__("The two font sizes are the size of the largest and smallest tags.", 'simpletags')),
				array('cloud_min_size', __('Least popular font size:', 'simpletags'), 'text', 10),
				array('cloud_unit', __('The units to display the font sizes with, on tag clouds:', 'simpletags'), 'dropdown', 'pt/px/em/%',
					__("The font size units option determines the units that the two font sizes use.", 'simpletags')),
				array('cloud_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_tag_cloud()</code> function to customize display. See <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">documentation</a> for more details.', 'simpletags'))
			),
		);

		// Update or reset options
		if ( isset($_POST['updateoptions']) ) {
			foreach((array) $this->options as $key => $value) {
				$newval = ( isset($_POST[$key]) ) ? stripslashes($_POST[$key]) : '0';
				$skipped_options = array('use_auto_tags', 'auto_list');
				if ( $newval != $value && !in_array($key, $skipped_options) ) {
					$this->setOption( $key, $newval );
				}
			}
			$this->saveOptions();
			$this->message = __('Options saved', 'simpletags');
			$this->status = 'updated';
		} elseif ( isset($_POST['reset_options']) ) {
			$this->resetToDefaultOptions();
			$this->message = __('Simple Tags options resetted to default options!', 'simpletags');
		}
		
		// Delete all options ?
		if ( $_POST['delete_all_options'] == 'true' ) {
			$this->deleteAllOptions();		
			$this->message = sprintf( __('All Simple Tags options are deleted ! You <a href="%s">deactive plugin</a> now !', 'simpletags'), $this->info['siteurl']. '/wp-admin/plugins.php');	
		}
		$this->displayMessage();
	    ?>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/jquery.tabs.pack.js?ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/helper-options.js?ver=<?php echo $this->version; ?>"></script>
		<div id="wpbody"><div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<form action="<?php echo $this->admin_base_url.'st_options'; ?>" method="post">
				<p>
					<input class="button" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>

				<div id="printOptions">
					<ul class="st_submenu">
						<?php foreach ( $option_data as $key => $val ) {
							echo '<li><a href="#'. sanitize_title ( $key ) .'">'.$this->getNiceTitleOptions($key).'</a></li>';
						} ?>
						<li><a href="#uninstallation"><?php _e('Uninstallation', 'simpletags'); ?></a></li>
					</ul>

					<?php echo $this->printOptions( $option_data ); ?>
					
					<div id="uninstallation" style="padding:0 30px;">
						<p><?php _e('Generally, deactivating this plugin does not erase any of its data, if you like to quit using Simple Tags for good, please erase <strong>all</strong> options before deactivating the plugin.', 'simpletags'); ?></p>
						<p><?php _e('This erases all Simple Tags options. <strong>This is irrevocable! Be careful.</strong>', 'simpletags'); ?></p>
						<p>
							<input type="checkbox" value="true" name="delete_all_options" id="delete_all_options" />
							<label for="delete_all_options"><?php _e('Delete all options ?', 'simpletags'); ?></label></p>						
					</div>
				</div>

				<p>
					<input class="button" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>
			</form>
	    <?php $this->printAdminFooter(); ?>
	    </div></div>
	    <?php
	}
}

?>