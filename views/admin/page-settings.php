<div class="wrap st_wrap">
	<div id="icon-themes" class="icon32"><br></div>
	<h2><?php _e('Simple Tags: Options', 'simpletags'); ?></h2>
	<h2 class="nav-tab-wrapper">
		<?php
		// Get array options/description
		$option_data = (array) include( STAGS_DIR . '/inc/helper.options.admin.php' );
		foreach ( $option_data as $key => $val ) {
			$style = '';

			// Deactive tabs if feature not actived
			if ( isset($options['active_related_posts']) && (int) $options['active_related_posts'] == 0 && $key == 'relatedposts' ) {
				$style = 'style="display:none;"';
			}

			// Deactive tabs if feature not actived
			if ( isset($options['auto_link_tags']) && (int) $options['auto_link_tags'] == 0 && $key == 'auto-links' ) {
				$style = 'style="display:none;"';
			}

			echo '<a id="'.sanitize_title ( $key ).'-tab" class="nav-tab" '.$style.' href="#'. sanitize_title ( $key ) .'">'.self::getNiceTitleOptions($key).'</a>';
		}
		?>
	</h2>
	
	<div style="float:right;margin:20px;">
		<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick" />
			<input type="hidden" name="hosted_button_id" value="L9QU9QT9R5FQS" />
			<input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donateCC_LG_global.gif" style="border:0;" name="submit" alt="PayPal - The safer, easier way to pay online." />
		</form>
	</div>

	<p><?php _e('Visit the <a href="https://github.com/herewithme/simple-tags">plugin\'s homepage</a> for further details. If you find a bug, or have a fantastic idea for this plugin, <a href="https://github.com/herewithme/simple-tags/issues">ask me</a> !', 'simpletags'); ?></p>
	<form action="<?php echo self::$admin_url; ?>" method="post">
		<?php echo self::print_options( $option_data ); ?>

		<p>
			<?php wp_nonce_field( 'updateresetoptions-simpletags' ); ?>
			<input class="button-primary" type="submit" name="updateoptions" value="<?php _e('Update options &raquo;', 'simpletags'); ?>" />
			<input class="button" type="submit" name="reset_options" onclick="return confirm('<?php _e('Do you really want to restore the default options?', 'simpletags'); ?>');" value="<?php _e('Reset Options', 'simpletags'); ?>" />
		</p>
	</form>
	
	<?php self::printAdminFooter(); ?>
</div>