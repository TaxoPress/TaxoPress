<?php

class SimpleTags_Admin_Manage
{
    const MENU_SLUG = 'st_options';

    // class instance
    public static $instance;

    /**
     * Constructor
     *
     * @return void
     * @author WebFactory Ltd
     */
    public function __construct()
    {
        add_filter('set-screen-option', [ __CLASS__, 'set_screen' ], 10, 3);
        // Admin menu
        add_action('admin_menu', array( $this, 'admin_menu' ));

        // Register taxo, parent method...
        SimpleTags_Admin::register_taxonomy();

        // Javascript
        add_action('admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ), 11);
    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author WebFactory Ltd
     */
    public static function admin_enqueue_scripts()
    {
        wp_register_script('st-helper-manage', STAGS_URL . '/assets/js/helper-manage.js', array( 'jquery' ), STAGS_VERSION);

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_manage') {
            wp_enqueue_script('st-helper-manage');
        }
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author WebFactory Ltd
     */
    public function admin_menu()
    {
        $hook = add_submenu_page(
            self::MENU_SLUG,
            __('TaxoPress: Manage Terms', 'simple-tags'),
            __('Manage Terms', 'simple-tags'),
            'simple_tags',
            'st_manage',
            array(
                $this,
                'page_manage_tags',
            )
        );
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author WebFactory Ltd
     */
    public function page_manage_tags()
    {
        $default_tab = '';
        // Control Post data
        if (isset($_POST['term_action'])) {
            if (!current_user_can('simple_tags')) {
                add_settings_error(__CLASS__, __CLASS__, esc_html__('Permission denied!', 'simple-tags'), 'error');
            } elseif (! wp_verify_nonce(sanitize_text_field($_POST['term_nonce']), 'simpletags_admin')) { // Origination and intention

                add_settings_error(__CLASS__, __CLASS__, esc_html__('Security problem. Try again.', 'simple-tags'), 'error');
            } elseif (! isset(SimpleTags_Admin::$taxonomy) || ! taxonomy_exists(SimpleTags_Admin::$taxonomy)) { // Valid taxo ?

                add_settings_error(__CLASS__, __CLASS__, esc_html__('Missing valid taxonomy for work. Try again.', 'simple-tags'), 'error');
            } elseif ($_POST['term_action'] == 'renameterm') {
                $oldtag = (isset($_POST['renameterm_old'])) ? sanitize_text_field($_POST['renameterm_old']) : '';
                $newtag = (isset($_POST['renameterm_new'])) ? sanitize_text_field($_POST['renameterm_new']) : '';
                self::renameTerms(SimpleTags_Admin::$taxonomy, $oldtag, $newtag);
                $default_tab = '.st-rename-terms';
            } elseif ($_POST['term_action'] == 'mergeterm') {
                $oldtag = (isset($_POST['renameterm_old'])) ? sanitize_text_field($_POST['renameterm_old']) : '';
                $newtag = (isset($_POST['renameterm_new'])) ? sanitize_text_field($_POST['renameterm_new']) : '';
                self::mergeTerms(SimpleTags_Admin::$taxonomy, $oldtag, $newtag);
                $default_tab = '.st-merge-terms';
            } elseif ($_POST['term_action'] == 'addterm') {
                $matchtag = (isset($_POST['addterm_match'])) ? sanitize_text_field($_POST['addterm_match']) : '';
                $newtag   = (isset($_POST['addterm_new'])) ? sanitize_text_field($_POST['addterm_new']) : '';
                self::addMatchTerms(SimpleTags_Admin::$taxonomy, $matchtag, $newtag);
                $default_tab = '.st-add-terms';
            } elseif ($_POST['term_action'] == 'remove-rarelyterms') {
                self::removeRarelyUsed(SimpleTags_Admin::$taxonomy, (int) $_POST['number-rarely']);
                $default_tab = '.st-delete-unuused-terms';
            } /* elseif ( $_POST['term_action'] == 'editslug'  ) {

                $matchtag = (isset($_POST['tagname_match'])) ? $_POST['tagname_match'] : '';
                $newslug  = (isset($_POST['tagslug_new'])) ? $_POST['tagslug_new'] : '';
                self::editTermSlug( SimpleTags_Admin::$taxonomy, $matchtag, $newslug );

            }*/
        }

        if ($default_tab && !empty($default_tab)) {
            //trigger default tab click on load
            echo '<div class="load-st-default-tab" data-page="'.esc_attr($default_tab).'"></div>';
        }

        // Default order
        if (! isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__); ?>
<div class="clear"></div>
<div class="taxopress-block-wrap">
<div class="wrap st_wrap tagcloudui st-manage-terms-page admin-settings wrap st_wrap">
	<?php SimpleTags_Admin::boxSelectorTaxonomy('st_manage'); ?>

	<h1><?php _e('Manage Terms', 'simple-tags'); ?>
	</h1>
    <div class="taxopress-description"><?php esc_html_e('This feature allows you to manage your content terms by adding, renaming, merging and deleting unused terms.', 'simple-tags'); ?></div>

	<div class="clear"></div>

		<hr class="wp-header-end">
		<div class="clear"></div>

		<div id="col-container" class="wp-clearfix">
			<div class="col-wrap">
				<div class="form-wrap">
					<ul class="simple-tags-nav-tab-wrapper">
						<li class="nav-tab nav-tab-active" data-page=".st-add-terms">
							<?php echo esc_html__('Add terms', 'simple-tags'); ?>
						</li>
						<li class="nav-tab" data-page=".st-rename-terms">
							<?php echo esc_html__('Rename terms', 'simple-tags'); ?>
						</li>
						<li class="nav-tab" data-page=".st-merge-terms">
							<?php echo esc_html__('Merge terms', 'simple-tags'); ?>
						</li>
						<li class="nav-tab" data-page=".st-delete-unuused-terms">
							<?php echo esc_html__('Delete unused terms', 'simple-tags'); ?>
						</li>
					</ul>
					<div class="clear"></div>



					<table class="form-table">



						<tr valign="top" class="auto-terms-content st-add-terms">
							<td>
								<h2><?php _e('Add Terms', 'simple-tags'); ?>
								</h2>
								<p><?php printf(esc_html__('This feature lets you add one or more new terms to all %s which match any of the terms given.', 'simple-tags'), esc_html(SimpleTags_Admin::$post_type_name)); ?>
								</p>

								<p><?php printf(esc_html__('Terms will be added to all %s If no "Term(s) to match" is specified.', 'simple-tags'), esc_html(SimpleTags_Admin::$post_type_name)); ?>
								</p>

								<fieldset>
									<form action="" method="post">
										<input type="hidden" name="taxo"
											value="<?php echo esc_attr(SimpleTags_Admin::$taxonomy); ?>" />
										<input type="hidden" name="cpt"
											value="<?php echo esc_attr(SimpleTags_Admin::$post_type); ?>" />

										<input type="hidden" name="term_action" value="addterm" />
										<input type="hidden" name="term_nonce"
											value="<?php echo esc_attr(wp_create_nonce('simpletags_admin')); ?>" />

										<p class="terms-type-options">
											<label><input type="radio" id="addterm_type" class="addterm_type_all_posts"
													name="addterm_type" value="all_posts"
													checked="checked"><?php printf(esc_html__('Add terms to all %s.', 'simple-tags'), esc_html(SimpleTags_Admin::$post_type_name)); ?></label><br>

											<label><input type="radio" id="addterm_type"
													class="addterm_type_matched_only" name="addterm_type"
													value="matched_only"><?php _e('Add terms only to posts with specific terms attached.', 'simple-tags'); ?></label>
										</p>

										<p class="terms-to-maatch-input" style="display: none;">
											<label
												for="addterm_match"><?php _e('Term(s) to match:', 'simple-tags'); ?></label>
											<br />
											<textarea type="text" class="autocomplete-input tag-cloud-input taxopress-expandable-textarea"
												id="addterm_match" name="addterm_match" size="80" /></textarea>
										</p>

										<p>
											<label
												for="addterm_new"><?php _e('Term(s) to add:', 'simple-tags'); ?></label>
											<br />
											<textarea type="text" class="autocomplete-input taxopress-expandable-textarea" id="addterm_new"
												name="addterm_new" size="80" /></textarea>
										</p>

										<input class="button-primary" type="submit" name="Add"
											value="<?php _e('Add', 'simple-tags'); ?>" />
									</form>
								</fieldset>
							</td>
						</tr>

						<tr valign="top" style="display:none;" class="auto-terms-content st-rename-terms">
							<td>
								<h2><?php _e('Rename Terms', 'simple-tags'); ?>
								</h2>
								<p><?php _e('Enter the terms to rename and their new names.', 'simple-tags'); ?>
								</p>

								<fieldset>
									<form action="" method="post">
										<input type="hidden" name="taxo"
											value="<?php echo esc_attr(SimpleTags_Admin::$taxonomy); ?>" />
										<input type="hidden" name="cpt"
											value="<?php echo esc_attr(SimpleTags_Admin::$post_type); ?>" />

										<input type="hidden" name="term_action" value="renameterm" />
										<input type="hidden" name="term_nonce"
											value="<?php echo esc_attr(wp_create_nonce('simpletags_admin')); ?>" />

										<p>
											<label
												for="renameterm_old"><?php _e('Term(s) to rename:', 'simple-tags'); ?></label>
											<br />
											<textarea type="text" class="autocomplete-input tag-cloud-input taxopress-expandable-textarea"
												id="renameterm_old" name="renameterm_old" size="80" /></textarea>
										</p>

										<p>
											<label
												for="renameterm_new"><?php _e('New term name(s):', 'simple-tags'); ?></label>
												<br />
												<textarea type="text" class="autocomplete-input taxopress-expandable-textarea" id="renameterm_new"
													name="renameterm_new" size="80" /></textarea>
										</p>

										<input class="button-primary" type="submit" name="rename"
											value="<?php _e('Rename', 'simple-tags'); ?>" />
									</form>
								</fieldset>
							</td>
						</tr>

						<tr valign="top" style="display:none;" class="auto-terms-content st-merge-terms">
							<td>
								<h2><?php _e('Merge Terms', 'simple-tags'); ?>
								</h2>
								<p><?php esc_html_e('This feature will delete existing terms and replace them with another term. If you want to merge term “A” into term “B”, put “A” in the first box and “B” in the second box.', 'simple-tags'); ?>
								</p>


								<fieldset>
									<form action="" method="post">
										<input type="hidden" name="taxo"
											value="<?php echo esc_attr(SimpleTags_Admin::$taxonomy); ?>" />
										<input type="hidden" name="cpt"
											value="<?php echo esc_attr(SimpleTags_Admin::$post_type); ?>" />

										<input type="hidden" name="term_action" value="mergeterm" />
										<input type="hidden" name="term_nonce"
											value="<?php echo esc_attr(wp_create_nonce('simpletags_admin')); ?>" />

										<p>
											<label
												for="renameterm_old"><?php _e('Term(s) to merge. These terms will be deleted', 'simple-tags'); ?></label>
											<br />
											<textarea type="text" class="autocomplete-input tag-cloud-input taxopress-expandable-textarea"
												id="mergeterm_old" name="renameterm_old" size="80" /></textarea>
										</p>

										<p>
											<label
												for="renameterm_new"><?php _e('New term. Any posts assigned to the old terms will be re-assigned to this term.', 'simple-tags'); ?></label>
												<br />
												<textarea type="text" class="autocomplete-input taxopress-expandable-textarea" id="renameterm_new"
													name="renameterm_new" size="80" /></textarea>
										</p>

										<input class="button-primary" type="submit" name="merge"
											value="<?php _e('Merge', 'simple-tags'); ?>" />
									</form>
								</fieldset>
							</td>
						</tr>

						<tr valign="top" style="display:none;" class="auto-terms-content st-delete-unuused-terms">
							<td>
								<h2><?php esc_html_e('Remove rarely used terms', 'simple-tags'); ?>
								</h2>
								<p><?php esc_html_e('This feature allows you to remove rarely used terms.', 'simple-tags'); ?>
								</p>

								<p><?php printf(esc_html__('If you choose 5, Taxopress will delete all terms attached to less than 5 %s.', 'simple-tags'), esc_html(SimpleTags_Admin::$post_type_name)); ?>
								</p>

								<fieldset>
									<form action="" method="post">
										<input type="hidden" name="taxo"
											value="<?php echo esc_attr(SimpleTags_Admin::$taxonomy); ?>" />
										<input type="hidden" name="cpt"
											value="<?php echo esc_attr(SimpleTags_Admin::$post_type); ?>" />

										<input type="hidden" name="term_action" value="remove-rarelyterms" />
										<input type="hidden" name="term_nonce"
											value="<?php echo esc_attr(wp_create_nonce('simpletags_admin')); ?>" />

										<p>
											<label
												for="number-delete"><?php _e('Minimum number of uses for each term:', 'simple-tags'); ?></label>
											<br />
											<select name="number-rarely" id="number-delete">
												<?php for ($i = 1; $i <= 100; $i ++) : ?>
												<option
													value="<?php echo esc_attr($i); ?>">
													<?php echo esc_html($i); ?>
												</option>
												<?php endfor; ?>
											</select>
										</p>

										<input class="button-primary" type="submit" name="Delete"
											value="<?php esc_attr_e('Delete rarely used', 'simple-tags'); ?>" />
									</form>
								</fieldset>
							</td>
						</tr>

						<?php /*
                <tr valign="top">
                    <th scope="row"><strong><?php _e('Edit Term Slug', 'simple-tags'); ?></strong></th>
                    <td>
                        <p><?php _e('Enter the term name to edit and its new slug. <a href="http://codex.wordpress.org/Glossary#Slug">Slug definition</a>', 'simple-tags'); ?></p>

                        <fieldset>
                            <form action="" method="post">
                                <input type="hidden" name="taxo" value="<?php echo esc_attr(SimpleTags_Admin::$taxonomy); ?>" />
                                <input type="hidden" name="cpt" value="<?php echo esc_attr(SimpleTags_Admin::$post_type); ?>" />

                                <input type="hidden" name="term_action" value="editslug" />
                                <input type="hidden" name="term_nonce" value="<?php echo wp_create_nonce('simpletags_admin'); ?>" />

                                <p>
                                    <label for="tagname_match"><?php _e('Term(s) to match:', 'simple-tags'); ?></label>
                                    <br />
                                    <input type="text" class="autocomplete-input" id="tagname_match" name="tagname_match" size="80" />
                                </p>

                                <p>
                                    <label for="tagslug_new"><?php _e('Slug(s) to set:', 'simple-tags'); ?></label>
                                    <br />
                                    <input type="text" class="autocomplete-input" id="tagslug_new" name="tagslug_new" size="80" />
                                </p>

                                <input class="button-primary" type="submit" name="edit" value="<?php _e('Edit', 'simple-tags'); ?>" />
                            </form>
                        </fieldset>
                    </td>
                </tr>
                */
                ?>

					</table>



				</div>
			</div>


			<div class="clear"></div>

		</div>
</div>
<div class="taxopress-right-sidebar admin-settings-sidebar">
	<?php do_action('taxopress_admin_after_sidebar'); ?>
</div>

</div>


	<?php SimpleTags_Admin::printAdminFooter(); ?>



<?php
        do_action('simpletags-manage_terms', SimpleTags_Admin::$taxonomy);
    }

    /**
     * Method to merge tags
     *
     * @param string $taxonomy
     * @param string $old
     * @param string $new
     *
     * @return boolean
     * @author olatechpro
     */
    public static function mergeTerms($taxonomy = 'post_tag', $old = '', $new = '')
    {
        if (trim(str_replace(',', '', stripslashes($new))) == '') {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No new term specified!', 'simple-tags'), 'error');

            return false;
        }

        // String to array
        $old_terms = explode(',', $old);
        $new_terms = explode(',', $new);

        // Remove empty element and trim
        $old_terms = array_filter($old_terms, '_delete_empty_element');
        $new_terms = array_filter($new_terms, '_delete_empty_element');
        $common_elements = array_intersect($old_terms, $new_terms);

        // If old/new tag are empty => exit !
        if (empty($old_terms) || empty($new_terms)) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No new/old valid term specified!', 'simple-tags'), 'error');

            return false;
        }

        if (!empty($common_elements)) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('Term to merge and New Term must not contain same term.', 'simple-tags'), 'error');

            return false;
        }

        $counter = 0;
        if (count($new_terms) == 1) { // Merge
            // Set new tag
            $new_tag = sanitize_text_field($new_terms[0]);
            if (empty($new_tag)) {
                add_settings_error(__CLASS__, __CLASS__, esc_html__('No valid new term.', 'simple-tags'), 'error');

                return false;
            }

            // Get terms ID from old terms names
            $terms_id = array();
            foreach ((array) $old_terms as $old_tag) {
                $term       = get_term_by('name', addslashes(sanitize_text_field($old_tag)), $taxonomy);
                $terms_id[] = (int) $term->term_id;
            }

            // Get objects from terms ID
            $objects_id = get_objects_in_term($terms_id, $taxonomy, array( 'fields' => 'all_with_object_id' ));

            // No objects ? exit !
            if (! $objects_id) {

                // Delete old terms
                foreach ((array) $terms_id as $term_id) {
                    wp_delete_term($term_id, $taxonomy);
                }

                add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Merge term(s) "%1$s" to "%2$s". %3$s posts edited.', 'simple-tags'), rtrim($old, ','), rtrim($new, ','), $counter), 'updated');
                return true;
            }

            // Set objects to new term ! (Append no replace)
            foreach ((array) $objects_id as $object_id) {
                wp_set_object_terms($object_id, $new_tag, $taxonomy, true);
                $counter ++;
            }

            // Delete old terms
            foreach ((array) $terms_id as $term_id) {
                wp_delete_term($term_id, $taxonomy);
            }

            // Test if term is also a category
            /*
            if ( is_term($new_tag, 'category') ) {
                // Edit the slug to use the new term
                self::editTermSlug( $new_tag, sanitize_title($new_tag) );
            }
            */

            // Clean cache
            clean_object_term_cache($objects_id, $taxonomy);
            clean_term_cache($terms_id, $taxonomy);

            if ($counter == 0) {
                add_settings_error(__CLASS__, __CLASS__, esc_html__('No term merged.', 'simple-tags'), 'updated');
            } else {
                add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Merge term(s) "%1$s" to "%2$s". %3$s posts edited.', 'simple-tags'), rtrim($old, ','), rtrim($new, ','), $counter), 'updated');
            }
        } else { // Error
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Error. You need to enter a single term to merge to in new term name !', 'simple-tags'), $old), 'error');
        }

        return true;
    }

    /**
     * Method for remove tags
     *
     * @param string $taxonomy
     * @param string $post_type
     * @param string $tag
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    public static function removeTerms($taxonomy = 'post_tag', $post_type = 'posts', $new = '')
    {
        if (trim(str_replace(',', '', stripslashes($new))) == '') {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No term specified!', 'simple-tags'), 'error');

            return false;
        }

        // String to array
        $new_terms = explode(',', $new);

        // Remove empty element and trim
        $new_terms = array_filter($new_terms, '_delete_empty_element');

        // If new tag are empty => exit !
        if (empty($new_terms)) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No valid term specified!', 'simple-tags'), 'error');

            return false;
        }

        $counter = 0;
        if (count($new_terms) > 0) {
            foreach ((array) $new_terms as $term) {
                $term = get_term_by('name', sanitize_text_field($term), $taxonomy);
                if (empty($term) || !is_object($term)) {
                    continue;
                }

                $term = $term->term_id;

                $args = array(
                'post_type' => $post_type, // post_type
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => $taxonomy,
                        'field' => 'id',
                        'terms' => $term
                    )
                )
            );
                $posts = get_posts($args);
                foreach ($posts as $post) {
                    $remove = wp_remove_object_terms($post->ID, $term, $taxonomy);
                    if ($remove) {
                        clean_object_term_cache($post->ID, $taxonomy);
                        clean_term_cache($term, $taxonomy);
                        $counter ++;
                    }
                }
            }

            if ($counter == 0) {
                add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('This term is not associated with any %1$s.', 'simple-tags'), SimpleTags_Admin::$post_type_name), 'error');
            } else {
                add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Removed term(s) "%1$s" from %2$s %3$s', 'simple-tags'), $new, $counter, SimpleTags_Admin::$post_type_name), 'updated');
            }
        } else { // Error
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Error. No enough terms specified.', 'simple-tags'), $old), 'error');
        }

        return true;
    }

    /**
     * Method for rename tags
     *
     * @param string $taxonomy
     * @param string $old
     * @param string $new
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    public static function renameTerms($taxonomy = 'post_tag', $old = '', $new = '')
    {
        if (trim(str_replace(',', '', stripslashes($new))) == '') {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No new term specified!', 'simple-tags'), 'error');

            return false;
        }

        // String to array
        $old_terms = explode(',', $old);
        $new_terms = explode(',', $new);

        // Remove empty element and trim
        $old_terms = array_filter($old_terms, '_delete_empty_element');
        $new_terms = array_filter($new_terms, '_delete_empty_element');

        // If old/new tag are empty => exit !
        if (empty($old_terms) || empty($new_terms)) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No new/old valid term specified!', 'simple-tags'), 'error');

            return false;
        }

        $counter = 0;
        if (count($old_terms) === count($new_terms)) { // Rename only
            foreach ((array) $old_terms as $i => $old_tag) {
                $new_name = sanitize_text_field($new_terms[ $i ]);

                // Get term by name
                $term = get_term_by('name', sanitize_text_field($old_tag), $taxonomy);
                if (! $term) {
                    continue;
                }

                // Get objects from term ID
                $objects_id = get_objects_in_term($term->term_id, $taxonomy, array( 'fields' => 'all_with_object_id' ));

                // Create the new term
                if (! $term_info = term_exists($new_name, $taxonomy)) {
                    $term_info = wp_insert_term($new_name, $taxonomy);
                }

                // If default category, update the ID for new term...
                if ('category' == $taxonomy && $term->term_id == get_option('default_category')) {
                    update_option('default_category', $term_info['term_id']);
                    clean_term_cache($term_info['term_id'], $taxonomy);
                }

                // Delete old term
                wp_delete_term($term->term_id, $taxonomy);

                // Set objects to new term ! (Append no replace)
                foreach ((array) $objects_id as $object_id) {
                    wp_set_object_terms($object_id, $new_name, $taxonomy, true);
                }

                // Clean cache
                clean_object_term_cache($objects_id, $taxonomy);
                clean_term_cache($term->term_id, $taxonomy);

                // Increment
                $counter ++;
            }

            if ($counter == 0) {
                add_settings_error(__CLASS__, __CLASS__, esc_html__('No term renamed.', 'simple-tags'), 'updated');
            } else {
                add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Renamed term(s) "%1$s" to "%2$s"', 'simple-tags'), rtrim($old, ','), rtrim($new, ',')), 'updated');
            }
        } else { // Error
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Error. No enough terms for rename.', 'simple-tags'), $old), 'error');
        }

        return true;
    }

    /**
     * Method for delete a list of terms
     *
     * @param string $taxonomy
     * @param string $delete
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    public static function deleteTermsByTermList($taxonomy = 'post_tag', $delete = '')
    {
        if (trim(str_replace(',', '', stripslashes($delete))) == '') {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No term specified!', 'simple-tags'), 'error');

            return false;
        }

        // In array + filter
        $delete_terms = explode(',', $delete);
        $delete_terms = array_filter($delete_terms, '_delete_empty_element');

        // Delete tags
        $counter = 0;
        foreach ((array) $delete_terms as $term) {
            $term    = get_term_by('name', sanitize_text_field($term), $taxonomy);
            $term_id = (int) $term->term_id;

            if ($term_id != 0) {
                wp_delete_term($term_id, $taxonomy);
                clean_term_cache($term_id, $taxonomy);
                $counter ++;
            }
        }

        if ($counter == 0) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No term deleted.', 'simple-tags'), 'updated');
        } else {
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('%1s term(s) deleted.', 'simple-tags'), $counter), 'updated');
        }

        return true;
    }

    /**
     * Method for add terms for all or specified posts
     *
     * @param string $taxonomy
     * @param string $match
     * @param string $new
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    public static function addMatchTerms($taxonomy = 'post_tag', $match = '', $new = '')
    {
        if (trim(str_replace(',', '', stripslashes($new))) == '') {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No new term(s) specified!', 'simple-tags'), 'error');

            return false;
        }

        $match_terms = explode(',', $match);
        $new_terms   = explode(',', $new);

        $match_terms = array_filter($match_terms, '_delete_empty_element');
        $new_terms   = array_filter($new_terms, '_delete_empty_element');

        $counter = 0;
        if (! empty($match_terms)) { // Match and add
            // Get terms ID from old match names
            $terms_id = array();
            foreach ((array) $match_terms as $match_term) {
                $term       = get_term_by('name', sanitize_text_field($match_term), $taxonomy);
                $terms_id[] = (int) $term->term_id;
            }

            // Get object ID with terms ID
            $objects_id = get_objects_in_term($terms_id, $taxonomy, array( 'fields' => 'all_with_object_id' ));

            // Add new tags for specified post
            foreach ((array) $objects_id as $object_id) {
                wp_set_object_terms($object_id, $new_terms, $taxonomy, true); // Append terms
                $counter ++;
            }

            // Clean cache
            clean_object_term_cache($objects_id, $taxonomy);
            clean_term_cache($terms_id, $taxonomy);
        } else { // Add for all posts
            // Page or not ?
            $post_type_sql = "(post_status = 'publish' OR post_status = 'inherit') AND post_type = '".SimpleTags_Admin::$post_type."'";

            // Get all posts ID
            global $wpdb;
            $objects_id = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE {$post_type_sql}");

            // Add new tags for all posts
            foreach ((array) $objects_id as $object_id) {
                wp_set_object_terms($object_id, $new_terms, $taxonomy, true); // Append terms
                clean_object_term_cache($object_id, $taxonomy);
                clean_term_cache($new_terms, $taxonomy);
                $counter ++;
            }

            // Clean cache
            clean_object_term_cache($objects_id, $taxonomy);
        }

        if ($counter == 0) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No term added.', 'simple-tags'), 'updated');
        } else {
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('Term(s) added to %1s %2s.', 'simple-tags'), $counter, SimpleTags_Admin::$post_type_name), 'updated');
        }

        return true;
    }

    /**
     * Delete terms when counter if inferior to a specific number
     *
     * @param string $taxonomy
     * @param integer $number
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    public static function removeRarelyUsed($taxonomy = 'post_tag', $number = 0)
    {
        global $wpdb;

        if ((int) $number > 100) {
            wp_die('Tcheater ?');
        }

        // Get terms with counter inferior to...
        $terms_id = $wpdb->get_col($wpdb->prepare("SELECT term_id FROM $wpdb->term_taxonomy WHERE taxonomy = %s AND count < %d", $taxonomy, (int) $number));

        // Delete terms
        $counter = 0;
        foreach ((array) $terms_id as $term_id) {
            if ($term_id != 0) {
                wp_delete_term($term_id, $taxonomy);
                clean_term_cache($term_id, $taxonomy);
                $counter ++;
            }
        }

        if ($counter == 0) {
            add_settings_error(__CLASS__, __CLASS__, esc_html__('No term deleted.', 'simple-tags'), 'updated');
        } else {
            add_settings_error(__CLASS__, __CLASS__, sprintf(esc_html__('%1s term(s) deleted.', 'simple-tags'), $counter), 'updated');
        }

        return true;
    }

    /**
     * Method for edit one or more terms slug
     *
     * @param string $taxonomy
     * @param string $names
     * @param string $slugs
     *
     * @return boolean
     * @author WebFactory Ltd
     */
    /*
    public static function editTermSlug( $taxonomy = 'post_tag', $names = '', $slugs = '') {
        if ( trim( str_replace(',', '', stripslashes($slugs)) ) == '' ) {
            add_settings_error( __CLASS__, __CLASS__, esc_html__('No new slug(s) specified!', 'simple-tags'), 'error' );
            return false;
        }

        $match_names = explode(',', $names);
        $new_slugs = explode(',', $slugs);

        $match_names = array_filter($match_names, '_delete_empty_element');
        $new_slugs = array_filter($new_slugs, '_delete_empty_element');

        if ( count($match_names) != count($new_slugs) ) {
            add_settings_error( __CLASS__, __CLASS__, esc_html__('Terms number and slugs number isn\'t the same!', 'simple-tags'), 'error' );
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
            add_settings_error( __CLASS__, __CLASS__, esc_html__('No slug edited.', 'simple-tags'), 'updated' );
        } else {
            add_settings_error( __CLASS__, __CLASS__, sprintf(esc_html__('%s slug(s) edited.', 'simple-tags'), $counter), 'updated' );
        }

        return true;
    }
    */


    /** Singleton instance */
    public static function get_instance()
    {
        if (! isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }
}
?>