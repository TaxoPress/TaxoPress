<?php

class SimpleTags_Plugin {

	public static $options = null;

	/**
	 * Add initial ST options in DB, init roles/permissions
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function activation() {
		// Put default options
		$options_from_table = get_option( STAGS_OPTIONS_NAME );
		if ( empty( $options_from_table ) ) {
			add_option( STAGS_OPTIONS_NAME, self::load_default_option() );
		}

		// Init roles
		if ( function_exists( 'get_role' ) ) {
			$role = get_role( 'administrator' );
			if ( null !== $role && ! $role->has_cap( 'simple_tags' ) ) {
				$role->add_cap( 'simple_tags' );
			}

			if ( null !== $role && ! $role->has_cap( 'admin_simple_tags' ) ) {
				$role->add_cap( 'admin_simple_tags' );
			}

			$role = get_role( 'editor' );
			if ( null !== $role && ! $role->has_cap( 'simple_tags' ) ) {
				$role->add_cap( 'simple_tags' );
			}
		}
	}

	/**
	 * Do nothing :)
	 */
	public static function deactivation() {
	}

	/**
	 * Load default option from specific file
	 *
	 * @return array
	 */
	private static function load_default_option() {
		return (array) include STAGS_DIR . '/inc/helper.options.default.php';
	}

	/**
	 * Load plugin option, combine DB options with default
	 */
	private static function load_option() {
		$saved_option = wp_parse_args( (array) get_option( STAGS_OPTIONS_NAME ), self::load_default_option() );
		
		
		if (($key = array_search('datatxt_access_token', $saved_option)) !== FALSE) {
			if($saved_option['datatxt_access_token'] == '0'){
				$saved_option['datatxt_access_token'] = '';
			}
		}
		if (($key = array_search('datatxt_min_confidence', $saved_option)) !== FALSE) {
			if($saved_option['datatxt_min_confidence'] == '0'){
				$saved_option['datatxt_min_confidence'] = '0.6';
			}
		}
		self::$options = $saved_option;
	}

	/*
	 * Get all options into an array
	 *
	 * @return array
	 */
	public static function get_option() {
		if ( null === self::$options ) {
			self::load_option();
		}

		return self::$options;
	}

	/**
	 * Get one option value from all options
	 *
	 * @param string $key
	 *
	 * @return bool|mixed
	 */
	public static function get_option_value( $key = '' ) {
		if ( null === self::$options ) {
			self::load_option();
		}

		return isset( self::$options[ $key ] ) ? self::$options[ $key ] : false;
	}

	/**
	 * Update one option value from all options
	 *
	 * @param string $key
	 * @param string $value
	 * @param bool $auto_update
	 */
	public static function set_option_value( $key = '', $value = '', $auto_update = true ) {
		if ( null === self::$options ) {
			self::load_option();
		}

		if ( isset( self::$options[ $key ] ) ) {
			self::$options[ $key ] = $value;

			if ( true === $auto_update ) {
				self::update_option();
			}
		}
	}

	/**
	 * Update all options
	 *
	 * @param $value
	 * @param bool $auto_update
	 */
	public static function set_option( $value, $auto_update = true ) {
		self::$options = $value;

		if ( true === $auto_update ) {
			self::update_option();
		}
	}

	/**
	 * Set default option into DB
	 */
	public static function set_default_option() {
		self::$options = self::load_default_option();
		self::update_option();
	}

	/**
	 * Update options into DB
	 *
	 * @return bool
	 */
	public static function update_option() {
		if ( null === self::$options ) {
			self::load_option();
		}

		// In case fields value < 0 override the value of fields.
		if (isset(self::$options['auto_link_min']) && self::$options['auto_link_min'] < 0) {
			self::$options['auto_link_min'] = 1;
		}

		if (isset(self::$options['auto_link_max_by_post']) && self::$options['auto_link_max_by_post'] < 0) {
			self::$options['auto_link_max_by_post'] = 10;
		}

		if (isset(self::$options['auto_link_max_by_tag']) && self::$options['auto_link_max_by_tag'] < 0) {
			self::$options['auto_link_max_by_tag'] = 1;
		}

		return update_option( STAGS_OPTIONS_NAME, self::$options );
	}
}
