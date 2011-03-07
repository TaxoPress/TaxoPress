<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://redmine.beapi.fr/projects/show/simple-tags
Description: Extended Tagging for WordPress 3.1 : Suggested Tags, Mass edit tags, Auto-tags, Autocompletion, Related Posts etc. NOW Compatible custom post type and custom taxonomy !
Version: 2.0-beta9
Author: Amaury BALMER
Author URI: http://www.herewithme.fr
Text Domain: simpletags

Copyright 2007-2011 - Amaury BALMER (amaury@balmer.fr)

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

Todo:
	Both :
	Admin: 
	Client :
*/

// Do a PHP version check, require 5.0 or newer
if (version_compare(PHP_VERSION, '5.0.0', '<') ) {
	// Silently deactivate plugin, keeps admin usable
	if( function_exists('deactivate_plugins') ) {
		deactivate_plugins(plugin_basename(__FILE__), true);
	}
	
	//Spit out die messages
	wp_die(sprintf(__('Your PHP version is too old, please upgrade to a newer version. Your version is %s, Simple Tags requires %s', 'simpletags'), phpversion(), '5.0.0'));
}

define( 'STAGS_VERSION', 			'2.0-beta9' );
define( 'STAGS_OPTIONS_NAME', 		'simpletags' ); // Option name for save settings
define( 'STAGS_OPTIONS_NAME_AUTO', 	'simpletags-auto' ); // Option name for save settings auto terms
define( 'STAGS_FOLDER', 			'simple-tags' );

define ( 'STAGS_URL', plugins_url('', __FILE__) );
define ( 'STAGS_DIR', dirname(__FILE__) );

require( STAGS_DIR . '/inc/functions.inc.php'); // Internal functions
require( STAGS_DIR . '/inc/functions.deprecated.php'); // Deprecated functions
require( STAGS_DIR . '/inc/functions.tpl.php');  // Templates functions

require( STAGS_DIR . '/inc/class.client.php');
require( STAGS_DIR . '/inc/class.client.tagcloud.php');
require( STAGS_DIR . '/inc/class.widgets.php');

// Activation, uninstall
register_activation_hook( __FILE__, 'SimpleTags_Install'   );
register_uninstall_hook ( __FILE__, 'SimpleTags_Uninstall' );

// Init Simple Tags
function simple_tags_init() {
	global $simple_tags;

	// Load translations
	load_plugin_textdomain ( 'simpletags', false, basename(rtrim(dirname(__FILE__), '/')) . '/languages' );
	
	// Load client
	$simple_tags['client'] = new SimpleTags_Client();
	$simple_tags['client-cloud'] = new SimpleTags_Client_TagCloud();
	
	// Admin and XML-RPC
	if ( is_admin() ) {
		require( STAGS_DIR . '/inc/class.admin.php' );
		$simple_tags['admin'] = new SimpleTags_Admin();
	}
}
add_action( 'plugins_loaded', 'simple_tags_init' );
?>