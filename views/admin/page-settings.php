<div class="wrap st_wrap">
	<div id="icon-themes" class="icon32"><br></div>
	<h2><?php _e( 'TaxoPress: Options', 'simple-tags' ); ?></h2>

	<h2 class="nav-tab-wrapper">
		<?php
		// Get array options/description
		$option_data = (array) include( STAGS_DIR . '/inc/helper.options.admin.php' );
		foreach ( $option_data as $key => $val ) {
			$style = '';

			// Deactive tabs if feature not actived
			if ( isset( $options['active_related_posts'] ) && (int) $options['active_related_posts'] == 0 && $key == 'relatedposts' ) {
				$style = 'style="display:none;"';
			}

			// Deactive tabs if feature not actived
			if ( isset( $options['auto_link_tags'] ) && ( (int) $options['auto_link_tags'] == 0 || (int) SimpleTags_Plugin::get_option_value( 'auto_link_tags' ) === 0 )&& $key == 'auto-links' ) {
				$style = 'style="display:none;"';
			}

			echo '<a id="' . sanitize_title( $key ) . '-tab" class="nav-tab" ' . $style . ' href="#' . sanitize_title( $key ) . '">' . self::getNiceTitleOptions( $key ) . '</a>';
		}
		?>
	</h2>

	<form action="<?php echo self::$admin_url; ?>" method="post">
		<?php echo self::print_options( $option_data ); ?>

		<p>
			<?php wp_nonce_field( 'updateresetoptions-simpletags' ); ?>
			<input class="button-primary" type="submit" name="updateoptions"
			       value="<?php _e( 'Update options &raquo;', 'simple-tags' ); ?>"/>
			<input class="button" type="submit" name="reset_options"
			       onclick="return confirm('<?php _e( 'Do you really want to restore the default options?', 'simple-tags' ); ?>');"
			       value="<?php _e( 'Reset Options', 'simple-tags' ); ?>"/>
		</p>
	</form>

	<?php self::printAdminFooter(); ?>
</div>
