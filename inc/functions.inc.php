<?php
/**
 * Add initial ST options in DB, init roles/permissions
 *
 * @return void
 * @author Amaury Balmer
 */
function SimpleTags_Install() {
	// Put default options
	$options_from_table = get_option( STAGS_OPTIONS_NAME );
	if ( $options_from_table == false ) {
		$options = (array) include( dirname(__FILE__) . '/helper.options.default.php' );
		update_option( STAGS_OPTIONS_NAME, $options );
		unset( $options );
	}
	
	// Init roles
	if ( function_exists('get_role') ) {
		$role = get_role('administrator');
		if( $role != null && !$role->has_cap('simple_tags') ) {
			$role->add_cap('simple_tags');
		}
		if( $role != null && !$role->has_cap('admin_simple_tags') ) {
			$role->add_cap('admin_simple_tags');
		}
		
		$role = get_role('editor');
		if( $role != null && !$role->has_cap('simple_tags') ) {
			$role->add_cap('simple_tags');
		}
		// Clean var
		unset($role);
	}
}

/**
 * Remove ST options when user delete plugin (use WP API Uninstall), remove permissions from role
 *
 */
function SimpleTags_Uninstall() {
	// Delete options
	delete_option( 'STAGS_OPTIONS_NAME' );
	delete_option( 'stp_options' ); // Old options from Simple Tagging !
	delete_option( 'widget_stags_cloud' );
	
	// Init roles
	if ( function_exists('get_role') ) {
		$role = get_role('administrator');
		if( $role != null ) {
			$role->remove_cap('simple_tags');
			$role->remove_cap('admin_simple_tags');
		}
		
		$role = get_role('editor');
		if( $role != null ) {
			$role->remove_cap('simple_tags');
			$role->remove_cap('admin_simple_tags');
		}
		// Clean var
		unset($role);
	}
}

/**
 * trim and remove empty element
 *
 * @param string $element
 * @return string
 */
function _delete_empty_element( &$element ) {
	$element = stripslashes($element);
	$element = trim($element);
	if ( !empty($element) ) {
		return $element;
	}
}

// Future of WP ?
if ( !function_exists('add_filters') ) :
function add_filters($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
	if ( is_array($tags) ) {
		foreach ( (array) $tags as $tag ) {
			add_filter($tag, $function_to_add, $priority, $accepted_args);
		}
		return true;
	} else {
		return add_filter($tags, $function_to_add, $priority, $accepted_args);
	}
}
endif;

if ( !function_exists('add_actions') ) :
function add_actions($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
	return add_filters($tags, $function_to_add, $priority, $accepted_args);
}
endif;
?>