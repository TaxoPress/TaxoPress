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
	delete_option( STAGS_OPTIONS_NAME ); // Options plugin
	delete_option( STAGS_OPTIONS_NAME . '-version' ); // Version ST
	
	delete_option( STAGS_OPTIONS_NAME_AUTO ); // Options auto tags
	delete_option( 'tmp_auto_tags_st' ); // Autotags Temp
		
	delete_option( 'stp_options' ); // Old options from Simple Tagging !
	delete_option( 'widget_stags_cloud' ); // Widget
	
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

/**
 * Test if page have tags or not...
 *
 * @return boolean
 * @author Amaury Balmer
 */
function is_page_have_tags() {
	$taxonomies = get_object_taxonomies('page');
	if ( in_array('post_tag', $taxonomies) ) {
		return true;
	}
	
	return false;
}
?>