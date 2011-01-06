<?php
class SimpleTags_Admin_Manage extends SimpleTags_Admin {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function SimpleTags_Admin_Manage() {
		// Admin menu
		add_action('admin_menu', array(&$this, 'adminMenu'));
		
		wp_register_script('st-helper-manage', STAGS_URL.'/inc/js/helper-manage.min.js', array('jquery'), STAGS_VERSION);
		
		// add JS for manage click tags
		if ( isset($_GET['page']) && $_GET['page'] == 'st_manage' ) {
			wp_enqueue_script('st-helper-manage');
		}
		
		// Register taxo, parent method...
		$this->registerDetermineTaxonomy();
	}
	
	/**
	 * Add WP admin menu for Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function adminMenu() {
		add_management_page( __('Simple Terms: Manage Terms', 'simpletags'), __('Manage Terms', 'simpletags'), 'simple_tags', 'st_manage', array(&$this, 'pageManageTags'));
	}
	
	/**
	 * Method for build the page HTML manage tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function pageManageTags() {
		global $simple_tags;
		
		// Control Post data
		if ( isset($_POST['term_action']) ) {
			if ( !wp_verify_nonce($_POST['term_nonce'], 'simpletags_admin') ) { // Origination and intention
				
				$this->message = __('Security problem. Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
			
			} elseif ( !isset($_POST['taxonomy']) || !taxonomy_exists($_POST['taxonomy']) ) { // Valid taxo ?
				
				$this->message = __('Missing valid taxonomy for work... Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
			
			} elseif ( $_POST['term_action'] == 'renameterm' ) {
				
				$oldtag = (isset($_POST['renameterm_old'])) ? $_POST['renameterm_old'] : '';
				$newtag = (isset($_POST['renameterm_new'])) ? $_POST['renameterm_new'] : '';
				$this->renameTerms( $_POST['taxonomy'] , $oldtag, $newtag );
			
			} elseif ( $_POST['term_action'] == 'deleteterm' ) {
				
				$todelete = (isset($_POST['deleteterm_name'])) ? $_POST['deleteterm_name'] : '';
				$this->deleteTermsByTermList( $_POST['taxonomy'], $todelete );
			
			} elseif ( $_POST['term_action'] == 'addterm'  ) {
				
				$matchtag = (isset($_POST['addterm_match'])) ? $_POST['addterm_match'] : '';
				$newtag   = (isset($_POST['addterm_new'])) ? $_POST['addterm_new'] : '';
				$this->addMatchTerms( $_POST['taxonomy'], $matchtag, $newtag );
			
			} elseif ( $_POST['term_action'] == 'editslug'  ) {
				
				$matchtag = (isset($_POST['tagname_match'])) ? $_POST['tagname_match'] : '';
				$newslug  = (isset($_POST['tagslug_new'])) ? $_POST['tagslug_new'] : '';
				$this->editTermSlug( $_POST['taxonomy'], $matchtag, $newslug );
			
			} elseif ( $_POST['term_action'] == 'cleandb'  ) {
				
				$this->cleanDatabase();
			
			}
		}
		
		// Default order
		if ( !isset($_GET['order']) ) {
			$_GET['order'] = 'name-asc';
		}
		
		$this->displayMessage();
		
		if ( isset($simple_tags['admin-autocomplete']) ) :
		?>
		<script type="text/javascript">
			<!--
			initAutoComplete( '.autocomplete-input', '<?php echo admin_url('admin.php') .'?st_ajax_action=helper_js_collection&taxonomy='.$this->taxonomy; ?>', 300 );
			-->
		</script>
		<?php endif; ?>
		
		<div class="wrap st_wrap">
			<?php $this->boxSelectorTaxonomy( 'st_manage' ); ?>
			
			<h2><?php _e('Simple Tags: Manage Terms', 'simpletags'); ?></h2>
			<p><?php _e('Visit the <a href="http://redmine.beapi.fr/wiki/simple-tags/Theme_integration">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			
			<div class="clear"></div>
			<div id="term-list">
				<h3><?php _e('Click terms list:', 'simpletags'); ?></h3>
				<form action="" method="get">
					<div>
						<input type="hidden" name="page" value="st_manage" />
						<select name="order">
							<option <?php if($_GET['order']=='count-asc') echo'selected="selected"'; ?> value="count-asc"><?php _e('Least used', 'simpletags'); ?></option>
							<option <?php if($_GET['order']=='count-desc') echo'selected="selected"'; ?> value="count-desc"><?php _e('Most popular', 'simpletags'); ?></option>
							<option <?php if($_GET['order']=='name-asc') echo'selected="selected"'; ?> value="name-asc"><?php _e('Alphabetical (default)', 'simpletags'); ?></option>
							<option <?php if($_GET['order']=='name-desc') echo'selected="selected"'; ?> value="name-desc"><?php _e('Inverse Alphabetical', 'simpletags'); ?></option>
							<option <?php if($_GET['order']=='random') echo'selected="selected"'; ?> value="random"><?php _e('Random', 'simpletags'); ?></option>
						</select>
						<input class="button" type="submit" value="<?php _e('Sort', 'simpletags'); ?>" />
					</div>
				</form>
				
				<div id="term-list-inner">
					<?php
					if ( isset($_GET['order']) ) {
						$order = explode('-', stripslashes($_GET['order']));
						if ( !isset($order[1]) ) $order[1] = ''; // for skip notice on random...
						
						$order = '&selectionby='.$order[0].'&selection='.$order[1].'&orderby='.$order[0].'&order='.$order[1];
					} else {
						$order = '&selectionby=name&selection=asc&orderby=name&order=asc';
					}
					st_tag_cloud('hide_empty=false&number=&color=false&get=all&title='.$order.'&taxonomy='.$this->taxonomy);
					?>
				</div>
			</div>
			
			<table id="manage-table-tags" class="form-table">
				<tr valign="top">
					<th scope="row"><strong><?php _e('Rename/Merge Terms', 'simpletags'); ?></strong></th>
					<td>
						<p><?php _e('Enter the term to rename and its new value. You can use this feature to merge terms too. Click "Rename" and all posts which use this term will be updated.', 'simpletags'); ?></p>
						<p><?php _e('You can specify multiple terms to rename by separating them with commas.', 'simpletags'); ?></p>
						
						<fieldset>
							<form action="" method="post">
								<input type="hidden" name="taxonomy" value="<?php echo $this->taxonomy; ?>" />
								<input type="hidden" name="term_action" value="renameterm" />
								<input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								
								<p>
									<label for="renameterm_old"><?php _e('Term(s) to rename:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="renameterm_old" name="renameterm_old" value="" size="40" />
								</p>
								
								<p>
									<label for="renameterm_new"><?php _e('New term name(s):', 'simpletags'); ?>
									<br />
									<input type="text" class="autocomplete-input" id="renameterm_new" name="renameterm_new" value="" size="40" />
								</p>
								
								<input class="button-primary" type="submit" name="rename" value="<?php _e('Rename', 'simpletags'); ?>" />
							</form>
						</fieldset>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><strong><?php _e('Delete Terms', 'simpletags'); ?></strong></th>
					<td>
						<p><?php _e('Enter the name of terms to delete. Terms will be removed from all posts.', 'simpletags'); ?></p>
						<p><?php _e('You can specify multiple terms to delete by separating them with commas', 'simpletags'); ?>.</p>
						
						<fieldset>
							<form action="" method="post">
								<input type="hidden" name="taxonomy" value="<?php echo $this->taxonomy; ?>" />
								<input type="hidden" name="term_action" value="deleteterm" />
								<input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								
								<p>
									<label for="deleteterm_name"><?php _e('Term(s) to delete:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="deleteterm_name" name="deleteterm_name" value="" size="40" />
								</p>
								
								<input class="button-primary" type="submit" name="delete" value="<?php _e('Delete', 'simpletags'); ?>" />
							</form>
						</fieldset>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><strong><?php _e('Add Terms', 'simpletags'); ?></strong></th>
					<td>
						<p><?php _e('This feature lets you add one or more new terms to all posts which match any of the terms given.', 'simpletags'); ?></p>
						<p><?php _e('You can specify multiple terms to add by separating them with commas.  If you want the term(s) to be added to all posts, then don\'t specify any terms to match.', 'simpletags'); ?></p>
						
						<fieldset>
							<form action="" method="post">
								<input type="hidden" name="taxonomy" value="<?php echo $this->taxonomy; ?>" />
								<input type="hidden" name="term_action" value="addterm" />
								<input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								
								<p>
									<label for="addterm_match"><?php _e('Term(s) to match:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="addterm_match" name="addterm_match" value="" size="40" />
								</p>
								
								<p>
									<label for="addterm_new"><?php _e('Term(s) to add:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="addterm_new" name="addterm_new" value="" size="40" />
								</p>
								
								<input class="button-primary" type="submit" name="Add" value="<?php _e('Add', 'simpletags'); ?>" />
							</form>
						</fieldset>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><strong><?php _e('Edit Term Slug', 'simpletags'); ?></strong></th>
					<td>
						<p><?php _e('Enter the term name to edit and its new slug. <a href="http://codex.wordpress.org/Glossary#Slug">Slug definition</a>', 'simpletags'); ?></p>
						<p><?php _e('You can specify multiple terms to rename by separating them with commas.', 'simpletags'); ?></p>
						
						<fieldset>
							<form action="" method="post">
								<input type="hidden" name="taxonomy" value="<?php echo $this->taxonomy; ?>" />
								<input type="hidden" name="term_action" value="editslug" />
								<input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								
								<p>
									<label for="tagname_match"><?php _e('Term(s) to match:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="tagname_match" name="tagname_match" value="" size="40" />
								</p>
								
								<p>
									<label for="tagslug_new"><?php _e('Slug(s) to set:', 'simpletags'); ?></label>
									<br />
									<input type="text" class="autocomplete-input" id="tagslug_new" name="tagslug_new" value="" size="40" />
								</p>
								
								<input class="button-primary" type="submit" name="edit" value="<?php _e('Edit', 'simpletags'); ?>" />
							</form>
						</fieldset>
					</td>
				</tr>
				
				<tr valign="top">
					<th scope="row"><strong><?php _e('Remove empty terms', 'simpletags'); ?></strong></th>
					<td>
						<p><?php _e('Old WordPress versions have a small bug and allow to create empty terms. Remove it !', 'simpletags'); ?></p>
						
						<fieldset>
							<form action="" method="post">
								<input type="hidden" name="taxonomy" value="<?php echo $this->taxonomy; ?>" />
								<input type="hidden" name="term_action" value="cleandb" />
								<input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />
								
								<p>
									<input class="button-primary" type="submit" name="clean" value="<?php _e('Clean !', 'simpletags'); ?>" />
								</p>
							</form>
						</fieldset>
					</td>
				</tr>
				
				<tr>
					<th scope="row"><strong><?php _e('Technical informations', 'simpletags'); ?></strong></th>
					<td>
						<p><strong><?php _e('Renaming', 'simpletags'); ?></strong></p>
						<p><em><?php _e('Simple Tags don\'t use the same method as WordPress for rename a term. For example, in WordPress you have 2 terms : "Blogging" and "Bloging". When you want edit the term "Bloging" for rename it on "Blogging", WordPress will keep the two terms with the same name but with a different slug. <br />With Simple Tags, when you edit "Bloging" for "Blogging", Simple Tags merge posts filled with "Bloging" to "Blogging" and it delete the term "Bloging". Another logic ;)', 'simpletags'); ?><em></p>
					</td>
				</tr>
			</table>
			
			<div class="clear"></div>
			<?php $this->printAdminFooter(); ?>
		</div>
		<?php
	}
	
	/**
	 * Method for rename or merge tags
	 *
	 * @param string $taxonomy
	 * @param string $old
	 * @param string $new
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function renameTerms( $taxonomy = 'post_tag', $old = '', $new = '' ) {
		if ( trim( str_replace(',', '', stripslashes($new)) ) == '' ) {
			$this->message = __('No new term specified!', 'simpletags');
			$this->status = 'error';
			return false;
		}
		
		// String to array
		$old_terms = explode(',', $old);
		$new_terms = explode(',', $new);
		
		// Remove empty element and trim
		$old_terms = array_filter($old_terms, '_delete_empty_element');
		$new_terms = array_filter($new_terms, '_delete_empty_element');
		
		// If old/new tag are empty => exit !
		if ( empty($old_terms) || empty($new_terms) ) {
			$this->message = __('No new/old valid term specified!', 'simpletags');
			$this->status = 'error';
			return false;
		}
		
		$counter = 0;
		if( count($old_terms) == count($new_terms) ) { // Rename only
			foreach ( (array) $old_terms as $i => $old_tag ) {
				$new_name = $new_terms[$i];
				
				// Get term by name
				$term = get_term_by('name', $old_tag, $taxonomy);
				if ( !$term ) {
					continue;
				}
				
				// Get objects from term ID
				$objects_id = get_objects_in_term( $term->term_id, $taxonomy, array('fields' => 'all_with_object_id'));
				
				// Delete old term
				wp_delete_term( $term->term_id, $taxonomy );
				
				// Set objects to new term ! (Append no replace)
				foreach ( (array) $objects_id as $object_id ) {
					wp_set_object_terms( $object_id, $new_name, $taxonomy, true );
				}
				
				// Clean cache
				clean_object_term_cache( $objects_id, $taxonomy);
				clean_term_cache($term->term_id, $taxonomy);
				
				// Increment
				$counter++;
			}
			
			if ( $counter == 0  ) {
				$this->message = __('No term renamed.', 'simpletags');
			} else {
				$this->message = sprintf(__('Renamed term(s) &laquo;%1$s&raquo; to &laquo;%2$s&raquo;', 'simpletags'), $old, $new);
			}
		}
		elseif ( count($new_terms) == 1  ) { // Merge
			// Set new tag
			$new_tag = $new_terms[0];
			if ( empty($new_tag) ) {
				$this->message = __('No valid new term.', 'simpletags');
				$this->status = 'error';
				return false;
			}
			
			// Get terms ID from old terms names
			$terms_id = array();
			foreach ( (array) $old_terms as $old_tag ) {
				$term = get_term_by('name', addslashes($old_tag), $taxonomy);
				$terms_id[] = (int) $term->term_id;
			}
			
			// Get objects from terms ID
			$objects_id = get_objects_in_term( $terms_id, $taxonomy, array('fields' => 'all_with_object_id'));
			
			// No objects ? exit !
			if ( !$objects_id ) {
				$this->message = __('No objects found for specified old terms.', 'simpletags');
				$this->status = 'error';
				return false;
			}
			
			// Delete old terms
			foreach ( (array) $terms_id as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
			
			// Set objects to new term ! (Append no replace)
			foreach ( (array) $objects_id as $object_id ) {
				wp_set_object_terms( $object_id, $new_tag, $taxonomy, true );
				$counter++;
			}
			
			// Test if term is also a category
			// TODO ?
			if ( is_term($new_tag, 'category') ) {
				// Edit the slug to use the new term
				$this->editTermSlug( $new_tag, sanitize_title($new_tag) );
			}
			
			// Clean cache
			clean_object_term_cache( $objects_id, $taxonomy);
			clean_term_cache($terms_id, $taxonomy);
			
			if ( $counter == 0  ) {
				$this->message = __('No term merged.', 'simpletags');
			} else {
				$this->message = sprintf(__('Merge term(s) &laquo;%1$s&raquo; to &laquo;%2$s&raquo;. %3$s objects edited.', 'simpletags'), $old, $new, $counter);
			}
		} else { // Error
			$this->message = sprintf(__('Error. No enough terms for rename. Too for merge. Choose !', 'simpletags'), $old);
			$this->status = 'error';
		}
		return true;
	}
	
	/**
	 * Method for delete a list of terms
	 *
	 * @param string $taxonomy
	 * @param string $delete
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function deleteTermsByTermList( $taxonomy = 'post_tag', $delete ) {
		if ( trim( str_replace(',', '', stripslashes($delete)) ) == '' ) {
			$this->message = __('No term specified!', 'simpletags');
			$this->status = 'error';
			return false;
		}
		
		// In array + filter
		$delete_terms = explode(',', $delete);
		$delete_terms = array_filter($delete_terms, '_delete_empty_element');
		
		// Delete tags
		$counter = 0;
		foreach ( (array) $delete_terms as $term ) {
			$term = get_term_by('name', $term, $taxonomy);
			$term_id = (int) $term->term_id;
			
			if ( $term_id != 0 ) {
				wp_delete_term( $term_id, $taxonomy );
				clean_term_cache( $term_id, $taxonomy );
				$counter++;
			}
		}
		
		if ( $counter == 0  ) {
			$this->message = __('No term deleted.', 'simpletags');
		} else {
			$this->message = sprintf(__('%1s term(s) deleted.', 'simpletags'), $counter);
		}
		
		return true;
	}
	
	/**
	 * Method for add terms for all or specified posts
	 *
	 * @param string $taxonomy
	 * @param string $match
	 * @param string $new
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function addMatchTerms( $taxonomy = 'post_tag', $match, $new ) {
		if ( trim( str_replace(',', '', stripslashes($new)) ) == '' ) {
			$this->message = __('No new term(s) specified!', 'simpletags');
			$this->status = 'error';
			return false;
		}
		
		$match_terms = explode(',', $match);
		$new_terms = explode(',', $new);
		
		$match_terms = array_filter($match_terms, '_delete_empty_element');
		$new_terms = array_filter($new_terms, '_delete_empty_element');
		
		$counter = 0;
		if ( !empty($match_terms) ) { // Match and add
			// Get terms ID from old match names
			$terms_id = array();
			foreach ( (array) $match_terms as $match_term ) {
				$term = get_term_by('name', $match_term, $taxonomy);
				$terms_id[] = (int) $term->term_id;
			}
			
			// Get object ID with terms ID
			$objects_id = get_objects_in_term( $terms_id, $taxonomy, array('fields' => 'all_with_object_id') );
			
			// Add new tags for specified post
			foreach ( (array) $objects_id as $object_id ) {
				wp_set_object_terms( $object_id, $new_terms, $taxonomy, true ); // Append tags
				$counter++;
			}
			
			// Clean cache
			clean_object_term_cache( $objects_id, $taxonomy );
			clean_term_cache($terms_id, $taxonomy );
		} else { // Add for all posts
			// Page or not ?
			$post_type_sql = ( is_page_have_tags() ) ? "post_type IN('page', 'post')" : "post_type = 'post'"; // TODO, CTP
			
			// Get all posts ID
			global $wpdb;
			$objects_id = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE {$post_type_sql}");
			
			// Add new tags for all posts
			foreach ( (array) $objects_id as $object_id ) {
				wp_set_object_terms( $object_id, $new_terms, $taxonomy, true ); // Append tags
				$counter++;
			}
			
			// Clean cache
			clean_object_term_cache( $objects_id, $taxonomy );
		}
		
		if ( $counter == 0  ) {
			$this->message = __('No term added.', 'simpletags');
		} else {
			$this->message = sprintf(__('Term(s) added to %1s post(s).', 'simpletags'), $counter);
		}
		
		return true;
	}
	
	/**
	 * Method for edit one or more terms slug
	 *
	 * @param string $taxonomy
	 * @param string $names
	 * @param string $slugs
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function editTermSlug( $taxonomy = 'post_tag', $names = '', $slugs = '') {
		if ( trim( str_replace(',', '', stripslashes($slugs)) ) == '' ) {
			$this->message = __('No new slug(s) specified!', 'simpletags');
			$this->status = 'error';
			return false;
		}
		
		$match_names = explode(',', $names);
		$new_slugs = explode(',', $slugs);
		
		$match_names = array_filter($match_names, '_delete_empty_element');
		$new_slugs = array_filter($new_slugs, '_delete_empty_element');
		
		if ( count($match_names) != count($new_slugs) ) {
			$this->message = __('Terms number and slugs number isn\'t the same!', 'simpletags');
			$this->status = 'error';
			return false;
		} else {
			$counter = 0;
			foreach ( (array) $match_names as $i => $match_name ) {
				// Sanitize slug + Escape
				$new_slug = sanitize_title($new_slugs[$i]);
				
				// Get term by name
				$term = get_term_by('name', $match_name, $taxonomy);
				if ( !$term ) {
					continue;
				}
				
				// Increment
				$counter++;
				
				// Update term
				wp_update_term($term->term_id, $taxonomy, array('slug' => $new_slug));
				
				// Clean cache
				clean_term_cache($term->term_id, $taxonomy);
			}
		}
		
		if ( $counter == 0  ) {
			$this->message = __('No slug edited.', 'simpletags');
		} else {
			$this->message = sprintf(__('%s slug(s) edited.', 'simpletags'), $counter);
		}
		
		return true;
	}
	
	/**
	 * Clean database - Remove empty terms
	 *
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function cleanDatabase() {
		global $wpdb;
		
		// Counter
		$counter = 0;
		
		// Get terms id empty
		$terms_id = $wpdb->get_col("SELECT term_id FROM {$wpdb->terms} WHERE name IN ('', ' ', '  ', '&nbsp;') GROUP BY term_id");
		if ( empty($terms_id) ) {
			$this->message = __('Nothing to muck. Good job !', 'simpletags');
			return false;
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
		
		// Delete cache
		clean_term_cache($terms_id, array('category', 'post_tag')); // TODO: All taxos ?
		clean_object_term_cache($tts_list, 'post'); // TODO: CTP !
		
		$this->message = sprintf(__('%s rows deleted. WordPress DB is clean now !', 'simpletags'), $counter);
		return true;
	}
}
?>