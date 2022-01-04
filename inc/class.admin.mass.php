<?php

class SimpleTags_Admin_Mass {

	const MENU_SLUG = 'st_options';

	/**
	 * SimpleTags_Admin_Mass constructor.
	 */
	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );

		// Admin menu
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// Register taxo, parent method...
		SimpleTags_Admin::register_taxonomy();
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_menu() {
		add_submenu_page(
			self::MENU_SLUG,
			__( 'TaxoPress: Mass Edit Terms', 'simple-tags' ),
			__( 'Mass Edit Terms', 'simple-tags' ),
			'simple_tags',
			'st_mass_terms',
			array(
				__CLASS__,
				'pageMassEditTags',
			)
		);
	}

	/**
	 * Control POST data for mass edit tags
	 *
	 */
	public static function admin_init() {
		if ( ! current_user_can( 'simple_tags' ) ) {
			return false;
		}

		// Get GET data
		if ( isset( $_GET['post_type'] ) ) {
			$type = stripslashes( sanitize_text_field($_GET['post_type']) );
		}

		if ( isset( $_POST['update_mass'] ) ) {
			// origination and intention
			if ( ! ( wp_verify_nonce( sanitize_text_field($_POST['secure_mass']), 'st_mass_terms' ) ) ) {
				add_settings_error( __CLASS__, __CLASS__, __( 'Security problem. Try again. If this problem persist, contact <a href="https://wordpress.org/support/plugin/simple-tags/#new-topic-0">plugin author</a>.', 'simple-tags' ), 'error' );

				return false;
			}

			if ( isset( $_POST['tags'] ) ) {
				$counter = 0;
				foreach ( (array) array_map('sanitize_text_field', $_POST['tags']) as $object_id => $tag_list ) {
					// Trim data
					$tag_list = trim( stripslashes( $tag_list ) );

					// String to array
					$tags = explode( ',', $tag_list );

					// Remove empty and trim tag
					$tags = array_filter( $tags, '_delete_empty_element' );

					// Add new tag (no append ! replace !)
					wp_set_object_terms( $object_id, $tags, SimpleTags_Admin::$taxonomy );
					$counter ++;

					// Clean cache
					clean_post_cache( $object_id );
				}

				add_settings_error( __CLASS__, __CLASS__, sprintf( __( '%1$s %2$s(s) terms updated with success !', 'simple-tags' ), (int) $counter, strtolower( SimpleTags_Admin::$post_type_name ) ), 'updated' );

				return true;
			}
		}

		return false;
	}

	/**
	 * WP Page - Mass edit tags
	 *
	 */
	public static function pageMassEditTags() {
		global $wpdb, $wp_locale, $wp_query;
		list( $post_stati, $avail_post_stati ) = self::edit_data_query();

		if ( ! isset( $_GET['paged'] ) ) {
			$_GET['paged'] = 1;
		}

		// Display message
		settings_errors( __CLASS__ );
		?>
		<div class="wrap">
			<?php SimpleTags_Admin::boxSelectorTaxonomy( 'st_mass_terms' ); ?>

			<form id="posts-filter" action="" method="get">
				<input type="hidden" name="page" value="st_mass_terms"/>
				<input type="hidden" name="taxo" value="<?php echo esc_attr( SimpleTags_Admin::$taxonomy ); ?>"/>
				<input type="hidden" name="cpt" value="<?php echo esc_attr( SimpleTags_Admin::$post_type ); ?>"/>

        <h2><?php _e( 'Mass edit terms', 'simple-tags' ); ?></h2>
      <br>

				<ul class="subsubsub">
					<?php
					$status_links   = array();
					$num_posts      = wp_count_posts( SimpleTags_Admin::$post_type, 'readable' );
					$class          = ( empty( $_GET['post_status'] ) && empty( $_GET['post_type'] ) ) ? ' class="current"' : '';
					$status_links[] = '<li><a href="' . admin_url( 'admin.php' ) . '?page=st_mass_terms&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;taxo=' . SimpleTags_Admin::$taxonomy . '"' . $class . '>' . __( 'All', 'simple-tags' ) . '</a>';
					foreach ( $post_stati as $status => $label ) {
						$class = '';

						if ( ! in_array( $status, $avail_post_stati ) ) {
							continue;
						}

						if ( empty( $num_posts->$status ) ) {
							continue;
						}
						if ( isset( $_GET['post_status'] ) && $status == $_GET['post_status'] ) {
							$class = ' class="current"';
						}

						$status_links[] = '<li><a href="' . admin_url( 'admin.php' ) . '?page=st_mass_terms&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;taxo=' . SimpleTags_Admin::$taxonomy . '&amp;post_status=' . $status . '"' . $class . '>' . sprintf( _n( $label[2][0], $label[2][1], (int) $num_posts->$status ), number_format_i18n( $num_posts->$status ) ) . '</a>';
					}
					echo implode( ' |</li>', $status_links ) . '</li>';
					unset( $status_links );

					$class = ( ! empty( $_GET['post_type'] ) ) ? ' class="current"' : '';
					?>
				</ul>

				<?php if ( isset( $_GET['post_status'] ) ) : ?>
					<input type="hidden" name="post_status" value="<?php echo esc_attr( sanitize_text_field($_GET['post_status']) ) ?>"/>
				<?php endif; ?>

				<p class="search-box">
					<input type="text" id="post-search-input" name="s" value="<?php the_search_query(); ?>"/>
					<input type="submit" value="<?php _e( 'Search', 'simple-tags' ); ?>" class="button"/>
				</p>

				<div class="tablenav">
					<?php
					$posts_per_page = ( isset( $_GET['posts_per_page'] ) ) ? (int) $_GET['posts_per_page'] : 0;
					if ( (int) $posts_per_page == 0 ) {
						$posts_per_page = 15;
					}

					$page_links = paginate_links( array(
						'base'    => add_query_arg( 'paged', '%#%' ),
						'format'  => '',
						'total'   => ceil( $wp_query->found_posts / $posts_per_page ),
						'current' => ( (int) $_GET['paged'] )
					) );

					if ( $page_links ) {
						echo "<div class='tablenav-pages'>$page_links</div>";
					}
					?>

					<div style="float: left">
						<?php
						if ( ! is_singular() ) {
							$arc_result = $wpdb->get_results( $wpdb->prepare( "SELECT DISTINCT YEAR(post_date) AS yyear, MONTH(post_date) AS mmonth FROM $wpdb->posts WHERE post_type = %s ORDER BY post_date DESC", SimpleTags_Admin::$post_type ) );

							$month_count = count( $arc_result );

							if ( ! isset( $_GET['m'] ) ) {
								$_GET['m'] = '';
							}

							if ( $month_count && ! ( 1 == $month_count && 0 == $arc_result[0]->mmonth ) ) {
								?>
								<select name='m'>
									<option <?php selected( @sanitize_text_field($_GET['m']), 0 ); ?>
										value='0'><?php _e( 'Show all dates', 'simple-tags' ); ?></option>
									<?php
									foreach ( $arc_result as $arc_row ) {
										if ( $arc_row->yyear == 0 ) {
											continue;
										}
										$arc_row->mmonth = zeroise( $arc_row->mmonth, 2 );

										if ( $arc_row->yyear . $arc_row->mmonth == $_GET['m'] ) {
											$default = ' selected="selected"';
										} else {
											$default = '';
										}

										echo "<option$default value='$arc_row->yyear$arc_row->mmonth'>";
										echo $wp_locale->get_month( $arc_row->mmonth ) . " $arc_row->yyear";
										echo "</option>\n";
									}
									?>
								</select>
								<?php
							}
							?>

							<select name="posts_per_page" id="posts_per_page">
								<option <?php if ( ! isset( $_GET['posts_per_page'] ) ) {
									echo 'selected="selected"';
								} ?> value=""><?php _e( 'Quantity&hellip;', 'simple-tags' ); ?></option>
								<option <?php selected( $posts_per_page, 10 ); ?> value="10">10</option>
								<option <?php selected( $posts_per_page, 20 ); ?> value="20">20</option>
								<option <?php selected( $posts_per_page, 30 ); ?> value="30">30</option>
								<option <?php selected( $posts_per_page, 40 ); ?> value="40">40</option>
								<option <?php selected( $posts_per_page, 50 ); ?> value="50">50</option>
								<option <?php selected( $posts_per_page, 100 ); ?> value="100">100</option>
								<option <?php selected( $posts_per_page, 200 ); ?> value="200">200</option>
							</select>

							<input type="submit" id="post-query-submit" value="<?php _e( 'Filter', 'simple-tags' ); ?>"
							       class="button-secondary"/>
						<?php } ?>
					</div>

					<br style="clear:both;"/>
				</div>
			</form>

			<br style="clear:both;"/>

			<?php if ( have_posts() ) :
				add_filter( 'the_title', 'esc_html' );
				?>
				<form name="post" id="post" method="post" class="st-mass-edit">
					<table class="widefat post fixed">
						<thead>
						<tr>
							<th class="manage-column"><?php _e( 'Post title', 'simple-tags' ); ?></th>
							<th class="manage-column"><?php printf( __( 'Terms : %s', 'simple-tags' ), esc_html( SimpleTags_Admin::$taxo_name ) ); ?></th>
						</tr>
						</thead>
						<tbody>
						<?php
						$class = 'alternate';
						while ( have_posts() ) {
							the_post();
							$class = ( $class == 'alternate' ) ? '' : 'alternate';
							?>
							<tr valign="top" class="<?php echo $class; ?>">
								<th scope="row"><a
										href="<?php echo admin_url( 'post.php?action=edit&amp;post=' . get_the_ID() ); ?>"
										title="<?php _e( 'Edit', 'simple-tags' ); ?>"><?php echo ( get_the_title() == '' ) ? the_ID() : the_title(); ?></a>
								</th>
								<td><input id="tags-input<?php the_ID(); ?>" class="autocomplete-input tags_input"
								           type="text" size="100" name="tags[<?php the_ID(); ?>]"
								           value="<?php echo SimpleTags_Admin::getTermsToEdit( SimpleTags_Admin::$taxonomy, get_the_ID() ); ?>"/>
								</td>
							</tr>
							<?php
						}
						?>
						</tbody>
					</table>

					<p class="submit">
						<input type="hidden" name="secure_mass"
						       value="<?php echo wp_create_nonce( 'st_mass_terms' ); ?>"/>
						<input class="button-primary" type="submit" name="update_mass"
						       value="<?php _e( 'Update all &raquo;', 'simple-tags' ); ?>"/>
					</p>
				</form>

			<?php else: ?>

			<p><?php _e( 'No content to edit.', 'simple-tags' ); ?>

				<?php endif; ?>

			<?php SimpleTags_Admin::printAdminFooter(); ?>
		</div>
		<?php
		do_action( 'simpletags-mass_terms', SimpleTags_Admin::$taxonomy );
	}

	/**
	 * Clone the core WP function, add the possibility to manage the post type
	 *
	 * @param bool $q
	 *
	 * @return array
	 * @author WebFactory Ltd
	 */
	public static function edit_data_query( $q = false ) {
		if ( false === $q ) {
			$q = $_GET;
		}

		// Date
		if ( isset( $q['m'] ) ) {
			$q['m'] = (int) $q['m'];
		}

		// Category
		if ( isset( $q['cat'] ) ) {
			$q['cat'] = (int) $q['cat'];
		}

		// Quantity
		$q['posts_per_page'] = ( isset( $q['posts_per_page'] ) ) ? (int) $q['posts_per_page'] : 0;
		if ( $q['posts_per_page'] == 0 ) {
			$q['posts_per_page'] = 15;
		}

		// Content type
		$q['post_type'] = SimpleTags_Admin::$post_type;

		// Post status
		$post_stati = array(    //	array( adj, noun )
			'publish' => array(
				_x( 'Published', 'post' ),
				__( 'Published posts' ),
				_n_noop( 'Published <span class="count">(%s)</span>', 'Published <span class="count">(%s)</span>' )
			),
			'future'  => array(
				_x( 'Scheduled', 'post' ),
				__( 'Scheduled posts' ),
				_n_noop( 'Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>' )
			),
			'pending' => array(
				_x( 'Pending Review', 'post' ),
				__( 'Pending posts' ),
				_n_noop( 'Pending Review <span class="count">(%s)</span>', 'Pending Review <span class="count">(%s)</span>' )
			),
			'draft'   => array(
				_x( 'Draft', 'post' ),
				_x( 'Drafts', 'manage posts header' ),
				_n_noop( 'Draft <span class="count">(%s)</span>', 'Drafts <span class="count">(%s)</span>' )
			),
			'private' => array(
				_x( 'Private', 'post' ),
				__( 'Private posts' ),
				_n_noop( 'Private <span class="count">(%s)</span>', 'Private <span class="count">(%s)</span>' )
			),
			'inherit' => array(
				_x( 'Inherit', 'post' ),
				__( 'Inherit posts' ),
				_n_noop( 'Inherit <span class="count">(%s)</span>', 'Inherit <span class="count">(%s)</span>' )
			),
		);

		$post_stati       = apply_filters( 'post_stati', $post_stati );
		$avail_post_stati = get_available_post_statuses( SimpleTags_Admin::$post_type );


        if($q['post_type'] === 'attachment'){
			$q['post_status'] = 'inherit';
        }

		$post_status_q = '';
		if ( isset( $q['post_status'] ) && in_array( $q['post_status'], array_keys( $post_stati ) ) ) {
			$post_status_q = '&post_status=' . $q['post_status'];
			$post_status_q .= '&perm=readable';
		} elseif ( ! isset( $q['post_status'] ) ) {
			$q['post_status'] = '';
		}
        
 
		if ( 'pending' === $q['post_status'] ) {
			$order   = 'ASC';
			$orderby = 'modified';
		} elseif ( 'draft' === $q['post_status'] ) {
			$order   = 'DESC';
			$orderby = 'modified';
		} else {
			$order   = 'DESC';
			$orderby = 'date';
		}

		wp( "post_type={$q['post_type']}&what_to_show=posts$post_status_q&posts_per_page={$q['posts_per_page']}&order=$order&orderby=$orderby" );

		return array( $post_stati, $avail_post_stati );
	}

}
