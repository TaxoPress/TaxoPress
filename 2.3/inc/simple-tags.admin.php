<?php
Class SimpleTagsAdmin {
	var $version = '';

	var $info;
	var $options;
	var $default_options;
	var $db_options = 'simpletags';

	var $admin_base_url = '';

	// Error management
	var $message = '';
	var $status = '';

	// Generic pagination
	var $datas;
	var $found_datas = 0;
	var $max_num_pages = 0;
	var $data_per_page = 20;
	var $actual_page = 1;

	// Tags for Editor
	var $all_tags = false;

	// Tags list (management)
	var $nb_tags = 50;

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return SimpleTagsAdmin
	 */
	function SimpleTagsAdmin( $default_options = array(), $version = '', $info = array() ) {	
		// 1. load version number
		$this->version = $version;
		unset($version);

		// 2. Set class property for default options
		$this->default_options = $default_options;

		// 3. Get options from WP
		$options_from_table = get_option( $this->db_options );

		// 4. Update default options by getting not empty values from options table
		foreach( (array) $default_options as $default_options_name => $default_options_value ) {
			if ( !is_null($options_from_table[$default_options_name]) ) {
				if ( is_int($default_options_value) ) {
					$default_options[$default_options_name] = (int) $options_from_table[$default_options_name];
				} else {
					$default_options[$default_options_name] = $options_from_table[$default_options_name];
				}
			}
		}

		// 5. Set the class property and unset no used variable
		$this->options = $default_options;
		unset($default_options);
		unset($options_from_table);
		unset($default_options_value);

		// 6. Get info data from constructor
		$this->info = $info;
		unset($info);

		// 8. Admin URL and Pagination
		$this->admin_base_url = $this->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset($_GET['pagination']) ) {
			$this->actual_page = (int) $_GET['pagination'];
		}

		// 9. Admin Capabilities
		$role = get_role('administrator');
		if( !$role->has_cap('simple_tags') ) {
			$role->add_cap('simple_tags');
		}

		// 10. Admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));

		// 11. Ajax action and JS Helper
		add_action('init', array(&$this, 'ajaxCheck'));

		// 12. ST CSS Helper
		add_action('admin_head', array(&$this, 'helperCSS'));

		// 13. Embedded Tags
		if ( $this->options['use_embed_tags'] == 1 ) {
			add_action('save_post', array(&$this, 'saveEmbedTags'));
			add_action('publish_post', array(&$this, 'saveEmbedTags'));		
      add_action('post_syndicated_item', array(&$this, 'saveEmbedTags'));	
		}

		// 14. Auto tags
		if ( $this->options['use_auto_tags'] == 1 ) {
			add_action('save_post', array(&$this, 'saveAutoTags'));
			add_action('publish_post', array(&$this, 'saveAutoTags'));
			add_action('post_syndicated_item', array(&$this, 'saveAutoTags'));
		}

		// 15. Tags helper for page
		if ( $this->options['use_tag_pages'] == 1 ) {
			add_action('edit_page_form', array(&$this, 'helperTagsPage'), 1); // Tag input
			
			if ( $this->options['use_autocompletion'] == 1 ) {
				add_action('dbx_page_advanced', array(&$this, 'helperBCompleteJS'));
			}			
			if ( $this->options['use_click_tags'] == 1 ) {
				add_action('edit_page_form', array(&$this, 'helperClickTags'), 1);
			}
			if ( $this->options['use_suggested_tags'] == 1 ) {
				add_action('edit_page_form', array(&$this, 'helperSuggestTags'), 1);
			}
		}

		// 16. Tags helper for post
		if ( $this->options['use_autocompletion'] == 1 ) {
			add_action('dbx_post_advanced', array(&$this, 'helperBCompleteJS'));
		}
		if ( $this->options['use_click_tags'] == 1 ) {
			add_action('edit_form_advanced', array(&$this, 'helperClickTags'), 1);
		}
		if ( $this->options['use_suggested_tags'] == 1 ) {
			add_action('edit_form_advanced', array(&$this, 'helperSuggestTags'), 1);
		}

		// 17. Helper JS & jQuery & Prototype
		global $pagenow;
		$wp_pages = array('post.php', 'post-new.php', 'page.php', 'page-new.php');
		$st_pages = array('st_manage', 'st_mass_tags', 'st_auto', 'st_mass_terms', 'st_options');
		if ( in_array($pagenow, $wp_pages) || in_array($_GET['page'], $st_pages) ) {
			wp_enqueue_script('jquery');
			wp_enqueue_script('prototype');
		}

		// 18. Helper Bcomplete JS
		if ( $_GET['page'] == 'st_mass_tags' && $this->options['use_autocompletion'] == 1 ) {
			add_action('admin_head', array(&$this, 'helperMassBCompleteJS'));
		}

		return;
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {	
		add_management_page( __('Simple Tags: Manage Tags', 'simpletags'), __('Manage Tags', 'simpletags'), 'simple_tags', 'st_manage', array(&$this, 'pageManageTags'));
		add_management_page( __('Simple Tags: Mass Edit Tags', 'simpletags'), __('Mass Edit Tags', 'simpletags'), 'simple_tags', 'st_mass_tags', array(&$this, 'pageMassEditTags'));
		add_management_page( __('Simple Tags: Auto Tags', 'simpletags'), __('Auto Tags', 'simpletags'), 'simple_tags', 'st_auto', array(&$this, 'pageAutoTags'));	
		add_options_page( __('Simple Tags: Options', 'simpletags'), __('Simple Tags', 'simpletags'), 'simple_tags', 'st_options', array(&$this, 'pageOptions'));
	}

	/**
	 * WP Page - Auto Tags
	 *
	 */
	function pageAutoTags() {
		$action = false;
		if ( isset($_POST['update_auto_list']) ) {
			// Tags list
			$tags_list = stripslashes($_POST['auto_list']);
			$tags = explode(',', $tags_list);

			// Remove empty and duplicate elements
			$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));
			$tags = array_unique($tags);

			$this->setOption( 'auto_list', maybe_serialize($tags) );

			// Active auto tags ?
			if ( $_POST['use_auto_tags'] == '1' ) {
				$this->setOption( 'use_auto_tags', '1' );
			} else {
				$this->setOption( 'use_auto_tags', '0' );
			}
			
			// All tags ?
			if ( $_POST['at_all'] == '1' ) {
				$this->setOption( 'at_all', '1' );
			} else {
				$this->setOption( 'at_all', '0' );
			}
			
			// Empty only ?
			if ( $_POST['at_empty'] == '1' ) {
				$this->setOption( 'at_empty', '1' );
			} else {
				$this->setOption( 'at_empty', '0' );
			}

			$this->saveOptions();
			$this->message = __('Auto tags options updated !', 'simpletags');
		} elseif ( $_GET['action'] == 'auto_tag' ) {
			$action = true;
			$n = ( isset($_GET['n']) ) ? intval($_GET['n']) : 0;
		}

		$tags_list = '';
		$tags = maybe_unserialize($this->options['auto_list']);
		if ( is_array($tags) ) {
			$tags_list = implode(', ', $tags);
		}

		$this->displayMessage();
		?>
		<div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Auto Tags', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

			<?php if ( $action === false ) : ?>

				<h3><?php _e('Auto tags list', 'simpletags'); ?></h3>
				<p><?php _e('This feature allows Wordpress to look into post content and title for specified tags when saving posts. If your post content or title contains the word "WordPress" and you have "wordpress" in auto tags list, Simple Tags will add automatically "wordpress" as tag for this post.', 'simpletags'); ?></p>

				<form action="<?php echo $this->admin_base_url.'st_auto'; ?>" method="post">
					<p><input type="checkbox" id="use_auto_tags" name="use_auto_tags" value="1" <?php echo ( $this->options['use_auto_tags'] == 1 ) ? 'checked="checked"' : ''; ?>  />
						<label for="use_auto_tags"><?php _e('Active Auto Tags.', 'simpletags'); ?></label></p>
						
					<p><input type="checkbox" id="at_all" name="at_all" value="1" <?php echo ( $this->options['at_all'] == 1 ) ? 'checked="checked"' : ''; ?>  />
						<label for="at_all"><?php _e('Use also local tags database with auto tags. (Warning, this option can increases the CPU consumption a lot if you have many tags)', 'simpletags'); ?></label></p>
						
					<p><input type="checkbox" id="at_empty" name="at_empty" value="1" <?php echo ( $this->options['at_empty'] == 1 ) ? 'checked="checked"' : ''; ?>  />
						<label for="at_empty"><?php _e('Autotag only posts without tags.', 'simpletags'); ?></label></p>

					<p><label for="auto_list"><?php _e('Keywords list: (separated with a comma)', 'simpletags'); ?></label><br />
						<input type="text" id="auto_list" class="auto_list" name="auto_list" value="<?php echo $tags_list; ?>" /></p>

					<?php $this->helperBCompleteJS( 'auto_list', false  ); ?>

					<p class="submit">
						<input type="submit" name="update_auto_list" value="<?php _e('Update list &raquo;', 'simpletags'); ?>" />
				</form>

				<h3><?php _e('Auto tags old content', 'simpletags'); ?></h3>
				<p><?php _e('Simple Tags can also tag all existing contents of your blog. This feature use auto tags list above-mentioned.', 'simpletags'); ?>
					<br /><strong><a href="<?php echo $this->admin_base_url.'st_auto'; ?>&amp;action=auto_tag"><?php _e('Auto tags all content', 'simpletags'); ?></a></strong></p>

			<?php else:

				// Page or not ?
				$post_type_sql = ( $this->options['use_tag_pages'] == '1' ) ? "post_type IN('page', 'post')" : "post_type = 'post'";

				// Get objects
				global $wpdb;
				$objects = (array) $wpdb->get_results("SELECT p.ID, p.post_title, p.post_content FROM {$wpdb->posts} p WHERE {$post_type_sql} ORDER BY ID DESC LIMIT {$n}, 20");
				
				if( !empty($objects) ) {
					echo '<ul>';
					foreach( $objects as $object ) {
						$this->autoTagsPost( $object->ID, $object->post_content, $object->post_title );			

						echo '<li>#'. $object->ID .' '. $object->post_title .'</li>';
						unset($object);
					}					
					echo '</ul>';
					?>
					<p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?> <a href="<?php echo $this->admin_base_url.'st_auto'; ?>&amp;action=auto_tag&amp;n=<?php echo ($n + 20) ?>"><?php _e('Next content', 'simpletags'); ?></a></p>
					<script type="text/javascript">
						// <![CDATA[
						function nextPage() {
							location.href = '<?php echo $this->admin_base_url.'st_auto'; ?>&action=auto_tag&n=<?php echo ($n + 20) ?>';
						}
						window.setTimeout( 'nextPage()', 300 );
						// ]]>
					</script>
					<?php
				} else {
					wp_cache_flush();
					echo '<p><strong>'.__('All done!', 'simpletags').'</strong></p>';
				}
				
			endif;
			$this->printAdminFooter(); ?>
		</div>
		<?php
	}

	/**
	 * WP Page - Tags options
	 *
	 */
	function pageOptions() {
		$option_data = array(
			'general' => array(
				array('inc_page_tag_search', __('Include page in tag search:', 'simpletags'), 'checkbox', '1',
					__('This feature need that option "Add page in tags management" is enabled.', 'simpletags')),
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
				array('use_tag_pages', __('Add page in tags management:', 'simpletags'), 'checkbox', '1',
					__('Add a tag input (and tag posts features) in page edition', 'simpletags')),
				array('use_click_tags', __('Activate click tags feature:', 'simpletags'), 'checkbox', '1',
					__('This feature add a link allowing you to display all the tags of your database. Once displayed, you can click over to add tags to post.', 'simpletags')),
				array('use_autocompletion', __('Activate autocompletion feature:', 'simpletags'), 'checkbox', '1',
					__('This feature displays a visual help allowing to enter tags more easily.', 'simpletags')),
				array('use_suggested_tags', __('Activate suggested tags feature: (Yahoo! Term Extraction API, Tag The Net, Local DB)', 'simpletags'), 'checkbox', '1',
					__('This feature add a box allowing you get suggested tags, by comparing post content and various sources of tags. (external and internal)', 'simpletags'))					
			),
			'metakeywords' => array(
				array('meta_autoheader', __('Automatically include in header:', 'simpletags'), 'checkbox', '1',
					__('Includes the meta keywords tag automatically in your header (most, but not all, themes support this). These keywords are sometimes used by search engines.<br /><strong>Warning:</strong> If the plugin "All in One SEO Pack" is installed and enabled. This feature is disabled.', 'simpletags')),
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
				array('text_helper', 'text_helper', 'helper', '', '<hr /><h2>'.__('Remove related Tags', 'simpletags').'</h2>'),
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
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/tabs/jquery.tabs.pack.js?ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript">
			jQuery(document).ready( function() {
				jQuery('#printOptions').tabs({fxSlide: true});
				
				jQuery('input#cloud_max_color')
					.ready(function(){cloudMaxColor()})
					.click(function(){cloudMaxColor()})
					.blur(function(){cloudMaxColor()})
					.change(function(){cloudMaxColor()})
					.focus(function(){cloudMaxColor()});					
				function cloudMaxColor() {
					jQuery('div.cloud_max_color').css({
						backgroundColor: jQuery('input#cloud_max_color').val()
					});
				}
				
				jQuery('input#cloud_min_color')
					.ready(function(){cloudMinColor()})
					.click(function(){cloudMinColor()})
					.blur(function(){cloudMinColor()})
					.change(function(){cloudMinColor()})
					.focus(function(){cloudMinColor()});					
				function cloudMinColor() {
					jQuery('div.cloud_min_color').css({
						backgroundColor: jQuery('input#cloud_min_color').val()
					});
				}				
			});
		</script>
	    <div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<form action="<?php echo $this->admin_base_url.'st_options'; ?>" method="post">
				<p class="submit">
					<input type="submit" name="updateoptions" value="<?php _e('Update Options &raquo;', 'simpletags'); ?>" />
					<input type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>

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

				<p class="submit">
					<input type="submit" name="updateoptions" value="<?php _e('Update Options &raquo;', 'simpletags'); ?>" />
					<input type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>
			</form>
	    <?php $this->printAdminFooter(); ?>
	    </div>

	    <?php
	}

	/**
	 * WP Page - Manage tags
	 *
	 */
	function pageManageTags() {
		// Control Post data
		if ( isset($_POST['tag_action']) ) {
			// Origination and intention
			if ( !wp_verify_nonce($_POST['tag_nonce'], 'simpletags_admin') ) {
				$this->message = __('Security problem. Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
			}
			elseif ( $_POST['tag_action'] == 'renametag' ) {
				$oldtag = (isset($_POST['renametag_old'])) ? $_POST['renametag_old'] : '';
				$newtag = (isset($_POST['renametag_new'])) ? $_POST['renametag_new'] : '';
				$this->renameTags( $oldtag, $newtag );
			}
			elseif ( $_POST['tag_action'] == 'deletetag' ) {
				$todelete = (isset($_POST['deletetag_name'])) ? $_POST['deletetag_name'] : '';
				$this->deleteTagsByTagList( $todelete );
			}
			elseif ( $_POST['tag_action'] == 'addtag'  ) {
				$matchtag = (isset($_POST['addtag_match'])) ? $_POST['addtag_match'] : '';
				$newtag   = (isset($_POST['addtag_new'])) ? $_POST['addtag_new'] : '';
				$this->addMatchTags( $matchtag, $newtag );
			}
			elseif ( $_POST['tag_action'] == 'editslug'  ) {
				$matchtag = (isset($_POST['tagname_match'])) ? $_POST['tagname_match'] : '';
				$newslug   = (isset($_POST['tagslug_new'])) ? $_POST['tagslug_new'] : '';
				$this->editTagSlug( $matchtag, $newslug );
			} elseif ( $_POST['tag_action'] == 'cleandb'  ) {
				$this->cleanDatabase();
			}
		}

		// Manage URL
		$sort_order = ( isset($_GET['tag_sortorder']) ) ? attribute_escape(stripslashes($_GET['tag_sortorder'])) : 'desc';
		$search_url = ( isset($_GET['search']) ) ? '&amp;search=' . stripslashes($_GET['search']) : '';
		$action_url = $this->admin_base_url . attribute_escape(stripslashes($_GET['page'])) . '&amp;tag_sortorder=' . $sort_order. $search_url;

		// TagsFilters
		$order_array = array(
			'desc' => __('Most popular', 'simpletags'),
			'asc' => __('Least used', 'simpletags'),
			'natural' => __('Alphabetical', 'simpletags'));

		// Build Tags Param
		switch ($sort_order) {
			case 'natural' :
				$param = 'number='.$this->nb_tags.'&hide_empty=false&cloud_selection=name-asc';
				break;
			case 'asc' :
				$param = 'number='.$this->nb_tags.'&hide_empty=false&cloud_selection=count-asc';
				break;
			default :
				$param = 'number='.$this->nb_tags.'&hide_empty=false&cloud_selection=count-desc';
				break;
		}


		// Search
		if ( !empty($_GET['search']) ) {
			$search = stripslashes($_GET['search']);
			$param = str_replace('number='.$this->nb_tags, 'number=200&st_name_like='.$search, $param );
		}

		$this->displayMessage();
		?>
		<div class="wrap st_wrap">
			<h2><?php _e('Simple Tags: Manage Tags', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<table>
				<tr>
					<td class="list_tags">
						<fieldset class="options" id="taglist">
							<legend><?php _e('Existing Tags', 'simpletags'); ?></legend>

							<form method="get">
								<p>
									<label for="search"><?php _e('Search tags', 'simpletags'); ?></label><br />
									<input type="hidden" name="page" value="<?php echo attribute_escape(stripslashes($_GET['page'])); ?>" />
									<input type="hidden" name="tag_sortorder" value="<?php echo $sort_order; ?>" />
									<input type="text" name="search" id="search" size="10" value="<?php echo stripslashes($_GET['search']); ?>" />
									<input class="button" type="submit" value="<?php _e('Go', 'simpletags'); ?>" /></p>
							</form>

							<div><?php _e('Sort Order:', 'simpletags'); ?></div>
							<p style="margin:0 0 10px 10px; padding:0;">
								<?php
								foreach( $order_array as $sort => $title ) {
									echo ($sort == $sort_order) ? '<span style="color: red;">'.$title.'</span><br />' : '<a href="'.$this->admin_base_url.attribute_escape(stripslashes($_GET['page'])).'&amp;tag_sortorder='.$sort.$search_url.'">'.$title.'</a><br/>';
								}
								?>
							</p>

							<div id="ajax_area_tagslist">
								<ul>
									<?php
									global $simple_tags;
									$tags = (array) $simple_tags->getTags($param, true);
									foreach( $tags as $tag ) {
										echo '<li><strong>'.$tag->term_id.'.</strong> <span>'.$tag->name.'</span>&nbsp;<a href="'.(get_tag_link( $tag->term_id )).'" title="'.sprintf(__('View all posts tagged with %s', 'simpletags'), $tag->name).'">('.$tag->count.')</a></li>'."\n";
									}
									unset($tags);
									?>
								</ul>

								<?php
									$total = wp_count_terms('post_tag');
									if ( empty($_GET['search']) && ($total > $this->nb_tags ) ) :
								?>
								<div class="navigation">
									<a href="<?php echo get_option('siteurl'). '/wp-admin/admin.php?st_ajax_action=get_tags&amp;pagination=1'. ( (isset($_GET['tag_sortorder'])) ? '&amp;order='.$sort_order : '' ); ?>"><?php _e('Previous tags', 'simpletags'); ?></a> | <?php _e('Next tags', 'simpletags'); ?>
								</div>
								<?php endif; ?>
							</div>
						</fieldset>
					</td>
					<td style="vertical-align: top;">

						<fieldset class="options"><legend><?php _e('Rename Tag', 'simpletags'); ?></legend>
							<p><?php _e('Enter the tag to rename and its new value.  You can use this feature to merge tags too. Click "Rename" and all posts which use this tag will be updated.', 'simpletags'); ?></p>
							<p><?php _e('You can specify multiple tags to rename by separating them with commas.', 'simpletags'); ?></p>
							<form action="<?php echo $action_url; ?>" method="post">
								<input type="hidden" name="tag_action" value="renametag" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<table>
									<tr><th><label><?php _e('Tag(s) to rename:', 'simpletags'); ?></label></th><td> <input type="text" id="renametag_old" name="renametag_old" value="" size="40" /> </td></tr>
									<tr><th><label><?php _e('New tag name(s):', 'simpletags'); ?></label></th><td> <input type="text" id="renametag_new" name="renametag_new" value="" size="40" /> </td></tr>
									<tr><th></th><td> <input class="button" type="submit" name="rename" value="<?php _e('Rename', 'simpletags'); ?>" /> </td></tr>
								</table>
							</form>
						</fieldset>

						<fieldset class="options"><legend><?php _e('Delete Tag', 'simpletags'); ?></legend>
							<p><?php _e('Enter the name of the tag to delete.  This tag will be removed from all posts.', 'simpletags'); ?></p>
							<p><?php _e('You can specify multiple tags to delete by separating them with commas', 'simpletags'); ?>.</p>
							<form action="<?php echo $action_url; ?>" method="post">
								<input type="hidden" name="tag_action" value="deletetag" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<table>
									<tr><th><label><?php _e('Tag(s) to delete:', 'simpletags'); ?></label></th><td> <input type="text" id="deletetag_name" name="deletetag_name" value="" size="40" /> </td></tr>
									<tr><th></th><td> <input class="button" type="submit" name="delete" value="<?php _e('Delete', 'simpletags'); ?>" /> </td></tr>
								</table>
							</form>
						</fieldset>

						<fieldset class="options"><legend><?php _e('Add Tag', 'simpletags'); ?></legend>
							<p><?php _e('This feature lets you add one or more new tags to all posts which match any of the tags given.', 'simpletags'); ?></p>
							<p><?php _e('You can specify multiple tags to add by separating them with commas.  If you want the tag(s) to be added to all posts, then don\'t specify any tags to match.', 'simpletags'); ?></p>
							<form action="<?php echo $action_url; ?>" method="post">
								<input type="hidden" name="tag_action" value="addtag" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<table>
									<tr><th><label><?php _e('Tag(s) to match:', 'simpletags'); ?></label></th><td> <input type="text" id="addtag_match" name="addtag_match" value="" size="40" /> </td></tr>
									<tr><th><label><?php _e('Tag(s) to add:', 'simpletags'); ?></label></th><td>   <input type="text" id="addtag_new" name="addtag_new" value="" size="40" /> </td></tr>
									<tr><th></th><td> <input class="button" type="submit" name="Add" value="<?php _e('Add', 'simpletags'); ?>" /> </td></tr>
								</table>
							</form>
						</fieldset>

						<fieldset class="options"><legend><?php _e('Edit Tag Slug', 'simpletags'); ?></legend>
							<p><?php _e('Enter the tag name to edit and its new slug. <a href="http://codex.wordpress.org/Glossary#Slug">Slug definition</a>', 'simpletags'); ?></p>
							<p><?php _e('You can specify multiple tags to rename by separating them with commas.', 'simpletags'); ?></p>
							<form action="<?php echo $action_url; ?>" method="post">
								<input type="hidden" name="tag_action" value="editslug" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<table>
									<tr>
										<th><label><?php _e('Tag(s) to match:', 'simpletags'); ?></label></th>
										<td><input type="text" id="tagname_match" name="tagname_match" value="" size="40" /></td>
									</tr>
									<tr>
										<th><label><?php _e('Slug(s) to set:', 'simpletags'); ?></label></th>
										<td><input type="text" id="tagslug_new" name="tagslug_new" value="" size="40" /></td>
									</tr>
									<tr>
										<th></th>
										<td><input class="button" type="submit" name="edit" value="<?php _e('Edit', 'simpletags'); ?>" /></td>
									</tr>
								</table>
							</form>
						</fieldset>

						<fieldset class="options"><legend><?php _e('Remove empty terms', 'simpletags'); ?></legend>
							<p><?php _e('WordPress 2.3 have a small bug and can create empty terms. Remove it !', 'simpletags'); ?></p>
							<form action="<?php echo $action_url; ?>" method="post">
								<input type="hidden" name="tag_action" value="cleandb" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<p><input class="button" type="submit" name="clean" value="<?php _e('Clean !', 'simpletags'); ?>" /></p>
							</form>
						</fieldset>
					</td>
				</tr>
			</table>
	  		<script type="text/javascript">
	  		// <![CDATA[
	  			// Register onclick event
	  			function registerClick() {
		  			jQuery('#taglist ul li span').bind("click", function(){
						addTag(this.innerHTML, "renametag_old");
						addTag(this.innerHTML, "deletetag_name");
						addTag(this.innerHTML, "addtag_match");
						addTag(this.innerHTML, "tagname_match");
					});
	  			}
	  			// Register ajax nav and reload event once ajax data loaded
				function registerAjaxNav() {
					jQuery(".navigation a").click(function() {
						jQuery("#ajax_area_tagslist").load(this.href, function(){
				  			registerClick();
				  			registerAjaxNav();
						});
						return false;
					});
				}
				// Register initial event
 				jQuery(document).ready(function() {
					registerClick();
					registerAjaxNav();
				});
				// Add tag into input
				function addTag( tag, name_element ) {
					var input_element = document.getElementById( name_element );

					if ( input_element.value.length > 0 && !input_element.value.match(/,\s*$/) )
						input_element.value += ", ";

					var re = new RegExp(tag + ",");
					if ( !input_element.value.match(re) )
						input_element.value += tag + ", ";

					return true;
				}
			// ]]>
			</script>
			<?php $this->printAdminFooter(); ?>
		</div>
		<?php
	}

	/**
	 * WP Page - Mass edit tags
	 *
	 */
	function pageMassEditTags() {
		// Search
		$search = stripslashes($_GET['s']);

		// Quantity
		$quantity = (int) stripslashes($_GET['quantity']);
		if ( $quantity < 10 || $quantity > 200 ) {
			$quantity = 20;
		}

		// Author
		$author = ( empty($_GET['author']) ) ? (int) $_GET['author'] : 0;

		// Type (future add link)
		$type = attribute_escape($_GET['type']);
		if ( $type != 'post' && $type != 'page' ) {
			$type = 'post';
		}

		// Order content
		$order = ( empty($_GET['order']) ) ? 'date_desc' : attribute_escape(stripslashes($_GET['order']));

		// Filter
		$filter = ( $_GET['filter'] == 'untagged' ) ? 'untagged' : 'all';

		// Check and update tags
		$this->checkFormMassEdit( $type );

		// Action Post URL
		$page = '';
		if ( $this->actual_page != 1 ) {
			$page = '&amp;pagination='.$this->actual_page;
		}
		$action_url = $this->admin_base_url.'st_mass_tags&amp;s='.$search.'&amp;quantity='.$quantity.'&amp;author='.$author.'&amp;type='.$type.'&amp;filter='.$filter.'&amp;order='.$order.$page;
		$objects = $this->getObjects( $type, $quantity, $author, $order, $filter, $search );

		$this->displayMessage();
		?>
		<div class="wrap st_wrap">
		<h2><?php _e('Simple Tags: Mass Edit Tags', 'simpletags'); ?></h2>
		<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/wiki/ThemeIntegration">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

		<form action="<?php echo $this->admin_base_url; ?>" id="searchform" method="get">
			<input type="hidden" name="page" value="st_mass_tags" />

			<fieldset><legend><?php _e('Search terms&hellip;', 'simpletags'); ?></legend>
				<input type="text" name="s" id="s" value="<?php echo $search; ?>" size="12" />
			</fieldset>

			<fieldset>
				<legend><?php _e('Quantity&hellip;', 'simpletags'); ?></legend>
				<select name="quantity" id="quantity">
					<option <?php if ( $quantity == 10 ) echo 'selected="selected"'; ?> value="10">10</option>
					<option <?php if ( $quantity == 20 ) echo 'selected="selected"'; ?> value="20">20</option>
					<option <?php if ( $quantity == 30 ) echo 'selected="selected"'; ?> value="30">30</option>
					<option <?php if ( $quantity == 40 ) echo 'selected="selected"'; ?> value="40">40</option>
					<option <?php if ( $quantity == 50 ) echo 'selected="selected"'; ?> value="50">50</option>
					<option <?php if ( $quantity == 100 ) echo 'selected="selected"'; ?> value="100">100</option>
					<option <?php if ( $quantity == 200 ) echo 'selected="selected"'; ?> value="200">200</option>
				</select>
			</fieldset>

			<?php
			global $user_ID;
			$editable_ids = get_editable_user_ids( $user_ID );
			if ( $editable_ids && count( $editable_ids ) > 1 ) :
			?>
				<fieldset>
				  <legend><?php _e('Author&hellip;', 'simpletags'); ?></legend>
				  <?php wp_dropdown_users( array('include' => $editable_ids, 'show_option_all' => __('Any'), 'name' => 'author', 'selected' => isset($_GET['author']) ? $_GET['author'] : 0) ); ?>
				</fieldset>
			<?php endif; ?>

			<fieldset>
				<legend><?php _e('Type&hellip;', 'simpletags'); ?></legend>
				<select name='type' id='type'>
					<option <?php if ( $type == 'post' ) { echo 'selected="selected"'; } ?> value='post'><?php _e('Post', 'simpletags'); ?></option>
					<?php if ( $this->options['use_tag_pages'] == '1' ) : ?>
					<option <?php if ( $type == 'page' ) { echo 'selected="selected"'; } ?> value='page'><?php _e('Page', 'simpletags'); ?></option>
					<?php endif; ?>
				</select>
			</fieldset>

			<fieldset>
				<legend><?php _e('Order&hellip;', 'simpletags'); ?></legend>
				<select name='order' id='order'>
					<option <?php if ( $order == 'date_desc' ) echo 'selected="selected"'; ?> value="date_desc"><?php _e('Date (descending)', 'simpletags'); ?></option>
					<option <?php if ( $order == 'date_asc' ) echo 'selected="selected"'; ?> value="date_asc"><?php _e('Date (ascending)', 'simpletags'); ?></option>
					<option <?php if ( $order == 'id_desc' ) echo 'selected="selected"'; ?> value="id_desc"><?php _e('ID (descending)', 'simpletags'); ?></option>
					<option <?php if ( $order == 'id_asc' ) echo 'selected="selected"'; ?> value="id_asc"><?php _e('ID (ascending)', 'simpletags'); ?></option>
				</select>
			</fieldset>

			<fieldset>
				<legend><?php _e('Filter&hellip;', 'simpletags'); ?></legend>
				<select name='filter' id='filter'>
					<option <?php if ( $filter == 'all' ) echo 'selected="selected"'; ?> value="all"><?php _e('All', 'simpletags'); ?></option>
					<option <?php if ( $filter == 'untagged' ) echo 'selected="selected"'; ?> value="untagged"><?php _e('Untagged only', 'simpletags'); ?></option>
				</select>
			</fieldset>

			<input type="submit" id="post-query-submit" value="<?php _e('Filter &#187;', 'simpletags'); ?>" class="button" />
			<br style="clear:both;" />
		</form>

		<?php if ( is_array($objects) && count($objects) > 0 ) : ?>
			<form name="post" id="post" action="<?php echo $action_url; ?>" method="post">
				<p class="submit">
				<input type="submit" name="update_mass" value="<?php _e('Update all', 'simpletags'); ?>" /></p>
				<?php $this->printPagination( $action_url ); ?>
				<?php
				foreach ( $objects as $object_id => $object ) {
					echo '<p><strong>#'.$object_id.'</strong> <a href="'.get_permalink($object_id).'">'.$object['title'].'</a> [<a href="'.$type.'.php?action=edit&amp;post=' .$object_id . '">' . __('Edit', 'simpletags') . '</a>]<br />'."\n";
					echo '<input id="tags-input'.$object_id.'" class="tags-input" type="text" size="100" name="tags['.$object_id.']" value="'.get_tags_to_edit( $object_id ).'" /></p>'."\n";
				}
				?>
				<p class="submit">
					<input type="hidden" name="secure_mass" value="<?php echo wp_create_nonce('st_mass_tags'); ?>" />
					<input type="submit" name="update_mass" value="<?php _e('Update all', 'simpletags'); ?>" /></p>
			</form>
			<?php $this->printPagination( $action_url ); ?>
			<?php if ( $this->all_tags === true ) : ?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						<?php foreach ( (array) $objects as $object_id => $object ) { ?>
							var tag_<?php echo $object_id; ?> = new BComplete('tags-input<?php echo $object_id; ?>');
							tag_<?php echo $object_id; ?>.setData(collection);
						<?php } ?>
					});
				</script>
			<?php endif; ?>
		<?php else: ?>
			<p><?php _e('No content to edit.', 'simpletags'); ?>
		<?php endif; ?>
		<?php $this->printAdminFooter(); ?>
    </div>
    <?php
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
			return;
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
			wp_cache_flush(); // Delete cache
		}
	}

	/**
	 * Check post data for auto tags
	 *
	 * @param integer $post_id
	 * @param array $post_data
	 */
	function saveAutoTags( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return false;
		}

		$result = $this->autoTagsPost( $object->ID, $object->post_content, $object->post_title );
		if ( $result == true ) {
			wp_cache_flush(); // Delete cache
		}
		return true;
	}
	
	function autoTagsPost( $post_id = 0, $content = '', $title = '' ) {	
		if ( get_the_tags($post_id) != false && $this->options['at_empty'] == 1 ) {
			return false; // Skip post with tags, if tag only empty post option is checked
		}
		
		$tags_to_add = array();
		
		// Auto tag with specifik auto tags list
		$tags = (array) maybe_unserialize($this->options['auto_list']);
		foreach ( $tags as $tag ) {
			if ( is_string($tag) && !empty($tag) && ( stristr($content, $tag) || stristr($title, $tag) ) ) {
				$tags_to_add[] = $tag;
			}
		}
		unset($tags, $tag);
		
		// Auto tags with all posts
		if ( $this->options['at_all'] == 1 ) { 
			global $simple_tags;
			$total = wp_count_terms('post_tag');
			$counter = 0;
			
			while ( ( $counter * 200 ) < $total ) {
				// Get tags							
				$tags = (array) $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);
				
				foreach ( $tags as $tag ) {
					if ( is_string($tag->name) && !empty($tag->name) && ( stristr($content, $tag->name) || stristr($title, $tag->name) ) ) {
						$tags_to_add[] = $tag->name;
					}
				}
				unset($tags, $tag);
				
				// Increment counter
				$counter++;
			}
		}
		
		// Append tags if tags to add
		if ( !empty($tags_to_add) ) {
			// Remove empty and duplicate elements
			$tags_to_add = array_filter($tags_to_add, array(&$this, 'deleteEmptyElement'));
			$tags_to_add = array_unique($tags_to_add);
			
			wp_set_object_terms( $post_id, $tags_to_add, 'post_tag', true );
			return true;
		}
		return false;
	}

	############## Helper Write Pages ##############
	/**
	 * Display tags input for page
	 *
	 */
	function helperTagsPage() {
		global $post_ID;
		?>
		<fieldset class="tags_page" id="tagdiv">
			<legend><?php _e('Tags (separate multiple tags with commas: cats, pet food, dogs)'); ?></legend>
			<div><input type="text" name="tags_input" class="tags-input" id="tags-input" size="30" tabindex="3" value="<?php echo get_tags_to_edit( $post_ID ); ?>" /></div>
		</fieldset>
		<?php
	}


	/**
	 * Helper type-ahead (single post)
	 *
	 */
	function helperBCompleteJS( $name_id = 'tags-input', $use_fct_js = true ) {
		if ( $use_fct_js == true ) :
		?>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/functions.js?ver=<?php echo $this->version; ?>"></script>
		<?php
		endif;

		// Get total
		$tags = (int) wp_count_terms('post_tag');

		// If no tags => exit !
		if ( $tags == 0 ) {
			return;
		}
		?>
		<script type="text/javascript" src="<?php echo $this->info['siteurl'] ?>/wp-admin/admin.php?st_ajax_action=helper_js_collection&ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<?php if ( 'rtl' == get_bloginfo( 'text_direction' ) ) : ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete-rtl.css?ver=<?php echo $this->version; ?>" />
		<?php endif; ?>
		<script type="text/javascript">
		// <![CDATA[
			jQuery(document).ready(function() {
				var tags_input = new BComplete('<?php echo ( empty($name_id) ) ? 'tags-input' : $name_id; ?>');
				tags_input.setData(collection);
			});
		// ]]>
		</script>
		<?php
	}

	############## Manages Tags Pages ##############
	/*
	 * Rename or merge tags
	 *
	 * @param string $old
	 * @param string $new
	 */
	function renameTags( $old = '', $new = '' ) {
		if ( trim( str_replace(',', '', stripslashes($new)) ) == '' ) {
			$this->message = __('No new tag specified!', 'simpletags');
			$this->status = 'error';
			return;
		}

		// Stripslashes and htmlspecialchars tags
		$old = htmlspecialchars($this->unHtmlEntities(stripslashes($old)));
		$new = htmlspecialchars($this->unHtmlEntities(stripslashes($new)));

		// String to array
		$old_tags = explode(',', $old);
		$new_tags = explode(',', $new);

		// Remove empty element and trim
		$old_tags = array_filter($old_tags, array(&$this, 'deleteEmptyElement'));
		$new_tags = array_filter($new_tags, array(&$this, 'deleteEmptyElement'));

		// If old/new tag are empty => exit !
		if ( empty($old_tags) || empty($new_tags) ) {
			$this->message = __('No new/old valid tag specified!', 'simpletags');
			$this->status = 'error';
			return;
		}

		$counter = 0;
		if( count($old_tags) == count($new_tags) ) { // Rename only
			foreach ( (array) $old_tags as $i => $old_tag ) {
				$new_name = $new_tags[$i];

				// Get term by name
				$term = get_term_by('name', addslashes($old_tag), 'post_tag');
				if ( !$term ) {
					continue;
				}

				// Get objects from term ID
				$objects_id = get_objects_in_term( $term->term_id, 'post_tag', array('fields' => 'all_with_object_id'));

				// Delete old term
				wp_delete_term( $term->term_id, 'post_tag' );

				// Set objects to new term ! (Append no replace)
				foreach ( (array) $objects_id as $object_id ) {
					wp_set_object_terms( $object_id, $new_name, 'post_tag', true );
				}

				// Increment
				$counter++;
			}

			if ( $counter == 0  ) {
				$this->message = __('No tag renamed.', 'simpletags');
			} else {
				wp_cache_flush(); // Delete cache
				$this->message = sprintf(__('Renamed tag(s) &laquo;%1$s&raquo; to &laquo;%2$s&raquo;', 'simpletags'), $old, $new);
			}
		}
		elseif ( count($new_tags) == 1  ) { // Merge
			// Set new tag
			$new_tag = $new_tags[0];
			if ( empty($new_tag) ) {
				$this->message = __('No valid new tag.', 'simpletags');
				$this->status = 'error';
				return;
			}

			// Get terms ID from old terms names
			$terms_id = array();
			foreach ( (array) $old_tags as $old_tag ) {
				$term = get_term_by('name', addslashes($old_tag), 'post_tag');
				$terms_id[] = (int) $term->term_id;
			}

			// Get objects from terms ID
			$objects_id = get_objects_in_term( $terms_id, 'post_tag', array('fields' => 'all_with_object_id'));

			// No objects ? exit !
			if ( !$objects_id ) {
				$this->message = __('No objects (post/page) found for specified old tags.', 'simpletags');
				$this->status = 'error';
				return;
			}

			// Delete old terms
			foreach ( (array) $terms_id as $term_id ) {
				wp_delete_term( $term_id, 'post_tag' );
			}

			// Set objects to new term ! (Append no replace)
			foreach ( (array) $objects_id as $object_id ) {
				wp_set_object_terms( $object_id, $new_tag, 'post_tag', true );
				$counter++;
			}

			// Test if term is also a category
			if ( is_term($new_tag, 'category') ) {
				// Edit the slug to use the new term
				$slug = sanitize_title($new_tag);
				$this->editTagSlug( $new_tag, $slug );
				unset($slug);
			}

			if ( $counter == 0  ) {
				$this->message = __('No tag merged.', 'simpletags');
			} else {
				wp_cache_flush(); // Delete cache
				$this->message = sprintf(__('Merge tag(s) &laquo;%1$s&raquo; to &laquo;%2$s&raquo;. %3$s objects edited.', 'simpletags'), $old, $new, $counter);
			}
		} else { // Error
			$this->message = sprintf(__('Error. No enough tags for rename. Too for merge. Choose !', 'simpletags'), $old);
			$this->status = 'error';
		}
		return;
	}

	/**
	 * trim and remove empty element
	 *
	 * @param string $element
	 * @return string
	 */
	function deleteEmptyElement( &$element ) {
		$element = trim($element);
		if ( !empty($element) ) {
			return $element;
		}
	}

	/**
	 * Delete list of tags
	 *
	 * @param string $delete
	 */
	function deleteTagsByTagList( $delete ) {
		if ( trim( str_replace(',', '', stripslashes($delete)) ) == '' ) {
			$this->message = __('No tag specified!', 'simpletags');
			$this->status = 'error';
			return;
		}

		// Stripslashes and htmlspecialchars tags
		$delete = htmlspecialchars($this->unHtmlEntities(stripslashes($delete)));

		// In array + filter
		$delete_tags = explode(',', $delete);
		$delete_tags = array_filter($delete_tags, array(&$this, 'deleteEmptyElement'));

		// Delete tags
		$counter = 0;
		foreach ( (array) $delete_tags as $tag ) {
			$term = get_term_by('name', addslashes($tag), 'post_tag');
			$term_id = (int) $term->term_id;

			if ( $term_id != 0 ) {
				wp_delete_term( $term_id, 'post_tag');
				$counter++;
			}
		}

		if ( $counter == 0  ) {
			$this->message = __('No tag deleted.', 'simpletags');
		} else {
			wp_cache_flush(); // Delete cache
			$this->message = sprintf(__('%1s tag(s) deleted.', 'simpletags'), $counter);
		}
	}

	/**
	 * Add tags for all or specified posts
	 *
	 * @param string $match
	 * @param string $new
	 */
	function addMatchTags( $match, $new ) {
		if ( trim( str_replace(',', '', stripslashes($new)) ) == '' ) {
			$this->message = __('No new tag(s) specified!', 'simpletags');
			$this->status = 'error';
			return;
		}

		// Stripslashes and htmlspecialchars tags
		$match = htmlspecialchars($this->unHtmlEntities(stripslashes($match)));
		$new = htmlspecialchars($this->unHtmlEntities(stripslashes($new)));

		$match_tags = explode(',', $match);
		$new_tags = explode(',', $new);

		$match_tags = array_filter($match_tags, array(&$this, 'deleteEmptyElement'));
		$new_tags = array_filter($new_tags, array(&$this, 'deleteEmptyElement'));

		$counter = 0;
		if ( !empty($match_tags) ) { // Match and add
			// Get terms ID from old match names
			$terms_id = array();
			foreach ( (array) $match_tags as $match_tag ) {
				$term = get_term_by('name', addslashes($match_tag), 'post_tag');
				$terms_id[] = (int) $term->term_id;
			}

			// Get object ID with terms ID
			$objects_id = get_objects_in_term( $terms_id, 'post_tag', array('fields' => 'all_with_object_id') );

			// Add new tags for specified post
			foreach ( (array) $objects_id as $object_id ) {
				wp_set_object_terms( $object_id, $new_tags, 'post_tag', true ); // Append tags
				$counter++;
			}
		} else { // Add for all posts
			// Page or not ?
			$post_type_sql = ( $this->options['use_tag_pages'] == '1' ) ? "post_type IN('page', 'post')" : "post_type = 'post'";

			// Get all posts ID
			global $wpdb;
			$posts_id = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE {$post_type_sql}");

			// Add new tags for all posts
			foreach ( (array) $posts_id as $post_id ) {
				wp_set_object_terms( $post_id, $new_tags, 'post_tag', true ); // Append tags
				$counter++;
			}
		}

		if ( $counter == 0  ) {
			$this->message = __('No tag added.', 'simpletags');
		} else {
			wp_cache_flush(); // Delete cache
			$this->message = sprintf(__('Tag(s) added to %1s post(s).', 'simpletags'), $counter);
		}
	}

	/**
	 * Edit one or lots tags slugs
	 *
	 * @param string $names
	 * @param string $slugs
	 */
	function editTagSlug( $names = '', $slugs = '') {
		if ( trim( str_replace(',', '', stripslashes($slugs)) ) == '' ) {
			$this->message = __('No new slug(s) specified!', 'simpletags');
			$this->status = 'error';
			return;
		}

		// Stripslashes and htmlspecialchars tags
		$names = htmlspecialchars($this->unHtmlEntities(stripslashes($names)));
		$slugs = htmlspecialchars($this->unHtmlEntities(stripslashes($slugs)));

		$match_names = explode(',', $names);
		$new_slugs = explode(',', $slugs);

		$match_names = array_filter($match_names, array(&$this, 'deleteEmptyElement'));
		$new_slugs = array_filter($new_slugs, array(&$this, 'deleteEmptyElement'));

		if ( count($match_names) != count($new_slugs) ) {
			$this->message = __('Tags number and slugs number isn\'t the same!', 'simpletags');
			$this->status = 'error';
			return;
		} else {
			$counter = 0;
			foreach ( (array) $match_names as $i => $match_name ) {
				// Sanitize slug + Escape
				$new_slug = sanitize_title($new_slugs[$i]);

				// Get term by name
				$term = get_term_by('name', addslashes($match_name), 'post_tag');
				if ( !$term ) {
					continue;
				}

				// Increment
				$counter++;

				// Update term
				wp_update_term($term->term_id, 'post_tag', array('slug' => $new_slug));
			}
		}

		if ( $counter == 0  ) {
			$this->message = __('No slug edited.', 'simpletags');
		} else {
			wp_cache_flush(); // Delete cache
			$this->message = sprintf(__('%s slug(s) edited.', 'simpletags'), $counter);
		}
		return;
	}

	/**
	 * Clean database - Remove empty terms
	 *
	 */
	function cleanDatabase() {
		global $wpdb;

		// Counter
		$counter = 0;

		// Get terms id empty
		$terms_id = $wpdb->get_col("SELECT term_id FROM {$wpdb->terms} WHERE name IN ('', ' ', '  ', '&nbsp;') GROUP BY term_id");
		if ( empty($terms_id) ) {
			$this->message = __('Nothing to muck. Good job !', 'simpletags');
			return;
		}

		// Prepare terms SQL List
		$terms_list = "'" . implode("', '", $terms_id) . "'";

		// Remove term empty
		$counter += $wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN ( {$terms_list} )");

		// Get term_taxonomy_id from term_id on term_taxonomy table
		$tts_id = $wpdb->get_col("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} ) GROUP BY term_taxonomy_id");

		if ( !empty($tts_id) ) {
			// Clean term_taxonomy table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} )");

			// Prepare terms SQL List
			$tts_list = "'" . implode("', '", $tts_id) . "'";

			// Clean term_relationships table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( {$tts_list} )");
		}

		wp_cache_flush(); // Delete cache
		$this->message = sprintf(__('%s rows deleted. WordPress DB is clean now !', 'simpletags'), $counter);
		return;
	}

	/**
	 * Add compatibility PHP4 for htmlspecialchars_decode()
	 *
	 * @param string $value
	 * @return string
	 */
	function unHtmlEntities( $value = '' ) {
		return strtr( $value, array_flip(get_html_translation_table(HTML_SPECIALCHARS, ENT_COMPAT)) );
	}

	/**
	 * Click tags
	 *
	 */
	function helperClickTags() {
		?>
	   	<script type="text/javascript">
	    // <![CDATA[
	    	jQuery(document).ready(function() {
	    		jQuery("#tagdiv").prepend('<a href="#st_click_tags" id="clicktags"><?php _e('Display click tags', 'simpletags'); ?></a><a href="#st_click_tags" id="close_clicktags"><?php _e('Hide click tags', 'simpletags'); ?></a>');
	    		jQuery("#tagdiv").after('<div id="st_click_tags"></div>');
	    		
	    		if ( jQuery.browser.mozilla ) {
   					jQuery("#tagdiv a").css({top:'-17px'}); // fix a Mozilla bug
				}
	    		    		
				jQuery("a#clicktags").click(function() {					
					jQuery("#st_click_tags")
						.fadeIn('slow')
						.load('<?php echo $this->info['siteurl']; ?>/wp-admin/admin.php?st_ajax_action=click_tags', function(){
							jQuery("#st_click_tags span").click(function() { addTag(this.innerHTML); });
							jQuery("a#clicktags").hide();
							jQuery("a#close_clicktags").show();
						});						
					return false;
				});
				jQuery("a#close_clicktags").click(function() {	
					jQuery("#st_click_tags").fadeOut('slow', function() {
						jQuery("a#clicktags").show();
						jQuery("a#close_clicktags").hide();
					});
					return false;
				});
			});
		// ]]>
	    </script>	   
		<?php
	}

	############## Suggested tags ##############
	/**
	 * Suggested tags
	 *
	 */
	function helperSuggestTags() {
		?>
		<style type="text/css">
			#advancedstuff_tag h3.dbx-handle{margin-left:7px;margin-bottom:-7px;height:19px;font-size:12px;background:#2685af url(images/box-head-right.gif) no-repeat top right;padding:6px 1em 0 3px;}
			#advancedstuff_tag div.dbx-h-andle-wrapper{background:#fff url(images/box-head-left.gif) no-repeat top left;margin:0 0 0 -7px;position:relative;}
			#advancedstuff_tag div.dbx-content{margin-left:8px;background:url(images/box-bg-right.gif) repeat-y right;padding:10px 10px 15px 0;}
			#advancedstuff_tag div.dbx-c-ontent-wrapper{margin-left:-7px;margin-right:0;background:url(images/box-bg-left.gif) repeat-y left;}
			#advancedstuff_tag fieldset.dbx-box{padding-bottom:9px;margin-left:6px;background:url(images/box-butt-right.gif) no-repeat bottom right;}
			#advancedstuff_tag div.dbx-b-ox-wrapper{background:url(images/box-butt-left.gif) no-repeat bottom left;}
			#advancedstuff_tag .dbx-box-closed div.dbx-c-ontent-wrapper{padding-bottom:2px;background:url(images/box-butt-left.gif) no-repeat bottom left;}
			#advancedstuff_tag .dbx-box{background:url(images/box-butt-right.gif) no-repeat bottom right;}
		</style>
		<div id="advancedstuff_tag" class="dbx-group" >
			<div class="dbx-b-ox-wrapper">
				<fieldset id="suggesttagsdiv" class="dbx-box">
				<div class="dbx-h-andle-wrapper">
					<h3 class="dbx-handle">
						<?php _e('Suggested tags from :', 'simpletags'); ?>&nbsp;&nbsp;
						<a class="local_db" href="#advancedstuff_tag"><?php _e('Local tags', 'simpletags'); ?></a>&nbsp;&nbsp;-&nbsp;&nbsp;
						<a class="yahoo_api" href="#advancedstuff_tag"><?php _e('Yahoo', 'simpletags'); ?></a>&nbsp;&nbsp;-&nbsp;&nbsp;
						<a class="ttn_api" href="#advancedstuff_tag"><?php _e('Tag The Net', 'simpletags'); ?></a>
					</h3>
					<img id="st_ajax_loading" src="<?php echo $this->info['install_url']; ?>/inc/images/ajax-loader.gif" alt="Ajax loading" />
				</div>
				<div class="dbx-c-ontent-wrapper">
					<div class="dbx-content">
						<?php _e('Choose a provider to get suggested tags (local, yahoo or tag the net).', 'simpletags'); ?>
						<div class="clearer"></div>
					</div>
				</div>
				</fieldset>
			</div>
		</div>
	   	<script type="text/javascript">
	    // <![CDATA[
	    	function getContentFromEditor() {
	    		var data = '';
				if ( typeof tinyMCE != "undefined" && tinyMCE.configs.length > 0 ) { // Tiny MCE
					data = tinyMCE.getContent().stripTags();
				} else if ( typeof FCKeditorAPI != "undefined" ) { // FCK Editor
					var oEditor = FCKeditorAPI.GetInstance('content') ;
					data = oEditor.GetHTML().stripTags();
				} else if ( typeof WYM_INSTANCES != "undefined" ) { // Simple WYMeditor
					data = WYM_INSTANCES[0].xhtml().stripTags();
				} else { // No editor, just quick tags
					data = jQuery("#content").val().stripTags();
				}
				return data;
	    	}

	    	jQuery(document).ready(function() {
	    		function loadAndRegisterSuggestedTags() {
					jQuery("#advancedstuff_tag .dbx-content span").click(function() { addTag(this.innerHTML); });
					jQuery('#st_ajax_loading').hide();
	    		}

				jQuery("a.yahoo_api").click(function() {
					jQuery('#st_ajax_loading').show();
					jQuery("#advancedstuff_tag .dbx-content").load('<?php echo $this->info['siteurl']; ?>/wp-admin/admin.php?st_ajax_action=tags_from_yahoo', {content:getContentFromEditor(),title:jQuery("#title").val(),tags:jQuery("#tags-input").val()}, function(){
						loadAndRegisterSuggestedTags();
					});
					return false;
				});

				jQuery("a.local_db").click(function() {
					jQuery('#st_ajax_loading').show();
					jQuery("#advancedstuff_tag .dbx-content").load('<?php echo $this->info['siteurl']; ?>/wp-admin/admin.php?st_ajax_action=tags_from_local_db', {content:getContentFromEditor(),title:jQuery("#title").val()}, function(){
						loadAndRegisterSuggestedTags();
					});
					return false;
				});

				jQuery("a.local_all").click(function() {
					jQuery('#st_ajax_loading').show();
					jQuery("#advancedstuff_tag .dbx-content").load('<?php echo $this->info['siteurl']; ?>/wp-admin/admin.php?st_ajax_action=tags_from_local_db', {all:'true'}, function(){
						loadAndRegisterSuggestedTags();
					});
					return false;
				});

				jQuery("a.ttn_api").click(function() {
					jQuery('#st_ajax_loading').show();
					jQuery("#advancedstuff_tag .dbx-content").load('<?php echo $this->info['siteurl']; ?>/wp-admin/admin.php?st_ajax_action=tags_from_tagthenet', {content:getContentFromEditor(),title:jQuery("#title").val()}, function(){
						loadAndRegisterSuggestedTags();
					});
					return false;
				});
			});
		// ]]>
	    </script>
    <?php
	}

	############## Mass Edit Pages ##############
	/**
	 * Javascript helper for mass edit tags
	 *
	 */
	function helperMassBCompleteJS() {
		// Get total
		$tags = (int) wp_count_terms('post_tag');

		// If no tags => exit !
		if ( $tags == 0 ) {
			return;
		}
		$this->all_tags = true;
		?>
		<script type="text/javascript" src="<?php echo $this->info['siteurl'] ?>/wp-admin/admin.php?st_ajax_action=helper_js_collection&ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
	  	<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<?php if ( 'rtl' == get_bloginfo( 'text_direction' ) ) : ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete-rtl.css?ver=<?php echo $this->version; ?>" />
		<?php endif;
	}

	/**
	 * Get posts/pages data for edit
	 *
	 * @param string $type
	 * @param integer $quantity
	 * @param integer $author
	 * @param string $order
	 * @param string $filter
	 * @param string $search
	 * @return array|boolean
	 */
	function getObjects( $type = 'post', $quantity = 20, $author = 0, $order = 'date_desc', $filter = 'all', $search = '' ) {
		global $wpdb;

		// Quantity
		$this->data_per_page = $quantity;

		if ( $type == 'post' || $type == 'page' ) { // Posts and Pages
			// Order
			switch ($order) {
				case 'date_asc':
					$order_sql = 'ORDER BY p.post_date ASC';
				break;
				case 'id_desc':
					$order_sql = 'ORDER BY p.ID DESC';
				break;
				case 'id_asc':
					$order_sql = 'ORDER BY p.ID ASC';
				break;
				default:
					$order_sql = 'ORDER BY p.post_date DESC';
				break;
			}

			// Search
			$search_sql = '';
			$search = trim($search);
			if ( !empty($search) ) {
				$search = addslashes_gpc($search);
				$search_sql = "AND ( (p.post_title LIKE '%{$search}%') OR (p.post_content LIKE '%{$search}%') )";
			}

			// Restrict Author
			$author_sql = ( $author != 0 ) ? "AND p.post_author = '{$author}'" : '';

			// Status
			$filter_sql = '';
			if ( $filter == 'untagged' ) {
				$p_id_used = $wpdb->get_col("
			      SELECT tr.object_id
			      FROM {$wpdb->term_taxonomy} AS tt, {$wpdb->term_relationships} AS tr, {$wpdb->posts} AS p
			      WHERE tt.taxonomy = 'post_tag'
			      AND tt.term_taxonomy_id = tr.term_taxonomy_id
			      AND tr.object_id  = p.ID
			      AND p.post_type = '{$type}'
			      GROUP BY tr.object_id");

				$filter_sql = 'AND p.ID NOT IN ("'.implode( '", "', $p_id_used ).'")';
				unset($p_id_used);
			}

			// Get datas with pagination
			$this->found_datas = (int) $wpdb->get_var("
		        SELECT COUNT(p.ID)
		        FROM {$wpdb->posts} AS p
		        WHERE p.post_type = '{$type}'
		        {$search_sql}
		        {$author_sql}
		        {$filter_sql}");

			$this->max_num_pages = ceil($this->found_datas/$this->data_per_page);

			if( $this->actual_page != 1 ) {
				if($this->actual_page > $this->max_num_pages) {
					$this->actual_page = $this->max_num_pages;
				}
			}

			$limit_sql = 'LIMIT '.(($this->actual_page - 1) * $this->data_per_page).', '.$this->data_per_page;

			$ps = (array) $wpdb->get_results("
		        SELECT p.ID, p.post_title
		        FROM {$wpdb->posts} AS p
		        WHERE p.post_type = '{$type}'
		        {$search_sql}
		        {$author_sql}
		        {$filter_sql}
		        {$order_sql}
		        {$limit_sql}");

			foreach ( $ps as $p ) {
				$objects[$p->ID]['title'] = $p->post_title;
			}
			return $objects;
		} elseif ( $type == 'link' ) {
			// link_owner -- future
			return false;
		}
		return false;
	}


	/**
	 * Control POST data for mass edit tags
	 *
	 * @param string $type
	 */
	function checkFormMassEdit( $type = 'post' ) {
		if ( isset($_POST['update_mass']) ) {
			// origination and intention
			if ( ! ( wp_verify_nonce($_POST['secure_mass'], 'st_mass_tags') ) ) {
				$this->message = __('Security problem. Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
				return;
			}

			if ( $type == 'post' || $type == 'page' ) {
				$taxonomy = 'post_tag';
			}

			if ( isset($_POST['tags']) ) {
				$counter = 0;
				foreach ( (array) $_POST['tags'] as $object_id => $tag_list ) {
					// Trim data
					$tag_list = trim(stripslashes($tag_list));

					// String to array
					$tags = explode( ',', $tag_list );

					// Remove empty and trim tag
					$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));

					// Add new tag (no append ! replace !)
					wp_set_object_terms( $object_id, $tags, $taxonomy );
					$counter++;
				}

				wp_cache_flush(); // Delete cache
				if ( $type == 'post' ) {
					$this->message = sprintf(__('%s post(s) tags updated with success !', 'simpletags'), $counter);
				} elseif ( $type == 'page' ) {
					$this->message = sprintf(__('%s page(s) tags updated with success !', 'simpletags'), $counter);
				}
			}
		}
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
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		update_option($this->db_options, $this->default_options);
		$this->options = $this->default_options;
		wp_cache_flush(); // Delete cache
	}

	/**
	 * Delete Simple Tags options from DB.
	 *
	 */
	function deleteAllOptions() {
		delete_option($this->db_options, $this->default_options);
		wp_cache_flush(); // Delete cache	
	}

	############## Ajax ##############
	/**
	 * Ajax Dispatcher, Choose right function depending $_GET value
	 *
	 */
	function ajaxCheck() {
		if ( $_GET['st_ajax_action'] == 'get_tags' ) {
			$this->ajaxListTags();
		} elseif ( $_GET['st_ajax_action'] == 'tags_from_yahoo' ) {
			$this->ajaxYahooTermExtraction();
		} elseif ( $_GET['st_ajax_action'] == 'tags_from_tagthenet' ) {
			$this->ajaxTagTheNet();
		} elseif ( $_GET['st_ajax_action'] == 'helper_js_collection' ) {
			$this->ajaxHelperJsCollection();
		} elseif ( $_GET['st_ajax_action'] == 'tags_from_local_db' ) {
			$this->ajaxSuggestLocal();
		} elseif ( $_GET['st_ajax_action'] == 'click_tags' ) {
			$this->ajaxClickTags();
		}
	}

	/**
	 * Get tags list for manage tags page.
	 *
	 */
	function ajaxListTags() {
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));

		// Build param for tags
		$sort_order = attribute_escape(stripslashes($_GET['order']));
		switch ($sort_order) {
			case 'natural' :
				$param = 'hide_empty=false&cloud_selection=name-asc';
				break;
			case 'asc' :
				$param = 'hide_empty=false&cloud_selection=count-asc';
				break;
			default :
				$param = 'hide_empty=false&cloud_selection=count-desc';
				break;
		}

		$total = wp_count_terms('post_tag');

		$current_page = (int) $_GET['pagination'];
		$param .= '&number=LIMIT '. $current_page * $this->nb_tags . ', '.$this->nb_tags;

		// Get tags
		global $simple_tags;
		$tags = (array) $simple_tags->getTags($param, true);

		// Build output
		echo '<ul class="ajax_list">';
		foreach( $tags as $tag ) {
			echo '<li><strong>'.$tag->term_id.'.</strong> <span>'.$tag->name.'</span>&nbsp;<a href="'.(get_tag_link( $tag->term_id )).'" title="'.sprintf(__('View all posts tagged with %s', 'simpletags'), $tag->name).'">('.$tag->count.')</a></li>'."\n";
		}
		unset($tags);
		echo '</ul>';

		// Build pagination
		$ajax_url = $this->info['siteurl']. '/wp-admin/admin.php?st_ajax_action=get_tags';

		// Order
		if ( isset($_GET['order']) ) {
			$ajax_url = $ajax_url . '&amp;order='.$sort_order ;
		}
		?>
		<div class="navigation">
			<?php if ( ($current_page * $this->nb_tags)  + $this->nb_tags > $total ) : ?>
				<?php _e('Previous tags', 'simpletags'); ?>
			<?php else : ?>
				<a href="<?php echo $ajax_url. '&amp;pagination='. ($current_page + 1); ?>"><?php _e('Previous tags', 'simpletags'); ?></a>
			<?php endif; ?>
			|
			<?php if ( $current_page == 0 ) : ?>
				<?php _e('Next tags', 'simpletags'); ?>
			<?php else : ?>
			<a href="<?php echo $ajax_url. '&amp;pagination='. ($current_page - 1) ?>"><?php _e('Next tags', 'simpletags'); ?></a>
			<?php endif; ?>
		</div>
		<?php
		exit();
	}

	/**
	 * Get suggest tags from Yahoo Term Extraction
	 *
	 */
	function ajaxYahooTermExtraction() {
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));

		// Application entrypoint -> http://www.herewithme.fr/wordpress-plugins/simple-tags
		// Yahoo ID : h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--
		$yahoo_id = 'h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--';
		$yahoo_api_host = 'search.yahooapis.com'; // Api URL
		$yahoo_api_path = '/ContentAnalysisService/V1/termExtraction'; // Api URL

		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			exit();
		}
		$tags = stripslashes($_POST['tags']);

		// Build params
		$param = 'appid='.$yahoo_id; // Yahoo ID
		$param .= '&context='.urlencode($content); // Post content
		if ( !empty($tags) ) {
			$param .= '&query='.urlencode($tags); // Existing tags
		}
		$param .= '&output=php'; // Get PHP Array !

		$data = '';
		if ( function_exists('curl_init') ) { // Curl exist ?
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, 'http://'.$yahoo_api_host.$yahoo_api_path.'?'.$param);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_POST, true);

			$data = curl_exec($curl);
			curl_close($curl);

			$data = unserialize($data);
		} else { // Fsocket
			$request = 'appid='.$yahoo_id.$param;

			$http_request  = "POST $yahoo_api_path HTTP/1.0\r\n";
			$http_request .= "Host: $yahoo_api_host\r\n";
			$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
			$http_request .= "Content-Length: " . strlen($request) . "\r\n";
			$http_request .= "\r\n";
			$http_request .= $request;

			if( false != ( $fs = @fsockopen( $yahoo_api_host, 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
				fwrite($fs, $http_request);

				while ( !feof($fs) )
					$data .= fgets($fs, 1160); // One TCP-IP packet
				fclose($fs);
				$data = explode("\r\n\r\n", $data, 2);
			}

			$data = unserialize($data[1]);
		}

		$data = (array) $data['ResultSet']['Result'];
		
		// Remove empty terms
		$data = array_filter($data, array(&$this, 'deleteEmptyElement'));

		foreach ( $data as $term ) {
			echo '<span class="yahoo">'.$term.'</span>'."\n";
		}
		echo '<div class="clearer"></div>';
		exit();
	}

	/**
	 * Get suggest tags from Tag The Net
	 *
	 */
	function ajaxTagTheNet() {
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));

		$api_host = 'tagthe.net'; // Api URL
		$api_path = '/api/'; // Api URL

		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			exit();
		}

		// Build params
		$param .= 'text='.urlencode($content); // Post content
		$param .= '&view=xml&count=50';

		$data = '';
		$request = $param;

		$http_request  = "POST $api_path HTTP/1.0\r\n";
		$http_request .= "Host: $api_host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;

		if( false != ( $fs = @fsockopen( $api_host, 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
			fwrite($fs, $http_request);

			while ( !feof($fs) )
				$data .= fgets($fs, 1160); // One TCP-IP packet
			fclose($fs);
			$data = explode("\r\n\r\n", $data, 2);
		}

		$data = $data[1];
		$terms = $all_topics = $all_locations = $all_persons = $persons = $topics = $locations = array();

		// Get all topics
		preg_match_all("/(.*?)<dim type=\"topic\">(.*?)<\/dim>(.*?)/s", $data, $all_topics );
		$all_topics = $all_topics[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_topics, $topics );
		$topics = $topics[2];

		foreach ( (array) $topics as $topic ) {
			$terms[] = '<span class="ttn_topic">'.$topic.'</span>';
		}

		// Get all locations
		preg_match_all("/(.*?)<dim type=\"location\">(.*?)<\/dim>(.*?)/s", $data, $all_locations );
		$all_locations = $all_locations[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_locations, $locations );
		$locations = $locations[2];

		foreach ( (array) $locations as $location ) {
			$terms[] = '<span class="ttn_location">'.$location.'</span>';
		}

		// Get all persons
		preg_match_all("/(.*?)<dim type=\"person\">(.*?)<\/dim>(.*?)/s", $data, $all_persons );
		$all_persons = $all_persons[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_persons, $persons );
		$persons = $persons[2];

		foreach ( (array) $persons as $person ) {
			$terms[] = '<span class="ttn_person">'.$person.'</span>';
		}
		
		// Remove empty terms
		$terms = array_filter($terms, array(&$this, 'deleteEmptyElement'));

		shuffle($terms);
		echo implode("\n", $terms);
		echo '<div class="clearer"></div>';
		exit();
	}

	/**
	 * Get suggest tags from local database
	 *
	 */
	function ajaxSuggestLocal() {
		$total = wp_count_terms('post_tag');

		// No tags to suggest.
		if ( $total == 0 ) {
			exit();
		}
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		global $simple_tags;

		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			exit();
		}

		$counter = 0;
		while ( ( $counter * 200 ) < $total ) {
			// Get tags
			$tags = $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);

			foreach ( (array) $tags as $tag ) {
				if ( is_string($tag->name) && !empty($tag->name) && stristr($content, $tag->name) ) {
					echo '<span class="local">'.$tag->name.'</span>'."\n";
				}
			}
			unset($tags, $tag);

			// Increment counter
			$counter++;
		}
		echo '<div class="clearer"></div>';
		exit();
	}
	
	function ajaxClickTags() {
		$total = wp_count_terms('post_tag');

		// No tags to suggest.
		if ( $total == 0 ) {
			exit();
		}
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		global $simple_tags;

		$counter = 0;
		while ( ( $counter * 200 ) < $total ) {
			// Get tags
			$tags = $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);

			foreach ( (array) $tags as $tag ) {
				echo '<span class="local">'.$tag->name.'</span>'."\n";
			}
			unset($tags, $tag);

			// Increment counter
			$counter++;
		}
		echo '<div class="clearer"></div>';
		exit();	
	}

	/**
	 * Output full list tags (collection for bcomplete.js)
	 *
	 */
	function ajaxHelperJsCollection() {
		global $simple_tags;

		if ( is_null($simple_tags) ) {
			exit();
		}

		$total = wp_count_terms('post_tag');

		status_header( 200 );
		cache_javascript_headers();

		echo 'collection = [';

		$flag = false;
		$counter = 0;
		while ( ( $counter * 200 ) < $total ) {
			// Get tags
			$tags = $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);

			foreach ( (array) $tags as $tag ) {
				$tag_name = str_replace('"', '\"', $tag->name);
				if ( $flag === false) {
					echo '"'.$tag_name.'"';
					$flag = true;
				} else {
					echo ', "'.$tag_name.'"';
				}
			}
			unset($tags, $tag);

			// Increment counter
			$counter++;
		}

		echo '];';
		exit();
	}

	############## Admin WP Helper ##############
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter() {
		?>
		<p class="tags_admin"><?php printf(__('&copy; Copyright 2007 <a href="http://www.herewithme.fr/" title="Here With Me">Amaury Balmer</a> | <a href="http://wordpress.org/extend/plugins/simple-tags">Simple Tags</a> | Version %s', 'simpletags'), $this->version); ?></p>
		<?php
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage() {
		if ( $this->message != '') {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}

		if ( $message ) {
		?>
			<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}

	/**
	 * Print link to ST File CSS
	 *
	 */
	function helperCSS() {
		echo '<link rel="stylesheet" href="'.$this->info['install_url'].'/inc/simple-tags.admin.css?ver='.$this->version.'" type="text/css" />';
	}

	/**
	 * Display generic pagination
	 *
	 * @param string $action_url
	 */
	function printPagination( $action_url ) {
		if ( $this->max_num_pages > 1 ) {
			$output = '<div class="pagination">';
			$output .= '<strong>'. __('Page: ', 'simpletags') .'</strong>';
			for ( $i = 1; $i <= $this->max_num_pages; $i++ ) {
				$output .= '<a href="'.$action_url.'&amp;pagination='.$i.'">'.$i.'</a>'."\n";
			}
			$output = str_replace('pagination='.$this->actual_page.'">', 'pagination='.$this->actual_page.'" class="current_page">', $output);
			$output .= '</div>';
			echo $output;
		}
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
			$output .= "\n" . '<div id="'. sanitize_title($section) .'"><fieldset class="options"><legend>' . $this->getNiceTitleOptions($section) . '</legend><table class="optiontable">' . "\n";
			foreach((array) $options as $option) {
				// Helper
				if (  $option[2] == 'helper' ) {
						$output .= '<tr style="vertical-align: top;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>' . "\n";
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
		$options_from_table = get_option( $this->db_options );
		if ( !$options_from_table ) {
			$this->resetToDefaultOptions();
		}
	}
}
?>