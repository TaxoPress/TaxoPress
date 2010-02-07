<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://redmine.beapi.fr/projects/show/simple-tags
Description: Extended Tagging for WordPress 2.8 and 2.9 ! Suggested Tags, Mass edit tags, Autocompletion, Tag Cloud Widgets, Related Posts, Related Tags, etc!
Version: 1.7.4.4
Author: Amaury BALMER
Author URI: http://www.herewithme.fr
Text Domain: simpletags

Copyright 2010 - Amaury BALMER (amaury@balmer.fr)

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

Todo:
	Both :
		Taxonomy supports ( 20% )
	
	Admin: 
		Click Tags Order
		HTML and esc_html for options render
	
	Client :
		- Test avec &$this, $this, et avec une fonction, test la conso memoire de wp_filter avant/apres
		- Verifier la case du remplacement par les liens
*/

define( 'STAGS_VERSION', 		'1.7.4.4' );
define( 'STAGS_OPTIONS_NAME', 	'simpletags' ); // Option name for save settings
define( 'STAGS_FOLDER', 		'simple-tags' );

// Mu-plugins or regular plugins ?
if ( is_dir( WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . STAGS_FOLDER ) ) {
	define ( 'STAGS_DIR', WPMU_PLUGIN_DIR . DIRECTORY_SEPARATOR . STAGS_FOLDER );
	define ( 'STAGS_URL', WPMU_PLUGIN_URL . '/' . STAGS_FOLDER );
} else {
	define ( 'STAGS_DIR', WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . STAGS_FOLDER );
	define ( 'STAGS_URL', WP_PLUGIN_URL . '/' . STAGS_FOLDER );
}

require( STAGS_DIR . '/inc/client.php'); 		// Client class
require( STAGS_DIR . '/inc/inc.functions.php'); // Internal functions
require( STAGS_DIR . '/inc/tpl.functions.php'); // Templates functions
require( STAGS_DIR . '/inc/widgets.php'); 		// Widgets

// Activation, uninstall
register_activation_hook(__FILE__, 'SimpleTags_Install'   );
register_uninstall_hook (__FILE__, 'SimpleTags_Uninstall' );

// Init ST
function simple_tags_init() {
	global $simple_tags;
	
	// Localization
	load_plugin_textdomain ( 'simpletags', str_replace ( ABSPATH, '', STAGS_DIR ) . '/languages', false );
	
	// Load client
	$simple_tags['client'] = new SimpleTags();
	
	// Admin and XML-RPC
	if ( is_admin() || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) && (isset($_REQUEST['code']) && $_REQUEST['code'] == get_option('wpo_croncode')) ) {
		require( STAGS_DIR . '/inc/admin.php' );
		$simple_tags['admin'] = new SimpleTagsAdmin();
	}
	
	// Register Widget
	add_action( 'widgets_init', create_function('', 'return register_widget("SimpleTags_Widget");') );
}
add_action( 'plugins_loaded', 'simple_tags_init' );
?>