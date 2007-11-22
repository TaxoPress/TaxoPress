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
	var $all_tags = '';

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return SimpleTagsAdmin
	 */
	function SimpleTagsAdmin( $defaultopt = array(), $version = '' ) {
		// load version number
		$this->version = $version;
		unset($version);

		// Set class property for default options
		$this->default_options = $defaultopt;

		// Get options from WP options
		$optionsFromTable = get_option( $this->db_options );
		if ( !$optionsFromTable ) {
			$this->resetToDefaultOptions();
		}

		// Update default options by getting not empty values from options table
		foreach( (array) $defaultopt as $def_optname => $def_optval ) {
			$defaultopt[$def_optname] = $optionsFromTable[$def_optname];
		}

		// Set the class property and unset no used variable
		$this->options = $defaultopt;
		unset($defaultopt);
		unset($optionsFromTable);

		// Determine installation path & url
		$path = basename(str_replace('/inc', '', str_replace('/inc/', '/', str_replace('\\','/',dirname(__FILE__)))));
		$info['siteurl'] = get_option('siteurl');
		$info['install_url'] = $info['siteurl'] . '/wp-content/plugins';
		$info['install_dir'] = ABSPATH . 'wp-content/plugins';
		if ( $path != 'plugins' ) {
			$info['install_url'] .= '/' . $path;
			$info['install_dir'] .= '/' . $path;
		}

		// Set informations
		$this->info = array(
			'siteurl' 			=> $info['siteurl'],
			'install_url'		=> $info['install_url'],
			'install_dir'		=> $info['install_dir']
		);
		unset($info);

		// Admin URL and Pagination
		$this->admin_base_url = $this->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset($_GET['pagination']) ) {
			$this->actual_page = (int) $_GET['pagination'];
		}

		// Admin Capabilities
		$role = get_role('administrator');
		if( !$role->has_cap('simple_tags') ) {
			$role->add_cap('simple_tags');
		}

		// Admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));

		// Embedded Tags
		if ( $this->options['use_embed_tags'] == '1' ) {
			add_action('save_post', array(&$this, 'saveEmbedTags'));
		}

		// Auto tags
		if ( $this->options['use_auto_tags'] == '1' ) {
			add_action('save_post', array(&$this, 'saveAutoTags'));
		}

		// Tags for page
		if ( $this->options['use_tag_pages'] == '1' ) {
			add_action('edit_page_form', array(&$this, 'helperTagsPage'), 1);
			add_action('dbx_page_advanced', array(&$this, 'helperJS'));
			add_action('edit_page_form', array(&$this, 'helperSuggestTags'), 1);
		}

		// Tags for post
		add_action('dbx_post_advanced', array(&$this, 'helperJS'));

		// Javascript helper for mass edit
		if ( $_GET['page'] == 'simpletags_mass' ) {
			wp_enqueue_script( 'prototype' );
			add_action('admin_head', array(&$this, 'helperMassJS'));
		}

		// Tags suggest for posts
		add_action('edit_form_advanced', array(&$this, 'helperSuggestTags'), 1);

		return;
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {
		add_menu_page(__('Simple Tags', 'simpletags'), __('Tags', 'simpletags'), 'simple_tags', __FILE__, array(&$this, 'pageManageTags'));
		add_submenu_page(__FILE__, __('Simple Tags: Manage Tags', 'simpletags'), __('Manage Tags', 'simpletags'), 'simple_tags', __FILE__, array(&$this, 'pageManageTags'));
		add_submenu_page(__FILE__, __('Simple Tags: Mass Edit Tags', 'simpletags'), __('Mass Edit Tags', 'simpletags'), 'simple_tags', 'simpletags_mass', array(&$this, 'pageMassEditTags'));
		add_submenu_page(__FILE__, __('Simple Tags: Auto Tags', 'simpletags'), __('Auto Tags', 'simpletags'), 'simple_tags', 'simpletags_auto', array(&$this, 'pageAutoTags'));
		add_submenu_page(__FILE__, __('Simple Tags: Options', 'simpletags'), __('Options', 'simpletags'), 'simple_tags', 'simpletags_options', array(&$this, 'pageOptions'));
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

			$this->saveOptions();
			$this->message = __('Auto tags options updated !', 'simpletags');
		} elseif ( $_GET['action'] == 'auto_tag' ) {
			$action = true;
			$n = ( isset($_GET['n']) ) ? intval($_GET['n']) : 0;
			global $wpdb;
		}

		$tags = maybe_unserialize($this->options['auto_list']);
		if ( is_array($tags) ) {
			$tags_list = implode(', ', $tags);
		}

		$this->displayMessage();
		?>
		<div class="wrap">
			<style type="text/css">
				.tags_admin { text-align:center; font-size:0.9em; }
				#auto_list { width:100%; margin:3px 0; padding:3px 5px;}
			</style>

			<h2><?php _e('Simple Tags: Auto Tags', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

			<?php if ( $action === false ) : ?>
			<h3><?php _e('Auto tags list', 'simpletags'); ?></h3>
			<p><?php _e('This feature allows Wordpress to look into post content and title for specified tags when saving posts. If your post content or title contains the word "WordPress" and you have "wordpress" in auto tags list, Simple Tags will add automatically "wordpress" as tag for this post.', 'simpletags'); ?></p>

			<form action="<?php echo $this->admin_base_url.'simpletags_auto'; ?>" method="post">
				<p><input type="checkbox" id="use_auto_tags" name="use_auto_tags" value="1" <?php echo ( $this->options['use_auto_tags'] == '1' ) ? 'checked="checked"' : ''; ?>  />
					<label for="use_auto_tags"><?php _e('Active Auto Tags.', 'simpletags'); ?></label></p>

				<p><label for="auto_list"><?php _e('Keywords list: (separated with a comma)', 'simpletags'); ?></label><br />
					<input type="text" id="auto_list" name="auto_list" value="<?php echo $tags_list; ?>" /></p>

				<p class="submit">
					<input type="submit" name="update_auto_list" value="<?php _e('Update list &raquo;', 'simpletags'); ?>" />
			</form>

			<h3><?php _e('Auto tags old content', 'simpletags'); ?></h3>
			<p><?php _e('Simple Tags can also tag all existing contents of your blog. This feature use auto tags list above-mentioned.', 'simpletags'); ?>
				<br /><strong><a href="<?php echo $this->admin_base_url.'simpletags_auto'; ?>&amp;action=auto_tag"><?php _e('Auto tags all content', 'simpletags'); ?></a></strong></p>

			<?php else:
				// Page or not ?
				$post_type_sql = ( $this->options['use_tag_pages'] == '1' ) ? "post_type IN('page', 'post')" : "post_type = 'post'";

				$objects = $wpdb->get_results( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE {$post_type_sql} ORDER BY ID DESC LIMIT {$n}, 20" );
				if( !empty($objects) ) {
					echo '<ul>';
					foreach( (array) $objects as $object ) {
						foreach ( (array) $tags as $tag ) {
							if ( is_string($tag) && !empty($tag) && ( stristr($object->post_content, $tag) || stristr($object->post_title, $tag) ) ) {
								$tags_to_add[] = $tag;
							}
						}

						// Append tags if tags to add
						if ( !empty($tags_to_add) ) {
							// Remove empty and duplicate elements
							$tags_to_add = array_filter($tags_to_add, array(&$this, 'deleteEmptyElement'));
							$tags_to_add = array_unique($tags_to_add);

							wp_set_object_terms( $object->ID, $tags_to_add, 'post_tag', true );
						}

						echo '<li>#'. $object->ID .' '. $object->post_title .'</li>';
						unset($tags_to_add, $object, $tag);
					}
					echo '</ul>';
					?>
					<p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?> <a href="<?php echo $this->admin_base_url.'simpletags_auto'; ?>&amp;action=auto_tag&amp;n=<?php echo ($n + 20) ?>"><?php _e('Next content', 'simpletags'); ?></a></p>
					<script type="text/javascript">
						<!--
						function nextpage() {
							location.href = '<?php echo $this->admin_base_url.'simpletags_auto'; ?>&action=auto_tag&n=<?php echo ($n + 20) ?>';
						}
						setTimeout( 'nextpage()', 250 );
						//-->
					</script>
					<?php
				} else {
					echo '<p><strong>'.__('All done!', 'simpletags').'</strong></p>';
				}

			endif; ?>

			<?php $this->printAdminFooter(); ?>
		</div>
		<?php
	}

	/**
	 * WP Page - Tags options
	 *
	 */
	function pageOptions() {
		$option_data = array(
			__('General', 'simpletags') => array(
				array('inc_page_tag_search', __('Include page in tag search:', 'simpletags'), 'checkbox', '1',
				__('This feature need that option "Add page in tags management" is enabled.', 'simpletags')),
				array('allow_embed_tcloud', __('Allow tag cloud in post/page content:', 'simpletags'), 'checkbox', '1',
				__('Enabling this will allow Wordpress to look for tag cloud marker <code><!--st_tag_cloud--></code> when displaying posts. WP replace this marker by a tag cloud.', 'simpletags')),
				array('auto_link_tags', __('Active auto link tags into post content:', 'simpletags'), 'checkbox', '1',
				__('Example: You have a tag called "WordPress" and your post content contains "wordpress", this feature will replace "wordpress" by a link to "wordpress" tags page. (http://myblog.net/tag/wordpress/)', 'simpletags')),
			),
			__('Administration', 'simpletags') => array(
				array('use_tag_pages', __('Add page in tags management:', 'simpletags'), 'checkbox', '1',
					__('Add a tag input (and tag posts features) in page edition', 'simpletags')),
				array('admin_max_suggest', __('Maximum number of suggested tags:', 'simpletags'), 'text', 10,
					__('The maximum number of tags displayed under <em>Write</em>. Zero (0) means no limit and shows all tags.', 'simpletags')),
				array('admin_tag_suggested', __('Display suggested tags only:', 'simpletags'), 'checkbox', '1',
					__('Displays suggested tags only instead of all tags. Tags will be suggested if they are part of the post.', 'simpletags')),
				array('admin_tag_sort', __('Tag cloud sort order:', 'simpletags'), 'dropdown', 'alpha/popular',
					'<ul>
						<li>'.__('<code>Alpha</code> &ndash; alphabetic order.', 'simpletags').'</li>
						<li>'.__('<code>Popular</code> &ndash; most popular tags first.', 'simpletags').'</li>
					</ul>')
			),
			__('Meta Keyword', 'simpletags') => array(
				array('meta_autoheader', __('Automatically include in header:', 'simpletags'), 'checkbox', '1',
				__('Includes the meta keywords tag automatically in your header (most, but not all, themes support this). These keywords are sometimes used by search engines.', 'simpletags')),
				array('meta_always_include', __('Always add these keywords:', 'simpletags'), 'text', 80)
			),
			__('Embedded Tags', 'simpletags') => array(
				array('use_embed_tags', __('Use embedded tags:', 'simpletags'), 'checkbox', '1',
				__('Enabling this will allow Wordpress to look for embedded tags when saving and displaying posts. Such set of tags is marked <code>[tags]like this, and this[/tags]</code>, and is added to the post when the post is saved, but does not display on the post.', 'simpletags')),
				array('start_embed_tags', __('Prefix for embedded tags:', 'simpletags'), 'text', 40),
				array('end_embed_tags', __('Suffix for embedded tags:', 'simpletags'), 'text', 40)
			),
			__('Tags for Current Post', 'simpletags') => array(
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
				array('tt_notagstext', __('Text to display if no tags found:', 'simpletags'), 'text', 80),
				array('tt_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_the_tags()</code> function to customize display. See <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags#advanced-usage">documentation</a> for more details.', 'simpletags'))
			),
			__('Related Posts', 'simpletags') => array(
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
				array('rp_limit_qty', __('Maximum number of related posts to display: (default: 5)', 'simpletags'), 'text', 10),
				array('rp_notagstext', __('Enter the text to show when there is no related post:', 'simpletags'), 'text', 80),
				array('rp_title', __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'), 'text', 80),
				array('rp_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_related_posts()</code>function to customize display. See <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags#advanced-usage">documentation</a> for more details.', 'simpletags'))
			),
			__('Tag cloud', 'simpletags') => array(
				array('cloud_helper', 'cloud_helper', 'helper', '', __('Which difference between <strong>&#8216;Order tags selection&#8217;</strong> and <strong>&#8216;Order tags display&#8217;</strong> ?<br />', 'simpletags')
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
				array('cloud_limit_qty', __('Maximum number of tags to display: (default: 45)', 'simpletags'), 'text', 10),
				array('cloud_notagstext', __('Enter the text to show when there is no tag:', 'simpletags'), 'text', 80),
				array('cloud_title', __('Enter the positioned title before the list, leave blank for no title:', 'simpletags'), 'text', 80),
				array('cloud_max_color', __('Most popular color:', 'simpletags'), 'text', 40,
					__("The colours are hexadecimal colours,  and need to have the full six digits (#eee is the shorthand version of #eeeeee).", 'simpletags')),
				array('cloud_min_color', __('Least popular color:', 'simpletags'), 'text', 40),
				array('cloud_max_size', __('Most popular font size:', 'simpletags'), 'text', 20,
					__("The two font sizes are the size of the largest and smallest tags.", 'simpletags')),
				array('cloud_min_size', __('Least popular font size:', 'simpletags'), 'text', 20),
				array('cloud_unit', __('The units to display the font sizes with, on tag clouds:', 'simpletags'), 'dropdown', 'pt/px/em/%',
					__("The font size units option determines the units that the two font sizes use.", 'simpletags')),
				array('cloud_adv_usage', __('<strong>Advanced usage</strong>:', 'simpletags'), 'text', 80,
					__('You can use the same syntax as <code>st_tag_cloud()</code> function to customize display. See <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags#advanced-usage">documentation</a> for more details.', 'simpletags'))
			),
		);

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
			update_option( $this->db_options, $this->default_options );
			$this->options = $this->default_options;
			$this->message = __('Simple Tags options resetted to default options!', 'simpletags');
		}

		$this->displayMessage();
	    ?>
	    <div class="wrap">
			<style type="text/css">
				.tags_admin { text-align: center; font-size: .90em; }
				.stpexplan { font-size: .85em; }
				.stpexplan ul { margin:0;padding:0;list-style:square;margin-left:20px; }
				.stpexplan ul li { margin:0;padding:0; }
			</style>
			<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<form action="<?php echo $this->admin_base_url.'simpletags_options'; ?>" method="post">
				<p class="submit">
					<input type="submit" name="updateoptions" value="<?php _e('Update Options &raquo;', 'simpletags'); ?>" />
					<input type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" /></p>
				<?php echo $this->printOptions( $option_data ); ?>
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
		// Manage URL
		$sort_order = ( isset($_GET['tag_sortorder']) ) ? attribute_escape($_GET['tag_sortorder']) : 'desc';
		$actionurl = $this->admin_base_url.attribute_escape($_GET['page']).'&amp;tag_sortorder='.$sort_order;

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

		$order_array = array('desc' => __('Most popular', 'simpletags'), 'asc' => __('Least used', 'simpletags'), 'natural' => __('Alphabetical', 'simpletags'));

		$tag_listing = '';
		foreach( (array) $order_array as $sort => $title ) {
			$tag_listing .= ($sort == $sort_order) ? '<span style="color: red;">'.$title.'</span><br />' : '<a href="'.$this->admin_base_url.attribute_escape($_GET['page']).'&amp;tag_sortorder='.$sort.'">'.$title.'</a><br/>';
		}
		$tag_listing = '<p style="margin:0; padding:0;">' .__('Sort Order:', 'simpletags'). '</p><p style="margin:0 0 10px 10px; padding:0;">' .$tag_listing. '</p>';

		/* create tag listing */
		switch ($sort_order) {
			case 'natural' :
				$tags = get_tags('hide_empty=false&orderby=name&order=ASC');
				break;
			case 'asc' :
				$tags = get_tags('hide_empty=false&orderby=count&order=ASC');
				break;
			default :
				$tags = get_tags('hide_empty=false&orderby=count&order=DESC');
				break;
		}
		$tag_listing .= '<ul>';
		foreach( (array) $tags as $tag ) {
			$tag_listing .= '<li><span style="font-weight:700; width:50px;" title="'.__('Term ID', 'simpletags').'">'.$tag->term_id.'.</span> <span style="cursor: pointer;" onclick="javascript:updateTagFields(this.innerHTML);">'.$tag->name.'</span>&nbsp;<a href="'.(get_tag_link( $tag->term_id )).'" title="'.sprintf(__('View all posts tagged with %s', 'simpletags'), $tag->name).'">('.$tag->count.')</a></li>'."\n";
		}
		$tag_listing .= '</ul>';

		$this->displayMessage();
		?>
		<div class="wrap">
  		<style type="text/css">
  			.tags_admin { text-align: center; font-size: .85em; }
  			fieldset#taglist ul { list-style: none; margin: 0; padding: 0; }
  			fieldset#taglist ul li { margin: 0; padding: 0; font-size: 85%; }
   		</style>
			<h2><?php _e('Simple Tags: Manage Tags', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<table>
				<tr>
					<td style="vertical-align: top; border-right: 1px dotted #ccc; width:220px;">
						<fieldset class="options" id="taglist"><legend><?php _e('Existing Tags', 'simpletags'); ?></legend>
							<?php echo $tag_listing; ?>
						</fieldset>
					</td>
					<td style="vertical-align: top;">

						<fieldset class="options"><legend><?php _e('Rename Tag', 'simpletags'); ?></legend>
							<p><?php _e('Enter the tag to rename and its new value.  You can use this feature to merge tags too. Click "Rename" and all posts which use this tag will be updated.', 'simpletags'); ?></p>
							<p><?php _e('You can specify multiple tags to rename by separating them with commas.', 'simpletags'); ?></p>
							<form action="<?php echo $actionurl; ?>" method="post">
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
							<form action="<?php echo $actionurl; ?>" method="post">
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
							<form action="<?php echo $actionurl; ?>" method="post">
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
							<form action="<?php echo $actionurl; ?>" method="post">
								<input type="hidden" name="tag_action" value="editslug" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<table>
									<tr><th><label><?php _e('Tag(s) to match:', 'simpletags'); ?></label></th><td> <input type="text" id="tagname_match" name="tagname_match" value="" size="40" /> </td></tr>
									<tr><th><label><?php _e('Slug(s) to set:', 'simpletags'); ?></label></th><td>   <input type="text" id="tagslug_new" name="tagslug_new" value="" size="40" /> </td></tr>
									<tr><th></th><td> <input class="button" type="submit" name="edit" value="<?php _e('Edit', 'simpletags'); ?>" /> </td></tr>
								</table>
							</form>
						</fieldset>

						<fieldset class="options"><legend><?php _e('Remove empty terms', 'simpletags'); ?></legend>
							<p><?php _e('WordPress 2.3 have a small bug and can create empty terms. Remove it !', 'simpletags'); ?></p>
							<form action="<?php echo $actionurl; ?>" method="post">
								<input type="hidden" name="tag_action" value="cleandb" />
								<input type="hidden" name="tag_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								<p><input class="button" type="submit" name="clean" value="<?php _e('Clean !', 'simpletags'); ?>" /></p>
							</form>
						</fieldset>
					</td>
				</tr>
			</table>
			<script type="text/javascript">
			if(document.all && !document.getElementById) {
				document.getElementById = function(id) { return document.all[id]; }
			}
			function addTag(tag, input_element) {
				if (input_element.value.length > 0 && !input_element.value.match(/,\s*$/))
				input_element.value += ", ";
				var re = new RegExp(tag + ",");
				if (!input_element.value.match(re))
				input_element.value += tag + ", ";
			}
			function updateTagFields(tag) {
				addTag(tag, document.getElementById("renametag_old"));
				addTag(tag, document.getElementById("deletetag_name"));
				addTag(tag, document.getElementById("addtag_match"));
				addTag(tag, document.getElementById("tagname_match"));
			}
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
		$quantity = (int) attribute_escape($_GET['quantity']);
		if ( $quantity < 10 || $quantity > 50 ) {
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
		$order = ( empty($_GET['order']) ) ? 'date_desc' : attribute_escape($_GET['order']);

		// Filter
		$filter = ( $_GET['filter'] == 'untagged' ) ? 'untagged' : 'all';

		// Check and update tags
		$this->checkFormMassEdit( $type );

		// Action Post URL
		$page = '';
		if ( $this->actual_page != 1 ) {
			$page = '&amp;pagination='.$this->actual_page;
		}
		$actionurl = $this->admin_base_url.'simpletags_mass&amp;s='.$search.'&amp;quantity='.$quantity.'&amp;author='.$author.'&amp;type='.$type.'&amp;filter='.$filter.'&amp;order='.$order.$page;
		$objects = $this->getObjects( $type, $quantity, $author, $order, $filter, $search );

		$this->displayMessage();
		?>
		<div class="wrap">
	    <style type="text/css">
	        .pagination{text-align:center;}
	        .pagination a{margin:1px 3px;}
	        .pagination a.current_page{font-weight:700;}
	        .tags_admin{text-align:center;font-size:.85em;}
	        #post input{width:100%;margin:3px 0;padding:3px 5px;}
	        #post .submit input{width:300px;}
  		</style>
		<h2><?php _e('Simple Tags: Mass Edit Tags', 'simpletags'); ?></h2>
		<p><?php _e('Visit the <a href="http://www.herewithme.fr/wordpress-plugins/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

		<form action="<?php echo $this->admin_base_url; ?>" id="searchform" method="get">
			<input type="hidden" name="page" value="simpletags_mass" />

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
			<form name="post" id="post" action="<?php echo $actionurl; ?>" method="post">
				<p class="submit">
				<input type="submit" name="update_mass" value="<?php _e('Update all', 'simpletags'); ?>" /></p>
				<?php $this->printPagination( $actionurl ); ?>
				<?php
				foreach ( (array) $objects as $object_id => $object ) {
					echo '<p><strong>#'.$object_id.'</strong> <a href="'.get_permalink($object_id).'">'.$object['title'].'</a> [<a href="'.$type.'.php?action=edit&amp;post=' .$object_id . '">' . __('Edit', 'simpletags') . '</a>]<br />'."\n";
					echo '<input id="tags-input'.$object_id.'" class="tags-input" type="text" size="100" name="tags['.$object_id.']" value="'.get_tags_to_edit( $object_id ).'" /></p>'."\n";
				}
				?>
				<p class="submit">
					<input type="hidden" name="secure_masss" value="<?php echo wp_create_nonce('simpletags_mass'); ?>" />
					<input type="submit" name="update_mass" value="<?php _e('Update all', 'simpletags'); ?>" /></p>
			</form>
			<?php $this->printPagination( $actionurl ); ?>
			<?php if ( $this->all_tags ) : ?>
				<script type="text/javascript">
					ST_WindowOnload( ST__Mass_BComplete );
					function ST__Mass_BComplete() {
						<?php foreach ( (array) $objects as $object_id => $object ) { ?>
							var tag_<?php echo $object_id; ?> = new BComplete('tags-input<?php echo $object_id; ?>');
							tag_<?php echo $object_id; ?>.setData(collection);
						<?php } ?>
					}
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
	 * Display generic pagination
	 *
	 * @param string $actionurl
	 */
	function printPagination( $actionurl ) {
		if ( $this->max_num_pages > 1 ) {
			$output = '<div class="pagination">';
			$output .= __('Page: ', 'simpletags');
			for ( $i = 1; $i <= $this->max_num_pages; $i++ ) {
				$output .= '<a href="'.$actionurl.'&amp;pagination='.$i.'">'.$i.'</a>';
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
		$option_actual = $this->options;

		// Generate output
		$output_option = '';
		foreach((array) $option_data as $section => $options) {
			$output_option .= "\n" . '<fieldset class="options"><legend>' . __($section) . '</legend><table class="optiontable">';
			foreach((array) $options as $option) {
				// Helper
				if (  $option[2] == 'helper' ) {
						$output_option .= '<tr style="vertical-align: top;"><td class="helper" colspan="2">' . $option[4] . '</td></tr>';
						continue;
				}

				switch ( $option[2] ) {
					case 'checkbox':
						$input_type = '<input type="checkbox" id="' . $option[0] . '" name="' . $option[0] . '" value="' . htmlspecialchars($option[3]) . '" ' . ( ($option_actual[ $option[0] ]) ? 'checked="checked"' : '') . ' />';
						break;

					case 'dropdown':
						$selopts = explode('/', $option[3]);
						$seldata = '';
						foreach( (array) $selopts as $sel) {
							$seldata .= '<option value="' . $sel . '" ' .(($option_actual[ $option[0] ] == $sel) ? 'selected="selected"' : '') .' >' . ucfirst($sel) . '</option>';
						}
						$input_type = '<select id="' . $option[0] . '" name="' . $option[0] . '">' . $seldata . '</select>';
						break;

					default:
						$input_type = '<input type="text" ' . (($option[3]>50) ? ' style="width: 95%" ' : '') . 'id="' . $option[0] . '" name="' . $option[0] . '" value="' . htmlspecialchars($option_actual[ $option[0] ]) . '" size="' . $option[3] .'" />';
						break;
				}

				// Additional Information
				$extra = '';
				if( !empty($option[4]) ) {
					$extra = '<div class="stpexplan">' . __($option[4]) . '</div>';
				}

				// Output
				$output_option .= '<tr style="vertical-align: top;"><th scope="row"><label for="'.$option[0].'">' . __($option[1]) . '</label></th><td>' . $input_type . '	' . $extra . '</td></tr>';
			}
			$output_option .= '</table>' . "\n";
			$output_option .= '</fieldset>';
		}
		return $output_option;
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
		preg_match_all('/(' . $this->regexEscape($this->options['start_embed_tags']) . '(.*?)' . $this->regexEscape($this->options['end_embed_tags']) . ')/is', $object->post_content, $matches);

		$tags = array();
		foreach ( (array) $matches[2] as $match) {
			foreach( (array) explode(',', $match) as $tag) {
				$tags[] = $tag;
			}
		}

		if( !empty($tags) ) {
			// Remove empty and duplicate elements
			$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));
			$tags = array_unique($tags);

			wp_set_post_tags( $post_id, $tags, true ); // Append tags
		}
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
	 * Check post data for auto tags
	 *
	 * @param integer $post_id
	 * @param array $post_data
	 */
	function saveAutoTags( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return;
		}

		$tags = maybe_unserialize($this->options['auto_list']);

		foreach ( (array) $tags as $tag ) {
			if ( is_string($tag) && !empty($tag) && ( stristr($object->post_content, $tag) || stristr($object->post_title, $tag) ) ) {
				$tags_to_add[] = $tag;
			}
		}

		// Append tags if tags to add
		if ( !empty($tags_to_add) ) {
			// Remove empty and duplicate elements
			$tags_to_add = array_filter($tags_to_add, array(&$this, 'deleteEmptyElement'));
			$tags_to_add = array_unique($tags_to_add);

			wp_set_object_terms( $post_id, $tags_to_add, 'post_tag', true );
		}
	}

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
			$this->message = sprintf(__('%s slug(s) edited.', 'simpletags'), $counter);
		}
		return;
	}

	function cleanDatabase() {
		global $wpdb;

		// Counter
		$counter = 0;

		// Get terms id empty
		$terms_id = $wpdb->get_col("SELECT DISTINCT term_id FROM {$wpdb->terms} WHERE name IN ('', ' ', '  ', '&nbsp;')");
		if ( empty($terms_id) ) {
			$this->message = __('Nothing to muck. Good job !', 'simpletags');
			return;
		}

		// Prepare terms SQL List
		$terms_list = "'" . implode("', '", $terms_id) . "'";

		// Remove term empty
		$counter += $wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN ( {$terms_list} )");

		// Get term_taxonomy_id from term_id on term_taxonomy table
		$tts_id = $wpdb->get_col("SELECT DISTINCT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} )");

		if ( !empty($tts_id) ) {
			// Clean term_taxonomy table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} )");

			// Prepare terms SQL List
			$tts_list = "'" . implode("', '", $tts_id) . "'";

			// Clean term_relationships table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( {$tts_list} )");
		}

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

			if ( $counter == 0  ) {
				$this->message = __('No tag merged.', 'simpletags');
			} else {
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
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter() {
		?>
		<p class="tags_admin"><?php printf(__('&copy; Copyright 2007 <a href="http://www.herewithme.fr/" title="Here With Me">Amaury Balmer</a> | <a href="http://wordpress.org/extend/plugins/simple-tags">Simple Tags</a> | Version %s', 'simpletags'), $this->version); ?></p>
		<?php
	}

	/**
	 * Helper type-ahead
	 *
	 */
	function helperJS() {
		// Get all tags
		$tags = get_tags('hide_empty=false');

		// If no tags => exit !
		if ( !$tags ) {
			return;
		}

		// Type-ahead
		foreach ( (array) $tags as $tag ) {
			$tag_name = str_replace('"', '\"', $tag->name);
			$tags_list .= '"'.$tag_name.'", ';
		}
		$tags_list = substr( $tags_list, 0, strlen($tags_list) - 2);
		?>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/functions.js?ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<script type="text/javascript">
			//<![CDATA[
			collection = [
			<?php echo $tags_list; ?>
			];
			//]]>';
			ST_WindowOnload( ST_BComplete );
			function ST_BComplete() {
				var tags_input = new BComplete('tags-input');
				tags_input.setData(collection);
			}
		</script>
		<?php
	}

	/**
	 * Suggested tags
	 *
	 */
	function helperSuggestTags() {
		global $post;
		$post_id = (int) $post->ID;

		// Get post tags
		$post_tags = array();
		$ptags = wp_get_post_tags($post_id);
		foreach ( (array) $ptags as $tag ) {
			$post_tags[] = $tag->name;
		}
		unset($ptags);

		// Order
		$orderby = ( $this->options['admin_tag_sort'] == 'popular' ) ? 'count' : 'name';

		// Quantity
		$number = (int) $this->options['admin_max_suggest'];
		$number = ( $number == 0 ) ? 9999999 : $number;

		// Get all tags
		$tags = get_tags( array('hide_empty' => false, 'number' => $number, 'orderby' => $orderby, 'order' => 'DESC') );

		// Quit if no tags
		if ( !$tags )
			return;

		foreach ( (array) $tags as $tag ) {
			$counts[$tag->name] = $tag->count;
		}

		// Level
		$min_count = min($counts);
		$spread = max($counts) - $min_count;
		if ( $spread <= 0 )
			$spread = 1;
		$font_step = 12 / $spread;

		// Click tags
		$click_tags = array();
		if ( $this->options['admin_tag_suggested'] == '1' ) { // Loop for suggested
			foreach ( (array) $counts as $tag => $count ) {
				if ( is_string($tag) && !empty($tag) && stristr($post->post_content, $tag) && !in_array($tag, $post_tags) ) {
					$click_tags[] = '<span onclick="javascript:addTag(this.innerHTML);" style="font-size:'.( 10 + ( ( $count - $min_count ) * $font_step ) ).'px;">'.$tag.'</span>';
				}
			}
		} else { // All tags
			foreach ( (array) $counts as $tag => $count ) {
				if ( is_string($tag) && !empty($tag) && !in_array($tag, $post_tags) ) {
					$click_tags[] = '<span onclick="javascript:addTag(this.innerHTML);" style="font-size:'.( 10 + ( ( $count - $min_count ) * $font_step ) ).'px;">'.$tag.'</span>';
				}
			}
		}

		// Remove empty and duplicate elements
		$click_tags = array_filter($click_tags, array(&$this, 'deleteEmptyElement'));
		$click_tags = array_unique($click_tags);

		if ( count($click_tags) > 0 ) {
			$click_tags_str = implode(' ', $click_tags);
		} else {
			$click_tags_str = __("No suggested tags founds.", 'simpletags');
		}
		?>
		<div id="advancedstuff_tag" class="dbx-group" >
			<div class="dbx-b-ox-wrapper">
				<fieldset id="suggesttagsdiv" class="dbx-box">
				<div class="dbx-h-andle-wrapper">
					<h3 class="dbx-handle"><?php _e('Suggested tags', 'simpletags'); ?></h3>
				</div>
				<div class="dbx-c-ontent-wrapper">
					<div class="dbx-content">
						<?php echo $click_tags_str; ?>
					<div class="clearer"></div>
					</div>
				</div>
				</fieldset>
			</div>
		</div>
	    <style type="text/css">
	      /* Clicks-Tags */
	      #suggesttagsdiv p{margin:0;padding:0;}
	      #suggesttagsdiv span{line-height:2;background:#f0f0ee;border:solid 1px;color:#333;cursor:pointer;border-color:#ccc #999 #999 #ccc;margin:5px 3px;padding:1px 2px;}
	      #suggesttagsdiv span:hover{color:#000;background:#b6bdd2;border-color:#0a246a;}
	      #suggesttagsdiv div.clearer{clear:both;line-height:1px;font-size:1px;height:5px;}

	      #advancedstuff_tag fieldset{margin-bottom:1em;}
	      #advancedstuff_tag h3{font-weight:400;font-size:13px;padding:3px;}
	      #advancedstuff_tag div{margin-top:.5em;}
	      #advancedstuff_tag h3.dbx-handle{margin-left:7px;margin-bottom:-7px;height:19px;font-size:12px;background:#2685af url(images/box-head-right.gif) no-repeat top right;padding:6px 1em 0 3px;}
	      #advancedstuff_tag div.dbx-h-andle-wrapper{background:#fff url(images/box-head-left.gif) no-repeat top left;margin:0 0 0 -7px;}
	      #advancedstuff_tag div.dbx-content{margin-left:8px;background:url(images/box-bg-right.gif) repeat-y right;padding:10px 10px 15px 0;}
	      #advancedstuff_tag div.dbx-c-ontent-wrapper{margin-left:-7px;margin-right:0;background:url(images/box-bg-left.gif) repeat-y left;}
	      #advancedstuff_tag fieldset.dbx-box{padding-bottom:9px;margin-left:6px;background:url(images/box-butt-right.gif) no-repeat bottom right;}
	      #advancedstuff_tag div.dbx-b-ox-wrapper{background:url(images/box-butt-left.gif) no-repeat bottom left;}
	      #advancedstuff_tag .dbx-box-closed div.dbx-c-ontent-wrapper{padding-bottom:2px;background:url(images/box-butt-left.gif) no-repeat bottom left;}
	      #advancedstuff_tag .dbx-box{background:url(images/box-butt-right.gif) no-repeat bottom right;}
	      #advancedstuff_tag a.dbx-toggle,#advancedstuff a.dbx-toggle-open:visited{height:22px;width:22px;top:3px;right:5px;background-position:0 -3px;}
	      #advancedstuff_tag a.dbx-toggle-open,#advancedstuff a.dbx-toggle-open:visited{height:22px;width:22px;top:3px;right:5px;background-position:0 -28px;}
	    </style>
    <?php
	}

	/**
	 * Javascript helper for mass edit tags
	 *
	 */
	function helperMassJS() {
		// Get all tags
		$this->all_tags = get_tags('hide_empty=false');

		// If no tags => exit !
		if ( !$this->all_tags ) {
			return;
		}

		// Type-ahead
		foreach ( (array) $this->all_tags as $tag ) {
			$tag_name = str_replace('"', '\"', $tag->name);
			$tags_list .= '"'.$tag_name.'", ';
		}
		$tags_list = substr( $tags_list, 0, strlen($tags_list) - 2);
		?>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/functions.js?ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
	  	<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/bcomplete/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<script type="text/javascript">
			//<![CDATA[
			collection = [
				<?php echo $tags_list; ?>
			];
			//]]>';
		</script>
		<?php
	}

	/**
	 * Get posts/pages data for edit
	 *
	 * @param string $type
	 * @param integer $quantity
	 * @param integer $author
	 * @return array
	 */
	function getObjects( $type = 'post', $quantity = 20, $author = 0, $order = 'date_desc', $filter = 'all', $search = '' ) {
		global $wpdb;

		// Quantity
		$this->data_per_page = $quantity;

		if ( $type == 'post' || $type == 'page' ) { // Posts and Pages
			// Order
			switch ($order) {
				case 'date_asc':
					$order_sql = 'ORDER BY post_date ASC';
				break;
				case 'id_desc':
					$order_sql = 'ORDER BY id DESC';
				break;
				case 'id_asc':
					$order_sql = 'ORDER BY id ASC';
				break;
				default:
					$order_sql = 'ORDER BY post_date DESC';
				break;
			}

			// Search
			$search_sql = '';
			$search = trim($search);
			if ( !empty($search) ) {
				$search = addslashes_gpc($search);
				$search_sql = "AND ( (post_title LIKE '%{$search}%') OR (post_content LIKE '%{$search}%') )";
			}

			// Restrict Author
			$author_sql = ( $author != 0 ) ? "AND post_author = '{$author}'" : '';

			// Status
			$filter_sql = '';
			if ( $filter == 'untagged' ) {
				$p_id_used = $wpdb->get_col("
			      SELECT DISTINCT term_relationships.object_id
			      FROM {$wpdb->term_taxonomy} term_taxonomy, {$wpdb->term_relationships} term_relationships, {$wpdb->posts} posts
			      WHERE term_taxonomy.taxonomy = 'post_tag'
			      AND term_taxonomy.term_taxonomy_id = term_relationships.term_taxonomy_id
			      AND term_relationships.object_id  = posts.ID
			      AND posts.post_type = '{$type}'");

				$filter_sql = 'AND ID NOT IN ("'.implode( '", "', $p_id_used ).'")';
				unset($p_id_used);
			}

			// Get datas with pagination
			$this->found_datas = (int) $wpdb->get_var("
		        SELECT count(ID)
		        FROM {$wpdb->posts} AS posts
		        WHERE post_type = '{$type}'
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

			$ps = $wpdb->get_results("
		        SELECT ID, post_title
		        FROM {$wpdb->posts}
		        WHERE post_type = '{$type}'
		        {$search_sql}
		        {$author_sql}
		        {$filter_sql}
		        {$order_sql}
		        {$limit_sql}");

			foreach ( (array) $ps as $p ) {
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
			if ( ! ( wp_verify_nonce($_POST['secure_masss'], 'simpletags_mass') ) ) {
				$this->message = __('Security problem. Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
				return;
			}

			if ( $type == 'post' || $type == 'page' ) {
				$taxinomy = 'post_tag';
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
					wp_set_object_terms( $object_id, $tags, $taxinomy );
					$counter++;
				}

				if ( $type == 'post' ) {
					$this->message = sprintf(__('%s post(s) tags updated with success !', 'simpletags'), $counter);
				} elseif ( $type == 'page' ) {
					$this->message = sprintf(__('%s page(s) tags updated with success !', 'simpletags'), $counter);
				}
			}
		}
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
}

?>