<?php
Class SimpleTags_AutoTags {
	var $options = array();
	var $options_name = 'st_auto_tags';
	
	function SimpleTags_AutoTags() {
		// Load default options
		$this->options = array(
			'use_auto_tags' => 0,
			'at_all' => 0,
			'at_empty' => 0,
			'auto_list' => ''
		);
		
		// Load options from DB
		$this->options = SimpleTags_Utils::loadOptionsFromDB( $this->options_name, $this->options );

		// WP Filter
		add_action('save_post', array(&$this, 'saveAutoTags'));
		add_action('publish_post', array(&$this, 'saveAutoTags'));	
		add_action('post_syndicated_item', array(&$this, 'saveAutoTags'));
		
		add_action('admin_menu', array(&$this, 'adminMenu'));	
	}
	
	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {
		add_management_page( __('Simple Tags: Auto Tags', 'simpletags'), __('Auto Tags', 'simpletags'), 'simple_tags', 'st_auto', array(&$this, 'pageAutoTags'));
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
		<div id="wpbody"><div class="wrap st_wrap">
			<h2><?php _e('Auto Tags', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>

			<?php if ( $action === false ) : ?>

				<h3><?php _e('Auto tags list', 'simpletags'); ?></h3>
				<p><?php _e('This feature allows Wordpress to look into post content and title for specified tags when saving posts. If your post content or title contains the word "WordPress" and you have "wordpress" in auto tags list, Simple Tags will add automatically "wordpress" as tag for this post.', 'simpletags'); ?></p>

				<h3><?php _e('Options', 'simpletags'); ?></h3>
				<form action="<?php echo $this->admin_base_url.'st_auto'; ?>" method="post">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Activation', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="use_auto_tags" name="use_auto_tags" value="1" <?php echo ( $this->options['use_auto_tags'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="use_auto_tags"><?php _e('Active Auto Tags.', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Tags database', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="at_all" name="at_all" value="1" <?php echo ( $this->options['at_all'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="at_all"><?php _e('Use also local tags database with auto tags. (Warning, this option can increases the CPU consumption a lot if you have many tags)', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Target', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="at_empty" name="at_empty" value="1" <?php echo ( $this->options['at_empty'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="at_empty"><?php _e('Autotag only posts without tags.', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="auto_list"><?php _e('Keywords list', 'simpletags'); ?></label></th>
							<td>
								<input type="text" id="auto_list" class="auto_list" name="auto_list" value="<?php echo $tags_list; ?>" />
								<br /><?php _e('Separated with a comma', 'simpletags'); ?>
								<?php $this->helperBCompleteJS( 'auto_list' ); ?>
							</td>
						</tr>
					</table>						

					<p class="submit"><input class="button" type="submit" name="update_auto_list" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
				</form>

				<h3><?php _e('Auto tags old content', 'simpletags'); ?></h3>
				<p><?php _e('Simple Tags can also tag all existing contents of your blog. This feature use auto tags list above-mentioned.', 'simpletags'); ?> <a class="button" style="font-weight:700;" href="<?php echo $this->admin_base_url.'st_auto'; ?>&amp;action=auto_tag"><?php _e('Auto tags all content &raquo;', 'simpletags'); ?></a></p>

			<?php else:
				// Counter
				if ( $n == 0 ) {
					update_option('tmp_auto_tags_st', 0);
				}	

				// Page or not ?
				$post_type_sql = ( $this->options['use_tag_pages'] == '1' ) ? "post_type IN('page', 'post')" : "post_type = 'post'";

				// Get objects
				global $wpdb;
				$objects = (array) $wpdb->get_results("SELECT p.ID, p.post_title, p.post_content FROM {$wpdb->posts} p WHERE {$post_type_sql} ORDER BY ID DESC LIMIT {$n}, 20");
				
				if( !empty($objects) ) {
					echo '<ul>';
					foreach( $objects as $object ) {
						$this->autoTagsPost( $object );			

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
					$counter = get_option('tmp_auto_tags_st');
					delete_option('tmp_auto_tags_st');
					echo '<p><strong>'.sprintf(__('All done! %s tags added.', 'simpletags'), $counter).'</strong></p>';
				}
				
			endif;
			$this->printAdminFooter(); ?>
		</div></div>
		<?php
	}
	
	/**
	 * Check post/page content for auto tags
	 *
	 * @param integer $post_id
	 * @param array $post_data
	 * @return boolean
	 */
	function saveAutoTags( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return false;
		}

		$result = $this->autoTagsPost( $object );
		if ( $result == true ) {
			// Clean cache
			if ( 'page' == $object->post_type ) {
				clean_page_cache($post_id);
			} else {
				clean_post_cache($post_id);
			}
		}
		return true;
	}
	
	/**
	 * Automatically tag a post/page from the database tags
	 *
	 * @param object $object
	 * @return boolean
	 */
	function autoTagsPost( $object ) {	
		if ( get_the_tags($object->ID) != false && $this->options['at_empty'] == 1 ) {
			return false; // Skip post with tags, if tag only empty post option is checked
		}
		
		$tags_to_add = array();

		// Merge title + content + excerpt to compare with tags
		$content = $object->post_content. ' ' . $object->post_title. ' ' . $object->post_excerpt;
		$content = trim($content);
		if ( empty($content) ) {
			return false;
		}
		
		// Auto tag with specifik auto tags list
		$tags = (array) maybe_unserialize($this->options['auto_list']);
		foreach ( $tags as $tag ) {
			if ( is_string($tag) && !empty($tag) && stristr($content, $tag) ) {
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
					if ( is_string($tag->name) && !empty($tag->name) && stristr($content, $tag->name) ) {
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
			
			// Increment counter
			$counter = ((int) get_option('tmp_auto_tags_st')) + count($tags_to_add);
			update_option('tmp_auto_tags_st', $counter);
			
			// Add tags to posts	
			wp_set_object_terms( $object->ID, $tags_to_add, 'post_tag', true );
			
			// Clean cache
			if ( 'page' == $object->post_type ) {
				clean_page_cache($object->ID);
			} else {
				clean_post_cache($object->ID);
			}
			
			return true;
		}
		return false;
	}	
}
?>