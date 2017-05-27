<?php
/*
Plugin Name: Simple Tags
Plugin URI: https://github.com/herewithme/simple-tags
Description: Extended Tagging for WordPress : Terms suggestion, Mass Edit Terms, Auto link Terms, Ajax Autocompletion, Click Terms, Advanced manage terms, etc.
Version: 2.4.7
Author: Amaury BALMER
Author URI: http://www.herewithme.fr
Text Domain: simpletags

Copyright 2013-2017 - Amaury BALMER (amaury@balmer.fr)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Contributors:
	- Kevin Drouvin (kevin.drouvin@gmail.com - http://inside-dev.net)
	- Martin Modler (modler@webformatik.com - http://www.webformatik.com)
	- Vladimir Kolesnikov (vladimir@extrememember.com - http://blog.sjinks.pro)

Credits Icons :
	- famfamfam - http://www.famfamfam.com/lab/icons/silk/
*/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Do a PHP version check, require 5.0 or newer
if ( version_compare( PHP_VERSION, '5.0.0', '<' ) ) {
	// Silently deactivate plugin, keeps admin usable
	if ( function_exists( 'deactivate_plugins' ) ) {
		deactivate_plugins( plugin_basename( __FILE__ ), true );
	}

	//Spit out die messages
	wp_die( sprintf( __( 'Your PHP version is too old, please upgrade to a newer version. Your version is %s, Simple Tags requires %s. Remove the plugin from WordPress plugins directory with FTP client.', 'simpletags' ), phpversion(), '5.0.0' ) );
}

define( 'STAGS_VERSION', '2.4.7' );
define( 'STAGS_OPTIONS_NAME', 'simpletags' ); // Option name for save settings
define( 'STAGS_OPTIONS_NAME_AUTO', 'simpletags-auto' ); // Option name for save settings auto terms

define ( 'STAGS_URL', plugins_url( '', __FILE__ ) );
define ( 'STAGS_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ) );

require( STAGS_DIR . '/inc/functions.inc.php' ); // Internal functions
require( STAGS_DIR . '/inc/functions.deprecated.php' ); // Deprecated functions
require( STAGS_DIR . '/inc/functions.tpl.php' );  // Templates functions

require( STAGS_DIR . '/inc/class.plugin.php' );
require( STAGS_DIR . '/inc/class.client.php' );
require( STAGS_DIR . '/inc/class.client.tagcloud.php' );
require( STAGS_DIR . '/inc/class.widgets.php' );

// Activation, uninstall
register_activation_hook( __FILE__, array( 'SimpleTags_Plugin', 'activation' ) );
register_deactivation_hook( __FILE__, array( 'SimpleTags_Plugin', 'deactivation' ) );

// Init Simple Tags
function init_simple_tags() {
	// Load client
	new SimpleTags_Client();
	new SimpleTags_Client_TagCloud();

	// Admin and XML-RPC
	if ( is_admin() ) {
		require( STAGS_DIR . '/inc/class.admin.php' );
		new SimpleTags_Admin();
	}

	add_action( 'widgets_init', create_function( '', 'return register_widget("SimpleTags_Widget");' ) );
}

add_action( 'plugins_loaded', 'init_simple_tags' );