<?php

//if uninstall not called from WordPress exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
}

// Delete options
delete_option( STAGS_OPTIONS_NAME ); // Options plugin
delete_option( STAGS_OPTIONS_NAME . '-version' ); // Version ST

delete_option( STAGS_OPTIONS_NAME_AUTO ); // Options auto tags
delete_option( 'tmp_auto_tags_st' ); // Autotags Temp

delete_option( 'stp_options' ); // Old options from Simple Tagging !
delete_option( 'widget_stags_cloud' ); // Widget

// remove permissions from role
if ( function_exists( 'get_role' ) ) {
	$role = get_role( 'administrator' );
	if ( $role != null ) {
		$role->remove_cap( 'simple_tags' );
		$role->remove_cap( 'admin_simple_tags' );
	}

	$role = get_role( 'editor' );
	if ( $role != null ) {
		$role->remove_cap( 'simple_tags' );
		$role->remove_cap( 'admin_simple_tags' );
	}

	// Clean var
	unset( $role );
}