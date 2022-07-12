<?php
/**
 * Plugin Name: TaxoPress
 * Plugin URI: https://wordpress.org/plugins/simple-tags/
 * Description: TaxoPress allows you to create and manage Tags, Categories, and all your WordPress taxonomy terms.
 * Version: 3.6.3
 * Author: TaxoPress
 * Author URI: https://taxopress.com
 * Text Domain: simple-tags
 * Domain Path: /languages
 * Min WP Version: 4.9.7
 * Requires PHP: 5.6
 * License: GPLv3
 *
 * Copyright (c) 2022 Taxopress
 *
 * ------------------------------------------------------------------------------
 * Based on Organize Series
 * Author: Darren Ethier
 * Copyright (c) 2007, 2011 Darren Ethier
 * ------------------------------------------------------------------------------
 *
 * @package 	simple-tags
 * @author		TaxoPress
 * @copyright   Copyright (C) 2007, 2011 Darren Ethier; modifications Copyright (C) 2022 TaxoPress
 * @license		GNU General Public License version 2
 * @link		https://taxoPress.com/
 */

######################################
/* 
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

Contributors to the TaxoPress code include:
    - Kevin Drouvin (kevin.drouvin@gmail.com - http://inside-dev.net)
    - Martin Modler (modler@webformatik.com - http://www.webformatik.com)
    - Vladimir Kolesnikov (vladimir@extrememember.com - http://blog.sjinks.pro)

Sections of the TaxoPress code are based on Custom Post Type UI by WebDevStudios.

Credits Icons :
    - famfamfam - http://www.famfamfam.com/lab/icons/silk/

*/

// don't load directly
if (!defined('ABSPATH')) {
    die('-1');
}

if (!defined('STAGS_VERSION')) {
define('STAGS_VERSION', '3.6.3');
}


$pro_active = false;

foreach ((array)get_option('active_plugins') as $plugin_file) {
    if (false !== strpos($plugin_file, 'taxopress-pro.php')) {
        $pro_active = true;
        break;
    }
}

if (!$pro_active && is_multisite()) {
    foreach (array_keys((array)get_site_option('active_sitewide_plugins')) as $plugin_file) {
        if (false !== strpos($plugin_file, 'taxopress-pro.php')) {
            $pro_active = true;
            break;
        }
    }
}

if ($pro_active) {
    add_filter(
        'plugin_row_meta',
        function($links, $file)
        {
            if ($file == plugin_basename(__FILE__)) {
                $links[]= __('<strong>This plugin can be deleted.</strong>', 'simple-tags');
            }

            return $links;
        },
        10, 2
    );
}

if (defined('TAXOPRESS_FILE') || $pro_active) {
    if(!function_exists('deactivate_plugins')){
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }    
    //deactivate current plugin if pro is active
    deactivate_plugins( plugin_basename( __FILE__ ) );
	return;
}



define ( 'TAXOPRESS_FILE', __FILE__ );

define('STAGS_MIN_PHP_VERSION', '5.6');
define('STAGS_OPTIONS_NAME', 'simpletags'); // Option name for save settings
define('STAGS_OPTIONS_NAME_AUTO', 'simpletags-auto'); // Option name for save settings auto terms

define('STAGS_URL', plugins_url('', __FILE__));
define('STAGS_DIR', rtrim(plugin_dir_path(__FILE__), '/'));
define('TAXOPRESS_ABSPATH', __DIR__);

// Check PHP min version
if (version_compare(PHP_VERSION, STAGS_MIN_PHP_VERSION, '<')) {
    require STAGS_DIR . '/inc/class.compatibility.php';

    // possibly display a notice, trigger error
    add_action('admin_init', array('SimpleTags_Compatibility', 'admin_init'));

    // stop execution of this file
    return;
}

require STAGS_DIR . '/inc/loads.php';

// Init TaxoPress
function init_free_simple_tags()
{
    if (is_admin() && !defined('TAXOPRESS_PRO_VERSION')) {
        require_once(TAXOPRESS_ABSPATH . '/includes-core/TaxopressCoreAdmin.php');
        new \PublishPress\Taxopress\TaxopressCoreAdmin();
    }
}
add_action('plugins_loaded', 'init_free_simple_tags');

// Activation, uninstall
register_activation_hook(__FILE__, array('SimpleTags_Plugin', 'activation'));
register_deactivation_hook(__FILE__, array('SimpleTags_Plugin', 'deactivation'));

add_action('plugins_loaded', 'init_simple_tags');