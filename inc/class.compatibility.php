<?php

class SimpleTags_Compatibility {
	/**
	 * admin_init hook callback
	 *
	 * @since 0.1
	 */
	public static function admin_init() {
		// Not on ajax
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		// Check activation
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		trigger_error( sprintf( esc_html__('TaxoPress requires PHP version %s or greater to be activated.'), esc_html(STAGS_MIN_PHP_VERSION) ) );

		// Deactive self
		deactivate_plugins( plugin_basename( STAGS_DIR . '/simple-tags.php' ) );

		unset( $_GET['activate'] );

		add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
	}

	/**
	 * Notify the user about the incompatibility issue.
	 */
	public static function admin_notices() {
		echo '<div class="notice error is-dismissible">';
		echo '<p>' . esc_html( sprintf( esc_html__('TaxoPress require PHP version %s or greater to be activated. Your server is currently running PHP version %s.'), esc_html(STAGS_MIN_PHP_VERSION), esc_html(PHP_VERSION) ) ) . '</p>';
		echo '</div>';
	}
}
