<?php
/**
 * Add initial ST options in DB
 *
 */
function SimpleTags_Install() {
	$options_from_table = get_option( STAGS_OPTIONS_NAME );
	if ( $options_from_table == false ) {
		$options = (array) include( dirname(__FILE__) . '/default.options.php' );
		update_option( STAGS_OPTIONS_NAME, $options );
		unset( $options );
	}
}

/**
 * Remove ST options when user delete plugin (use WP API Uninstall)
 *
 */
function SimpleTags_Uninstall() {
	delete_option( STAGS_OPTIONS_NAME );
	delete_option( 'widget_stags_cloud' );
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