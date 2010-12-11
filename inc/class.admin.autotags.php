<?php
class SimpleTags_Admin_AutoTags {
	// Application entrypoint -> http://redmine.beapi.fr/projects/show/simple-tags/
	var $yahoo_id = 'h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--';

	function SimpleTags_Admin_AutoTags() {
		// Admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));
		
		// Ajax action, JS Helper and admin action
		add_action('admin_init', array(&$this, 'ajaxCheck'));
		
		// Auto tags
		if ( $this->options['use_auto_tags'] == 1 ) {
			add_actions( array('save_post', 'publish_post', 'post_syndicated_item'), array(&$this, 'saveAutoTags'), 10, 2 );
		}
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function adminMenu() {
		add_posts_page( __('Simple Terms: Auto Terms', 'simpletags'), __('Auto Terms', 'simpletags'), 'simple_tags', 'st_auto', array(&$this, 'pageAutoTags'));
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
			if ( isset($object->post_type) && 'page' == $object->post_type ) {
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
		$content = $object->post_content. ' ' . $object->post_title;
		if ( isset($object->post_excerpt) )
		 	$content .= ' ' . $object->post_excerpt;
		
		$content = trim(strip_tags($content));
		if ( empty($content) ) {
			return false;
		}
		
		// Auto tag with specifik auto tags list
		$tags = (array) maybe_unserialize($this->options['auto_list']);
		foreach ( $tags as $tag ) {
			if ( !is_string($tag) && empty($tag) )
			 	continue;
			
			// Whole word ?
			if ( (int) $this->options['only_full_word'] == 1 ) {
				$tag = ' '.$tag.' '; // Add space before and after !
			}
			
			if ( stristr($content, $tag) ) {
				$tags_to_add[] = $tag;
			}
		}
		unset($tags, $tag);
		
		// Auto tags with all posts
		if ( $this->options['at_all'] == 1 ) {
			// Get all terms
			global $wpdb;
			$terms = $wpdb->get_col("
				SELECT DISTINCT name
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = 'post_tag'
			");
			$terms = array_unique($terms);
			
			foreach ( $terms as $term ) {
				$term = stripslashes($term);
				
				if ( !is_string($term) && empty($term) )
				 	continue;
				
				// Whole word ?
				if ( (int) $this->options['only_full_word'] == 1 ) {
					$term = ' '.$term.' '; // Add space before and after !
				}
				
				if ( stristr($content, $term) ) {
					$tags_to_add[] = $term;
				}
			}
			
			// Clean memory
			$terms = array();
			unset($terms, $term);
		}
		
		// Append tags if tags to add
		if ( !empty($tags_to_add) ) {
			// Remove empty and duplicate elements
			$tags_to_add = array_filter($tags_to_add, '_delete_empty_element');
			$tags_to_add = array_unique($tags_to_add);
			
			// Increment counter
			$counter = ((int) get_option('tmp_auto_tags_st')) + count($tags_to_add);
			update_option('tmp_auto_tags_st', $counter);
			
			// Add tags to posts
			wp_set_object_terms( $object->ID, $tags_to_add, 'post_tag', true );
			
			// Clean cache
			if ( isset($object->post_type) && 'page' == $object->post_type ) {
				clean_page_cache($object->ID);
			} else {
				clean_post_cache($object->ID);
			}
			
			return true;
		}
		return false;
	}
	
	/**
	 * WP Page - Auto Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function pageAutoTags() {
		$action = false;
		if ( isset($_POST['update_auto_list']) ) {
			// Tags list
			$tags_list = stripslashes($_POST['auto_list']);
			$tags = explode(',', $tags_list);
			
			// Remove empty and duplicate elements
			$tags = array_filter($tags, '_delete_empty_element');
			$tags = array_unique($tags);
			
			$this->setOption( 'auto_list', maybe_serialize($tags) );
			
			// Active auto tags ?
			if ( isset($_POST['use_auto_tags']) && $_POST['use_auto_tags'] == '1' ) {
				$this->setOption( 'use_auto_tags', '1' );
			} else {
				$this->setOption( 'use_auto_tags', '0' );
			}
			
			// All tags ?
			if ( isset($_POST['at_all']) && $_POST['at_all'] == '1' ) {
				$this->setOption( 'at_all', '1' );
			} else {
				$this->setOption( 'at_all', '0' );
			}
			
			// Empty only ?
			if ( isset($_POST['at_empty']) && $_POST['at_empty'] == '1' ) {
				$this->setOption( 'at_empty', '1' );
			} else {
				$this->setOption( 'at_empty', '0' );
			}
			
			// Full word ?
			if ( isset($_POST['only_full_word']) && $_POST['only_full_word'] == '1' ) {
				$this->setOption( 'only_full_word', '1' );
			} else {
				$this->setOption( 'only_full_word', '0' );
			}
			
			$this->saveOptions();
			$this->message = __('Auto tags options updated !', 'simpletags');
		} elseif ( isset($_GET['action']) && $_GET['action'] == 'auto_tag' ) {
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
		<script type="text/javascript">
			<!--
			initAutoComplete( '#auto_list', '<?php echo admin_url('admin.php') .'?st_ajax_action=helper_js_collection&taxonomy='.$this->taxonomy; ?>', 300 );
			-->
		</script>
		
		<div class="wrap st_wrap">
			<h2><?php _e('Auto Terms', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://redmine.beapi.fr/projects/show/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			
			<?php if ( $action === false ) : ?>
				
				<h3><?php _e('Auto terms list', 'simpletags'); ?></h3>
				<p><?php _e('This feature allows Wordpress to look into post content and title for specified terms when saving posts. If your post content or title contains the word "WordPress" and you have "wordpress" in auto terms list, Simple Tags will add automatically "wordpress" as term for this post.', 'simpletags'); ?></p>
				
				<h3><?php _e('Options', 'simpletags'); ?></h3>
				<form action="<?php echo $this->posts_base_url.'st_auto'; ?>" method="post">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e('Activation', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="use_auto_tags" name="use_auto_tags" value="1" <?php echo ( $this->options['use_auto_tags'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="use_auto_tags"><?php _e('Active Auto Tags.', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Terms database', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="at_all" name="at_all" value="1" <?php echo ( $this->options['at_all'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="at_all"><?php _e('Use also local terms database with auto tags. (Warning, this option can increases the CPU consumption a lot if you have many terms)', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Target', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="at_empty" name="at_empty" value="1" <?php echo ( $this->options['at_empty'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="at_empty"><?php _e('Autotag only posts without terms.', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e('Whole Word ?', 'simpletags'); ?></th>
							<td>
								<input type="checkbox" id="only_full_word" name="only_full_word" value="1" <?php echo ( $this->options['only_full_word'] == 1 ) ? 'checked="checked"' : ''; ?>  />
								<label for="only_full_word"><?php _e('Autotag only a post when tags finded in the content are a the same name. (whole word only)', 'simpletags'); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="auto_list"><?php _e('Keywords list', 'simpletags'); ?></label></th>
							<td>
								<input type="text" id="auto_list" class="auto_list" name="auto_list" value="<?php echo esc_attr($tags_list); ?>" style="width:98%;" />
								<br /><?php _e('Separated with a comma', 'simpletags'); ?>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<input class="button-primary" type="submit" name="update_auto_list" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
					</p>
				</form>
				
				<h3><?php _e('Auto terms old content', 'simpletags'); ?></h3>
				<p>
					<?php _e('Simple Tags can also tag all existing contents of your blog. This feature use auto terms list above-mentioned.', 'simpletags'); ?>
				</p>
				<p class="submit">
					<a class="button-primary" href="<?php echo $this->posts_base_url.'st_auto'; ?>&amp;action=auto_tag"><?php _e('Auto terms all content &raquo;', 'simpletags'); ?></a>
				</p>
			
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
					<p><?php _e("If your browser doesn't start loading the next page automatically click this link:", 'simpletags'); ?> <a href="<?php echo $this->posts_base_url.'st_auto'; ?>&amp;action=auto_tag&amp;n=<?php echo ($n + 20) ?>"><?php _e('Next content', 'simpletags'); ?></a></p>
					<script type="text/javascript">
						// <![CDATA[
						function nextPage() {
							location.href = '<?php echo $this->posts_base_url.'st_auto'; ?>&action=auto_tag&n=<?php echo ($n + 20) ?>';
						}
						window.setTimeout( 'nextPage()', 300 );
						// ]]>
					</script>
					<?php
				} else {
					$counter = get_option('tmp_auto_tags_st');
					delete_option('tmp_auto_tags_st');
					echo '<p><strong>'.sprintf(__('All done! %s terms added.', 'simpletags'), $counter).'</strong></p>';
				}
			
			endif;
			$this->printAdminFooter(); ?>
		</div>
		<?php
	}
	
}
?>