<?php
/*
Plugin Name: Simple Tags
Plugin URI: http://wordpress.org/extend/plugins/simple-tags
Description: Simple Tags : Extended Tagging for WordPress 2.3 and upper ! Autocompletion, Suggested Tags, Tag Cloud Widgets, Related Posts, Mass edit tags !
Version: 1.5.8-2.7
Author: Amaury BALMER
Author URI: http://www.herewithme.fr

Copyright 2007 Amaury BALMER (balmer.amaury@gmail.com)

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

// Check version.
global $wp_version;
if ( strpos($wp_version, '2.5') !== false || strpos($wp_version, '2.6') !== false || strpos($wp_version, '2.7') !== false ) {
	require(dirname(__FILE__).'/2.5/simple-tags.client.php');
} elseif ( strpos($wp_version, '2.3') !== false ) {
	require(dirname(__FILE__).'/2.3/simple-tags.client.php');
} else {
	function simple_tags_warning() {
		echo '<div class="updated fade"><p><strong>'.__('Simple Tags can\'t work with this WordPress version !', 'simpletags').'</strong> '.sprintf(__('You must use <a href="%1$s">Simple Tagging Plugin</a> for it to work.', 'simpletags'), 'http://wordpress.org/extend/plugins/simple-tagging-plugin/').'</p></div>';
	}
	add_action('admin_notices', 'simple_tags_warning');
}
?>