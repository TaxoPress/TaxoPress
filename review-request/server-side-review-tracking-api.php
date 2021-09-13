<?php

/**
 * Plugin Name: PUM API - Reviews
 * Plugin URI: 
 * Description: An API endpoint to collect user review request results.
 * Version: 1.0.0
 * Author: danieliser
 * Author URI: https://danieliser.com
 * License: GPL2
 * 
 * To use this please include the following credit block as well as completing the following TODOS.
 *
 * Original Author: danieliser
 * Original Author URL: https://danieliser.com
 *
 * TODO Search & Replace taxopress_ with your prefix
 * TODO Search & Replace Taxopress_ with your prefix
 * TODO Search & Replace 'text-domain' with your 'text-domain'
 */

class Taxopress_API_Reviews {

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_endpoints' ) );
		add_action( 'template_redirect', array( __CLASS__, 'template_redirect' ) );
	}

	public static function register_endpoints() {
		$version   = 1;
		$namespace = 'prefix/v' . $version;

		register_rest_route( $namespace, '/review_action', array(
			'methods'  => 'POST',
			'callback' => array( __CLASS__, 'review_action_endpoint' ),
			'args'     => array(
				'uuid'          => array(
					'required'          => true,
					'description'       => __( 'Unique Identifier' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'time'          => array(
					'description'       => __( 'Timestamp' ),
					'type'              => 'string',
					'default'           => current_time( 'mysql' ),
					'sanitize_callback' => 'sanitize_text_field',
				),
				'trigger_group' => array(
					'required'          => true,
					'description'       => __( 'Trigger Group' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'trigger_code'  => array(
					'required'          => true,
					'description'       => __( 'Trigger Code' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
				'reason'        => array(
					'required'          => true,
					'description'       => __( 'Reason' ),
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				),
			),
		) );
	}

	public static function review_action_endpoint( \WP_REST_Request $request ) {

		$params = $request->get_params();

		if ( ! $params || empty( $params ) ) {
			return new WP_Error( 'missing_params', __( 'Missing Parameters.' ), array( 'status' => 404 ) );
		}

		return static::insert_record( $params );

	}

	public static function insert_record( $values = array() ) {
		global $wpdb;

		$values = shortcode_atts( array(
			'time'          => current_time( 'mysql' ),
			'trigger_group' => '',
			'trigger_code'  => '',
			'reason'        => '',
			'uuid'          => '',
		), $values );

		// Install / Upgrade table if needed.
		static::install();

		// Insert record.
		$wpdb->insert( $wpdb->taxopress_reviews, $values, array( '%s', '%s', '%s', '%s' ) );

		return $wpdb->insert_id > 0 ? 1 : - 1;
	}

	public static function install() {
		global $wpdb;

		$version         = 2;
		$current_version = get_option( '_taxopress_review_tracking_table_ver', 0 );

		$wpdb->taxopress_reviews = $wpdb->prefix . "taxopress_reviews";

		if ( $current_version < $version ) {
			$charset_collate = $wpdb->get_charset_collate();

			$sql = "CREATE TABLE $wpdb->taxopress_reviews (
				id bigint(20) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
				trigger_group varchar(255) DEFAULT '' NOT NULL,
				trigger_code varchar(255) DEFAULT '' NOT NULL,
				uuid varchar(32) DEFAULT '' NOT NULL,
				reason varchar(32) DEFAULT '' NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate;";

			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );

			update_option( '_taxopress_review_tracking_table_ver', $version );
		}
	}

	public static function template_redirect() {
		if ( ! isset( $_REQUEST['prefix-reviews'] ) ) {
			return;
		}

		$args = wp_parse_args( $_REQUEST, array(
			'time'          => current_time( 'mysql' ),
			'trigger_group' => '',
			'trigger_code'  => '',
			'reason'        => '',
			'uuid'          => '',
			'redirect'      => false,
		) );

		$tracked = static::insert_record( $args );

		if ( $args['redirect'] ) {
			wp_redirect( $args['redirect'] );
		} else {
			wp_send_json_success( $tracked );
		}
	}

}

add_action( 'plugins_loaded', array( 'Taxopress_API_Reviews', 'init' ) );
