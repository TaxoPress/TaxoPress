<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://wordpress.org/extend/plugins/simple-tags
Description: Simple Tags : Extended Tagging for WordPress 2.8 ! Autocompletion, Suggested Tags, Tag Cloud Widgets, Related Posts, Mass edit tags !
Version: 1.7b1
Author: Amaury BALMER
Author URI: http://www.herewithme.fr

Copyright 2009 Amaury BALMER (balmer.amaury@gmail.com)

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
	Admin
	Client
		- Test avec &$this, $this, et avec une fonction, test la conso memoire de wp_filter avant/apres
		- Verifier la case du remplacement par les liens
*/

require(dirname(__FILE__).'/inc/base.php'); // Client class
require(dirname(__FILE__).'/inc/client.php'); // Client class
require(dirname(__FILE__).'/inc/inc.functions.php'); // Internal functions
require(dirname(__FILE__).'/inc/tpl.functions.php'); // Templates functions
require(dirname(__FILE__).'/inc/widgets.php'); // Widgets

// Init ST
function simple_tags_init() {
	global $simple_tags;
	$simple_tags['client'] = new SimpleTags();
	
	// Admin and XML-RPC
	if ( is_admin() || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ) {
		require(dirname(__FILE__).'/inc/admin.php');
		$simple_tags['admin'] = new SimpleTagsAdmin();
	}
	
	// Register Widget
	add_action( 'widgets_init', 'widget_simpletags_register', 2 );
}
add_action('plugins_loaded', 'simple_tags_init');
?>