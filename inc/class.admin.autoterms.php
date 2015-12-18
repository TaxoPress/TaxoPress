<?php

class SimpleTags_Admin_AutoTags {
	// Build admin URL
	static $tools_base_url = '';

	/**
	 *
	 */
	public function __construct() {
		self::$tools_base_url = admin_url( 'tools.php' ) . '?page=';

		// Admin menu
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );

		// Register taxo, parent method...
		SimpleTags_Admin::registerDetermineTaxonomy();
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_menu() {
		add_management_page( __( 'Simple Terms: Auto Terms', 'simpletags' ), __( 'Auto Terms', 'simpletags' ), 'simple_tags', 'st_auto', array(
			__CLASS__,
			'pageAutoTerms'
		) );
	}

	/**
	 * WP Page - Auto Tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function pageAutoTerms() {
		global $wpdb;

		// Get options
		$options = get_option( STAGS_OPTIONS_NAME_AUTO );
		if ( $options == false ) // First save ?
		{
			$options = array();
		}

		if ( ! isset( $options[ SimpleTags_Admin::$post_type ] ) ) { // First save for this CPT ?
			$options[ SimpleTags_Admin::$post_type ] = array();
		}

		if ( ! isset( $options[ SimpleTags_Admin::$post_type ][ SimpleTags_Admin::$taxonomy ] ) ) { // First save for this taxo ?
			$options[ SimpleTags_Admin::$post_type ][ SimpleTags_Admin::$taxonomy ] = array();
		}

		$taxo_options = $options[ SimpleTags_Admin::$post_type ][ SimpleTags_Admin::$taxonomy ]; // Edit local option taxo

		$action = false;
		if ( isset( $_POST['update_auto_list'] ) ) {
			check_admin_referer( 'update_auto_list-simpletags' );

			// Tags list
			$terms_list = stripslashes( $_POST['auto_list'] );
			$terms      = explode( ',', $terms_list );

			// Remove empty and duplicate elements
			$terms = array_filter( $terms, '_delete_empty_element' );
			$terms = array_unique( $terms );

			$taxo_options['auto_list'] = maybe_serialize( $terms );

			// Active auto terms ?
			$taxo_options['use_auto_terms'] = ( isset( $_POST['use_auto_terms'] ) && $_POST['use_auto_terms'] == '1' ) ? '1' : '0';

			// All terms ?
			$taxo_options['at_all'] = ( isset( $_POST['at_all'] ) && $_POST['at_all'] == '1' ) ? '1' : '0';

			// Empty only ?
			$taxo_options['at_empty'] = ( isset( $_POST['at_empty'] ) && $_POST['at_empty'] == '1' ) ? '1' : '0';

			// Full word ?
			$taxo_options['only_full_word'] = ( isset( $_POST['only_full_word'] ) && $_POST['only_full_word'] == '1' ) ? '1' : '0';

			// Support hashtag format ?
			$taxo_options['allow_hashtag_format'] = ( isset( $_POST['allow_hashtag_format'] ) && $_POST['allow_hashtag_format'] == '1' ) ? '1' : '0';

			$options[ SimpleTags_Admin::$post_type ][ SimpleTags_Admin::$taxonomy ] = $taxo_options;
			update_option( STAGS_OPTIONS_NAME_AUTO, $options );

			add_settings_error( __CLASS__, __CLASS__, __( 'Auto terms options updated !', 'simpletags' ), 'updated' );
		} elseif ( isset( $_GET['action'] ) && $_GET['action'] == 'auto_tag' ) {
			$action = true;
			$n      = ( isset( $_GET['n'] ) ) ? intval( $_GET['n'] ) : 0;
		}

		$terms_list = '';
		if ( isset( $taxo_options['auto_list'] ) && ! empty( $taxo_options['auto_list'] ) ) {
			$terms = maybe_unserialize( $taxo_options['auto_list'] );
			if ( is_array( $terms ) ) {
				$terms_list = implode( ', ', $terms );
			}
		}

		settings_errors( __CLASS__ );
		?>
		<div class="wrap st_wrap">
			<h2><?php _e( 'Overview', 'simpletags' ); ?>
				<p><?php _e( 'The bulb are lit when the association taxonomy and custom post type have the classification automatic activated. Otherwise, the bulb is off.', 'simpletags' ); ?>
				<table class="widefat tag fixed" cellspacing="0">
					<thead>
					<tr>
						<th scope="col" id="label"
						    class="manage-column column-name"><?php _e( 'Custom types / Taxonomies', 'simpletags' ); ?></th>
						<?php
						foreach ( get_taxonomies( array( 'show_ui' => true ), 'object' ) as $taxo ) {
							if ( empty( $taxo->labels->name ) ) {
								continue;
							}

							echo '<th scope="col">' . esc_html( $taxo->labels->name ) . '</th>';
						}
						?>
					</tr>
					</thead>
					<tfoot>
					<tr>
						<th scope="col"
						    class="manage-column column-name"><?php _e( 'Custom types / Taxonomies', 'simpletags' ); ?></th>
						<?php
						foreach ( get_taxonomies( array( 'show_ui' => true ), 'object' ) as $taxo ) {
							if ( empty( $taxo->labels->name ) ) {
								continue;
							}

							echo '<th scope="col">' . esc_html( $taxo->labels->name ) . '</th>';
						}
						?>
					</tr>
					</tfoot>

					<tbody id="the-list" class="list:taxonomies">
					<?php
					$class = 'alternate';
					$i     = 0;
					foreach ( get_post_types( array(), 'objects' ) as $post_type ) :
						if ( ! $post_type->show_ui || empty( $post_type->labels->name ) ) {
							continue;
						}

						// Get compatible taxo for current post type
						$compatible_taxonomies = get_object_taxonomies( $post_type->name );
						if ( empty( $compatible_taxonomies ) ) {
							continue;
						}


						$i ++;
						$class = ( $class == 'alternate' ) ? '' : 'alternate';
						?>
						<tr id="custom type-<?php echo $i; ?>" class="<?php echo $class; ?>">
							<th class="name column-name"><?php echo esc_html( $post_type->labels->name ); ?></th>
							<?php
							foreach ( get_taxonomies( array( 'show_ui' => true ), 'object' ) as $line_taxo ) {
								if ( empty( $line_taxo->labels->name ) ) {
									continue;
								}

								echo '<td>' . "\n";
								if ( in_array( $line_taxo->name, $compatible_taxonomies ) ) {
									if ( isset( $options[ $post_type->name ][ $line_taxo->name ] ) && isset( $options[ $post_type->name ][ $line_taxo->name ]['use_auto_terms'] ) && $options[ $post_type->name ][ $line_taxo->name ]['use_auto_terms'] == '1' ) {
										echo '<a href="' . self::$tools_base_url . 'st_auto&taxo=' . $line_taxo->name . '&cpt=' . $post_type->name . '"><img src="' . STAGS_URL . '/assets/images/lightbulb.png" alt="' . __( 'Context configured & actived.', 'simpletags' ) . '" /></a>' . "\n";
									} else {
										echo '<a href="' . self::$tools_base_url . 'st_auto&taxo=' . $line_taxo->name . '&cpt=' . $post_type->name . '"><img src="' . STAGS_URL . '/assets/images/lightbulb_off.png" alt="' . __( 'Context unconfigured.', 'simpletags' ) . '" /></a>' . "\n";
									}
								} else {
									echo '-' . "\n";
								}
								echo '</td>' . "\n";
							}
							?>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<div class="clear"></div>
		</div>

		<div class="wrap st_wrap">
			<h2><?php printf( __( 'Auto Terms for %s and %s', 'simpletags' ), '<strong>' . SimpleTags_Admin::$post_type_name . '</strong>', '<strong>' . SimpleTags_Admin::$taxo_name . '</strong>' ); ?></h2>

			<?php if ( $action === false ) : ?>

				<h3><?php _e( 'Auto terms list', 'simpletags' ); ?></h3>
				<p><?php _e( 'This feature allows Wordpress to look into post content and title for specified terms when saving posts. If your post content or title contains the word "WordPress" and you have "wordpress" in auto terms list, Simple Tags will add automatically "wordpress" as term for this post.', 'simpletags' ); ?></p>

				<h3><?php _e( 'Options', 'simpletags' ); ?></h3>
				<form
					action="<?php echo self::$tools_base_url . 'st_auto&taxo=' . SimpleTags_Admin::$taxonomy . '&cpt=' . SimpleTags_Admin::$post_type; ?>"
					method="post">
					<table class="form-table">
						<tr valign="top">
							<th scope="row"><?php _e( 'Activation', 'simpletags' ); ?></th>
							<td>
								<input type="checkbox" id="use_auto_terms" name="use_auto_terms"
								       value="1" <?php echo ( isset( $taxo_options['use_auto_terms'] ) && $taxo_options['use_auto_terms'] == 1 ) ? 'checked="checked"' : ''; ?> />
								<label for="use_auto_terms"><?php _e( 'Active Auto Tags.', 'simpletags' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Terms database', 'simpletags' ); ?></th>
							<td>
								<input type="checkbox" id="at_all" name="at_all"
								       value="1" <?php echo ( isset( $taxo_options['at_all'] ) && $taxo_options['at_all'] == 1 ) ? 'checked="checked"' : ''; ?> />
								<label
									for="at_all"><?php _e( 'Use also local terms database with auto terms. (Warning, this option can increases the CPU consumption a lot if you have many terms)', 'simpletags' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Target', 'simpletags' ); ?></th>
							<td>
								<input type="checkbox" id="at_empty" name="at_empty"
								       value="1" <?php echo ( isset( $taxo_options['at_empty'] ) && $taxo_options['at_empty'] == 1 ) ? 'checked="checked"' : ''; ?> />
								<label
									for="at_empty"><?php _e( 'Autotag only posts without terms.', 'simpletags' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Whole Word ?', 'simpletags' ); ?></th>
							<td>
								<input type="checkbox" id="only_full_word" name="only_full_word"
								       value="1" <?php echo ( isset( $taxo_options['only_full_word'] ) && $taxo_options['only_full_word'] == 1 ) ? 'checked="checked"' : ''; ?> />
								<label
									for="only_full_word"><?php _e( 'Autotag only a post when terms finded in the content are a the same name. (whole word only)', 'simpletags' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><?php _e( 'Suport Hashtag format ?', 'simpletags' ); ?></th>
							<td>
								<input type="checkbox" id="allow_hashtag_format" name="allow_hashtag_format"
								       value="1" <?php echo ( isset( $taxo_options['allow_hashtag_format'] ) && $taxo_options['allow_hashtag_format'] == 1 ) ? 'checked="checked"' : ''; ?> />
								<label
									for="allow_hashtag_format"><?php _e( 'When the whole word option is enabled, hashtag will not be autotag because of # prefix. This option allow to fixed this issue!', 'simpletags' ); ?></label>
							</td>
						</tr>
						<tr valign="top">
							<th scope="row"><label for="auto_list"><?php _e( 'Keywords list', 'simpletags' ); ?></label>
							</th>
							<td>
								<input type="text" id="auto_list" class="auto_list" name="auto_list"
								       value="<?php echo esc_attr( $terms_list ); ?>" style="width:98%;"/>
								<br/><?php _e( 'Separated with a comma', 'simpletags' ); ?>
							</td>
						</tr>
					</table>

					<p class="submit">
						<?php wp_nonce_field( 'update_auto_list-simpletags' ); ?>
						<input class="button-primary" type="submit" name="update_auto_list"
						       value="<?php _e( 'Update options &raquo;', 'simpletags' ); ?>"/>
					</p>
				</form>

				<h3><?php _e( 'Auto terms old content', 'simpletags' ); ?></h3>
				<p>
					<?php _e( 'Simple Tags can also tag all existing contents of your blog. This feature use auto terms list above-mentioned.', 'simpletags' ); ?>
				</p>
				<p class="submit">
					<a class="button-primary"
					   href="<?php echo self::$tools_base_url . 'st_auto&amp;taxo=' . SimpleTags_Admin::$taxonomy . '&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;action=auto_tag'; ?>"><?php _e( 'Auto terms all content &raquo;', 'simpletags' ); ?></a>
				</p>

			<?php else:
			// Counter
			{
			if ( $n == 0 ) {
				update_option( 'tmp_auto_terms_st', 0 );
			}

			// Get objects
			$objects = (array) $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_title, post_content FROM {$wpdb->posts} WHERE post_type = %s AND post_status = 'publish' ORDER BY ID DESC LIMIT %d, 20", SimpleTags_Admin::$post_type, $n ) );

			if ( ! empty( $objects ) ) {
			echo '<ul>';
			foreach ( $objects as $object ) {
				SimpleTags_Client_Autoterms::auto_terms_post( $object, SimpleTags_Admin::$taxonomy, $taxo_options, true );

				echo '<li>#' . $object->ID . ' ' . $object->post_title . '</li>';
				unset( $object );
			}
			echo '</ul>';
			?>
				<p><?php _e( "If your browser doesn't start loading the next page automatically click this link:", 'simpletags' ); ?>
					<a href="<?php echo self::$tools_base_url . 'st_auto&amp;taxo=' . SimpleTags_Admin::$taxonomy . '&amp;cpt=' . SimpleTags_Admin::$post_type . '&amp;action=auto_tag&amp;n=' . ( $n + 20 ); ?>"><?php _e( 'Next content', 'simpletags' ); ?></a>
				</p>
				<script type="text/javascript">
					// <![CDATA[
					function nextPage() {
						location.href = "<?php echo self::$tools_base_url.'st_auto&taxo='.SimpleTags_Admin::$taxonomy.'&cpt='.SimpleTags_Admin::$post_type.'&action=auto_tag&n='.($n + 20); ?>";
					}
					window.setTimeout('nextPage()', 300);
					// ]]>
				</script>
				<?php
			} else {
				$counter = get_option( 'tmp_auto_terms_st' );
				delete_option( 'tmp_auto_terms_st' );
				echo '<p><strong>' . sprintf( __( 'All done! %s terms added.', 'simpletags' ), $counter ) . '</strong></p>';
			}
			}

			endif;
			?>
			<p><?php _e( 'Visit the <a href="https://github.com/herewithme/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="mailto:amaury@wordpress-fr.net">ask me</a> !', 'simpletags' ); ?></p>
			<?php SimpleTags_Admin::printAdminFooter(); ?>
		</div>
		<?php
		do_action( 'simpletags-auto_terms', SimpleTags_Admin::$taxonomy );
	}

}