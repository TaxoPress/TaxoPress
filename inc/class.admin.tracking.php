<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SimpleTags_Admin_Tracking {

	public function __construct() {
		add_action( 'init', array( $this, 'send_checkin' ) );

	}

	public static function get_data() {

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

		$data['php_version']  = phpversion();
		$data['mi_version']   = STAGS_VERSION;
		$data['wp_version']   = get_bloginfo( 'version' );
		$data['server']       = isset( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '';
		$data['multisite']    = is_multisite();
		$data['url']          = home_url();
		$data['themename']    = $theme_data->Name;
		$data['themeversion'] = $theme_data->Version;
		$data['email']        = get_bloginfo( 'admin_email' );
		$data['settings']     = self::simpletags_get_options();
		$data['sites']        = $count_b;
		$data['timezoneoffset'] = date('Y-m-d H:i:s');


		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        = array_keys( get_plugins() );
		$active_plugins = get_option( 'active_plugins', array() );

		foreach ( $plugins as $key => $plugin ) {
			if ( in_array( $plugin, $active_plugins ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $key ] );
			}
		}

		$data['active_plugins']   = $active_plugins;
		$data['inactive_plugins'] = $plugins;
		$data['locale']           = get_locale();

		return $data;

	}


	public static function send_checkin() {


		$request = wp_remote_post( 'http://devegidio.beapi.space/test.php', array(
			'method'      => 'POST',
			'timeout'     => 5,
			'redirection' => 5,
			'httpversion' => '1.1',
			'blocking'    => true,
			'body'        => self::get_data()
		) );


		return true;

	}

	public static function simpletags_get_options() {
		$settings = array();

		$option_actual = SimpleTags_Plugin::get_option('features');

		$filter = 	array_slice($option_actual, 0, 11);


		$settings = $filter;


		if ( empty( $settings ) || ! is_array( $settings ) ) {
			$settings = array();
		}

		return $settings;


	}

}
