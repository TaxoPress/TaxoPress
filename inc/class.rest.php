<?php

class SimpleTags_Rest {
	/**
	 * Register hooks
	 *
	 * SimpleTags_Rest constructor.
	 */
	public function __construct() {
		if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'use_autocompletion' ) ) {
			add_filter( 'rest_prepare_taxonomy', array( __CLASS__, 'rest_prepare_taxonomy' ), 10, 3 );
		}
	}

	/**
	 * Hack REST API for Gutenberg usage for hide and deactive core features
	 *
	 * @param WP_REST_Response $response
	 * @param stdClass $taxonomy
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public static function rest_prepare_taxonomy( $response, $taxonomy, $request ) {
		if ( isset( $taxonomy->name ) && 'post_tag' === $taxonomy->name && 'edit' === $request->get_param( 'context' ) ) {
			$response->data['visibility']['show_ui'] = false;
		}

		return $response;
	}
}
