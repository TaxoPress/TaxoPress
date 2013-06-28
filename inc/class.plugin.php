<?php
class SimpleTags_Plugin {

	static $options = null;

	/**
	 * Add initial ST options in DB, init roles/permissions
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function activation() {
		// Put default options
		$options_from_table = get_option( STAGS_OPTIONS_NAME );
		if ( $options_from_table == false ) {
			$options = (array) include( STAGS_DIR . '/inc/helper.options.default.php' );
			add_option( STAGS_OPTIONS_NAME, $options );
			unset( $options );
		}

		// Init roles
		if ( function_exists( 'get_role' ) ) {
			$role = get_role( 'administrator' );
			if ( $role != null && !$role->has_cap( 'simple_tags' ) ) {
				$role->add_cap( 'simple_tags' );
			}
			if ( $role != null && !$role->has_cap( 'admin_simple_tags' ) ) {
				$role->add_cap( 'admin_simple_tags' );
			}

			$role = get_role( 'editor' );
			if ( $role != null && !$role->has_cap( 'simple_tags' ) ) {
				$role->add_cap( 'simple_tags' );
			}

			// Clean var
			unset( $role );
		}
	}

	public static function deactivation() {
		
	}

	private static function _load_option() {
		self::$options = wp_parse_args( (array) get_option( STAGS_OPTIONS_NAME ), (array) include( STAGS_DIR . '/inc/helper.options.default.php' ) );
	}

	public static function get_option() {
		if ( self::$options === null ) {
			self::_load_option();
		}

		return self::$options;
	}

	public static function get_option_value( $key = '' ) {
		if ( self::$options === null ) {
			self::_load_option();
		}

		return isset( self::$options[$key] ) ? self::$options[$key] : false;
	}

	public static function set_option_value( $key = '', $value = '', $auto_update = true ) {
		if ( self::$options === null ) {
			self::_load_option();
		}

		if ( isset( self::$options[$key] ) ) {
			self::$options[$key] = $value;

			if ( $auto_update == true ) {
				self::update_option();
			}
		}
	}

	public static function set_option( $value, $auto_update = true ) {
		self::$options = $value;

		if ( $auto_update == true ) {
			self::update_option();
		}
	}
	
	public static function set_default_option() {
		self::$options = (array) include( STAGS_DIR . '/inc/helper.options.default.php' );
		self::update_option();
	}

	public static function update_option() {
		if ( self::$options === null ) {
			self::_load_option();
		}

		return update_option( STAGS_OPTIONS_NAME, self::$options );
	}

}