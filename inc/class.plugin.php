<?php

class SimpleTags_Plugin {

	/**
	 * Add initial ST options in DB, init roles/permissions
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function activation() {
		// Put default options
		$options_from_table = get_option(STAGS_OPTIONS_NAME);
		if ($options_from_table == false) {
			$options = (array) include( STAGS_DIR . '/inc/helper.options.default.php' );
			add_option(STAGS_OPTIONS_NAME, $options);
			unset($options);
		}

		// Init roles
		if (function_exists('get_role')) {
			$role = get_role('administrator');
			if ($role != null && !$role->has_cap('simple_tags')) {
				$role->add_cap('simple_tags');
			}
			if ($role != null && !$role->has_cap('admin_simple_tags')) {
				$role->add_cap('admin_simple_tags');
			}

			$role = get_role('editor');
			if ($role != null && !$role->has_cap('simple_tags')) {
				$role->add_cap('simple_tags');
			}

			// Clean var
			unset($role);
		}
	}
	
	public static function deactivation() {
		
	}
}