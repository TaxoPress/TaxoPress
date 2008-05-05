<?php
Class SimpleTags_Admin_MassEdit {
	function SimpleTags_Admin_MassEdit() {
		if ( $_GET['page'] == 'st_mass_tags' && $this->options['use_autocompletion'] == 1 ) {
			add_action('admin_head', array(&$this, 'helperMassBCompleteJS'));
		}
	}
	
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
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
	  	<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/css/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<?php if ( 'rtl' == get_bloginfo( 'text_direction' ) ) : ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/css/bcomplete-rtl.css?ver=<?php echo $this->version; ?>" />
		<?php endif;
	}

	/**
	 * Control POST data for mass edit tags
	 *
	 * @param string $type
	 */
	function checkFormMassEdit() {
		if ( !current_user_can('simple_tags') ) {
			return false;
		}
		
		// Get GET data
		$type = stripslashes($_GET['post_type']);
		
		if ( isset($_POST['update_mass']) ) {
			// origination and intention
			if ( ! ( wp_verify_nonce($_POST['secure_mass'], 'st_mass_tags') ) ) {
				$this->message = __('Security problem. Try again. If this problem persist, contact <a href="mailto:amaury@wordpress-fr.net">plugin author</a>.', 'simpletags');
				$this->status = 'error';
				return;
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
					wp_set_object_terms( $object_id, $tags, 'post_tag' );
					$counter++;
					
					// Clean cache
					if ( 'page' == $type ) {
						clean_page_cache($object_id);
					} else {
						clean_post_cache($object_id);
					}
				}
				
				if ( $type == 'page' ) {
					$this->message = sprintf(__('%s page(s) tags updated with success !', 'simpletags'), (int) $counter);
				} else {
					$this->message = sprintf(__('%s post(s) tags updated with success !', 'simpletags'), (int) $counter);
				}
			}
		}
	}
	
	function edit_data_query( $q = false ) {
		if ( false === $q ) {
			$q = $_GET;
		}
		
		// Date
		$q['m']   = (int) $q['m'];
		
		// Category
		$q['cat'] = (int) $q['cat'];
		
		// Quantity
		$q['posts_per_page'] = (int) $q['posts_per_page'];
		if ( $q['posts_per_page'] == 0 ) {
			$q['posts_per_page'] = 15;
		}		
		
		// Content type
		if ( $q['post_type'] == 'page' ) {
			$q['post_type'] = 'page';
		} else {
			$q['post_type'] = 'post';
		}
		
		// Post status
		$post_stati = array(	//	array( adj, noun )
			'publish' => array(__('Published'), __('Published posts'), __ngettext_noop('Published (%s)', 'Published (%s)')),
			'future' => array(__('Scheduled'), __('Scheduled posts'), __ngettext_noop('Scheduled (%s)', 'Scheduled (%s)')),
			'pending' => array(__('Pending Review'), __('Pending posts'), __ngettext_noop('Pending Review (%s)', 'Pending Review (%s)')),
			'draft' => array(__('Draft'), _c('Drafts|manage posts header'), __ngettext_noop('Draft (%s)', 'Drafts (%s)')),
			'private' => array(__('Private'), __('Private posts'), __ngettext_noop('Private (%s)', 'Private (%s)')),
		);
	
		$post_stati = apply_filters('post_stati', $post_stati);	
		$avail_post_stati = get_available_post_statuses('post');
	
		$post_status_q = '';
		if ( isset($q['post_status']) && in_array( $q['post_status'], array_keys($post_stati) ) ) {
			$post_status_q = '&post_status=' . $q['post_status'];
			$post_status_q .= '&perm=readable';
		}
	
		if ( 'pending' === $q['post_status'] ) {
			$order = 'ASC';
			$orderby = 'modified';
		} elseif ( 'draft' === $q['post_status'] ) {
			$order = 'DESC';
			$orderby = 'modified';
		} else {
			$order = 'DESC';
			$orderby = 'date';
		}
	
		wp("post_type={$q['post_type']}&what_to_show=posts$post_status_q&posts_per_page={$q['posts_per_page']}&order=$order&orderby=$orderby");
	
		return array($post_stati, $avail_post_stati);
	}

	/**
	 * WP Page - Mass edit tags
	 *
	 */
	function pageMassEditTags() {	
		global $wpdb, $wp_locale, $wp_query;		
		list($post_stati, $avail_post_stati) = $this->edit_data_query();
		
		if ( !isset( $_GET['paged'] ) ) {
			$_GET['paged'] = 1;
		}
			
		?>
		<div id="wpbody"><div class="wrap">
			<form id="posts-filter" action="" method="get">
				<input type="hidden" name="page" value="st_mass_tags" />
				<h2><?php _e('Mass edit tags', 'simpletags'); ?></h2>
							
				<ul class="subsubsub">
					<?php
					$status_links = array();
					$num_posts = wp_count_posts('post', 'readable');
					$class = (empty($_GET['post_status']) && empty($_GET['post_type'])) ? ' class="current"' : '';
					$status_links[] = "<li><a href=\"edit.php?page=st_mass_tags\"$class>".__('All Posts', 'simpletags')."</a>";
					foreach ( $post_stati as $status => $label ) {
						$class = '';
					
						if ( !in_array($status, $avail_post_stati) ) {
							continue;
						}
					
						if ( empty($num_posts->$status) )
							continue;
						if ( $status == $_GET['post_status'] )
							$class = ' class="current"';
					
						$status_links[] = "<li><a href=\"edit.php?page=st_mass_tags&amp;post_status=$status\"$class>" .
						sprintf(__ngettext($label[2][0], $label[2][1], $num_posts->$status), $num_posts->$status) . '</a>';
					}
					echo implode(' |</li>', $status_links) . ' |</li>';
					unset($status_links);
					
					$class = (!empty($_GET['post_type'])) ? ' class="current"' : '';
					?>
					<li><a href="edit.php?page=st_mass_tags&amp;post_type=page" <?php echo $class; ?>><?php _e('All Pages', 'simpletags'); ?></a>
				</ul>
				
				<?php if ( isset($_GET['post_status'] ) ) : ?>
					<input type="hidden" name="post_status" value="<?php echo attribute_escape($_GET['post_status']) ?>" />
				<?php endif; ?>
				
				<p id="post-search">
					<input type="text" id="post-search-input" name="s" value="<?php the_search_query(); ?>" />
					<input type="submit" value="<?php _e( 'Search Posts', 'simpletags' ); ?>" class="button" />
				</p>
				
				<div class="tablenav">		
					<?php
					$posts_per_page = (int) $_GET['posts_per_page'];
					if ( $posts_per_page == 0 ) {
						$posts_per_page = 15;
					}
					
					$page_links = paginate_links( array(
						'base' => add_query_arg( 'paged', '%#%' ),
						'format' => '',
						'total' => ceil($wp_query->found_posts / $posts_per_page ),
						'current' => ((int) $_GET['paged'])
					));
					
					if ( $page_links )
						echo "<div class='tablenav-pages'>$page_links</div>";
					?>
					
					<div style="float: left">
						<?php 						
						if ( !is_singular() ) {
						$arc_query = "SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = 'post' ORDER BY post_date DESC";
						
						$arc_result = $wpdb->get_results( $arc_query );
						
						$month_count = count($arc_result);
						
						if ( $month_count && !( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) { ?>
							<select name='m'>
							<option<?php selected( @$_GET['m'], 0 ); ?> value='0'><?php _e('Show all dates', 'simpletags'); ?></option>
							<?php
							foreach ($arc_result as $arc_row) {
								if ( $arc_row->yyear == 0 )
									continue;
								$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );
							
								if ( $arc_row->yyear . $arc_row->mmonth == $_GET['m'] )
									$default = ' selected="selected"';
								else
									$default = '';
							
								echo "<option$default value='$arc_row->yyear$arc_row->mmonth'>";
								echo $wp_locale->get_month($arc_row->mmonth) . " $arc_row->yyear";
								echo "</option>\n";
							}
							?>
							</select>
						<?php } ?>
						
						<?php wp_dropdown_categories('show_option_all='.__('View all categories', 'simpletags').'&hide_empty=1&hierarchical=1&show_count=1&selected='.$_GET['cat']);?>
									
						<select name="posts_per_page" id="posts_per_page">							
							<option <?php if ( !isset($_GET['posts_per_page']) ) echo 'selected="selected"'; ?> value=""><?php _e('Quantity&hellip;', 'simpletags'); ?></option>
							<option <?php if ( $posts_per_page == 10 ) echo 'selected="selected"'; ?> value="10">10</option>
							<option <?php if ( $posts_per_page == 20 ) echo 'selected="selected"'; ?> value="20">20</option>
							<option <?php if ( $posts_per_page == 30 ) echo 'selected="selected"'; ?> value="30">30</option>
							<option <?php if ( $posts_per_page == 40 ) echo 'selected="selected"'; ?> value="40">40</option>
							<option <?php if ( $posts_per_page == 50 ) echo 'selected="selected"'; ?> value="50">50</option>
							<option <?php if ( $posts_per_page == 100 ) echo 'selected="selected"'; ?> value="100">100</option>
							<option <?php if ( $posts_per_page == 200 ) echo 'selected="selected"'; ?> value="200">200</option>
						</select>
						
						<input type="submit" id="post-query-submit" value="<?php _e('Filter', 'simpletags'); ?>" class="button-secondary" />
						<?php } ?>
					</div>
					
					<br style="clear:both;" />
				</div>
			</form>
			
			<br style="clear:both;" />
	
			<?php if ( have_posts() ) :
				add_filter('the_title','wp_specialchars');
				?>
				<form name="post" id="post" method="post">
					<table class="form-table">
					<?php
					while (have_posts()) {
						the_post();
						?>
						<tr valign="top">
							<th scope="row"><a href="post.php?action=edit&amp;post=<?php the_ID(); ?>" title="<?php _e('Edit', 'simpletags'); ?>"><?php the_title(); ?></a></th>
							<td><input id="tags-input<?php the_ID(); ?>" class="tags_input" type="text" size="100" name="tags[<?php the_ID(); ?>]" value="<?php echo get_tags_to_edit( get_the_ID() ); ?>" /></td>
						</tr>
						<?php					
					}
					?>
					</table>
					
					<p class="submit">
						<input class="button" type="hidden" name="secure_mass" value="<?php echo wp_create_nonce('st_mass_tags'); ?>" />
						<input class="button" type="submit" name="update_mass" value="<?php _e('Update all &raquo;', 'simpletags'); ?>" /></p>
				</form>
				<?php if ( $this->all_tags === true ) : ?>
					<script type="text/javascript">
						// <![CDATA[			
						jQuery(document).ready(function() {
							<?php
							while ( have_posts() ) { the_post(); ?>
								if ( document.getElementById('tags-input<?php the_ID(); ?>') ) {
									var tag_<?php the_ID(); ?> = new BComplete('tags-input<?php the_ID(); ?>');
									tag_<?php the_ID(); ?>.setData(collection);
								}
							<?php } ?>
						});
						// ]]>
					</script>
				<?php endif; ?>
				
			<?php else: ?>
			
				<p><?php _e('No content to edit.', 'simpletags'); ?>
				
			<?php endif; ?>
			<p><?php _e('Visit the <a href="http://code.google.com/p/simple-tags/">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags'); ?></p>
			<?php $this->printAdminFooter(); ?>
		</div></div>
    <?php
	}
}
?>