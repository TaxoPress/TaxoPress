<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SimpleTags_Client_Tracking {

	/**
	 * Register hooks
	 *
	 * SimpleTags_Client_Tracking constructor.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'schedule_send' ) );
		add_action( 'simpletags_usage_tracking_cron', array( $this, 'send_checkin' ) );
		add_action( 'simpletags_settings_save_general_end', array( $this, 'check_for_settings_optin' ) );

		add_action( 'admin_head', array( $this, 'check_for_optin' ) );
		add_action( 'admin_head', array( $this, 'check_for_optout' ) );
	}

	/**
	 * Check on DB if tracking is allowed
	 *
	 * @return bool
	 */
	private function tracking_allowed() {
		return 1 === (int) SimpleTags_Plugin::get_option_value( 'use_tracking' );
	}

	/**
	 * Schedule a webservice call one time by week
	 */
	public function schedule_send() {
		if ( ! wp_next_scheduled( 'simpletags_usage_tracking_cron' ) ) {
			$tracking             = array();
			$tracking['day']      = wp_rand( 0, 6 );
			$tracking['hour']     = wp_rand( 0, 23 );
			$tracking['minute']   = wp_rand( 0, 59 );
			$tracking['second']   = wp_rand( 0, 59 );
			$tracking['offset']   = ( $tracking['day'] * DAY_IN_SECONDS ) +
									( $tracking['hour'] * HOUR_IN_SECONDS ) +
									( $tracking['minute'] * MINUTE_IN_SECONDS ) +
									$tracking['second'];
			$tracking['initsend'] = strtotime( 'next sunday' ) + $tracking['offset'];

			wp_schedule_event( $tracking['initsend'], 'weekly', 'simpletags_usage_tracking_cron' );
			update_option( 'simpletags_usage_tracking_config', $tracking );
		}
	}

	/**
	 * Call webservice, max one time by week
	 *
	 * @param bool $override
	 * @param bool $ignore_last_checkin
	 *
	 * @return bool
	 */
	public function send_checkin( $override = false, $ignore_last_checkin = false ) {
		if ( ! $this->tracking_allowed() && false === $override ) {
			return false;
		}

		// Send a maximum of once per week
		$last_send = get_option( 'simpletags_usage_tracking_last_checkin' );
		if ( is_numeric( $last_send ) && $last_send > strtotime( '-1 week' ) && ! $ignore_last_checkin ) {
			return false;
		}

		wp_remote_post(
			'https://simpletagsforwp.com/tracking.php',
			array(
				'method'      => 'POST',
				'timeout'     => 5,
				'redirection' => 5,
				'httpversion' => '1.1',
				'blocking'    => false,
				'body'        => $this->get_data(),
				'user-agent'  => 'ST/' . STAGS_VERSION . '; ' . get_bloginfo( 'url' ),
			)
		);

		// If we have completed successfully, recheck in 1 week
		update_option( 'simpletags_usage_tracking_last_checkin', time() );

		return true;
	}

	/**
	 * Force a webservice call after option update
	 */
	public function check_for_settings_optin() {
		if ( ! current_user_can( 'admin_simple_tags' ) ) {
			return;
		}

		// Send an check in on settings save
		$force_tracking = isset( $_POST['use_tracking'] ) ? true : false;
		if ( $force_tracking ) {
			$this->send_checkin( true, true );
		}
	}

	/**
	 * Check enable action for disable tracking
	 */
	public function check_for_optin() {
		if ( ! ( ! empty( $_REQUEST['st_action'] ) && 'opt_into_tracking' === $_REQUEST['st_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'admin_simple_tags' ) ) {
			return;
		}

		SimpleTags_Plugin::set_option_value( 'use_tracking', 1, true );
		$this->send_checkin( true, true );

		update_option( 'simpletags_tracking_notice', 1 );
	}

	/**
	 * Check admin action for disable tracking
	 */
	public function check_for_optout() {
		if ( ! ( ! empty( $_REQUEST['st_action'] ) && 'opt_out_of_tracking' === $_REQUEST['st_action'] ) ) {
			return;
		}

		if ( ! current_user_can( 'admin_simple_tags' ) ) {
			return;
		}

		SimpleTags_Plugin::set_option_value( 'use_tracking', 0, true );
		update_option( 'simpletags_tracking_notice', 1 );
	}

	/**
	 * Get some data from WP installation
	 *
	 * @return array
	 */
	public function get_data() {
		$data = array();

		// Retrieve current theme info
		$theme_data = wp_get_theme();

		$count_b = 1;
		if ( is_multisite() ) {
			if ( function_exists( 'get_blog_count' ) ) {
				$count_b = get_blog_count();
			} else {
				$count_b = 'Not Set';
			}
		}

		$data['php_version']    = phpversion();
		$data['mi_version']     = STAGS_VERSION;
		$data['wp_version']     = get_bloginfo( 'version' );
		$data['server']         = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$data['multisite']      = is_multisite();
		$data['url']            = home_url();
		$data['themename']      = $theme_data->get( 'Name' );
		$data['themeversion']   = $theme_data->get( 'Version' );
		$data['email']          = get_bloginfo( 'admin_email' );
		$data['settings']       = $this->simpletags_get_options();
		$data['sites']          = $count_b;
		$data['timezoneoffset'] = date( 'Y-m-d H:i:s' );

		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins, true ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $key ] );
			}
		}

		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = $plugins;
		$data['locale']           = get_locale();

		return $data;
	}

	/**
	 * Get interesting information from the plugin
	 *
	 * @return array
	 */
	public function simpletags_get_options() {
		$current_options = SimpleTags_Plugin::get_option();

		// Get only 11 first option
		$current_options = array_slice( $current_options, 0, 11 );

		if ( empty( $current_options ) || ! is_array( $current_options ) ) {
			$current_options = array();
		}

		return $current_options;
	}
}
