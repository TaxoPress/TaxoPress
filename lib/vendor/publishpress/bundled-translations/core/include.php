<?php

/*****************************************************************
 * This file is generated on composer update command by
 * a custom script.
 *
 * Do not edit it manually!
 ****************************************************************/

namespace PublishPress\BundledTranslations;

use function add_action;
use function do_action;

if (! function_exists('add_action')) {
    return;
}

if (! function_exists(__NAMESPACE__ . '\register1Dot0Dot0')) {
    if (! defined('PUBLISHPRESS_BUNDLED_TRANSLATIONS_INCLUDED')) {
        define('PUBLISHPRESS_BUNDLED_TRANSLATIONS_INCLUDED', __DIR__);
    }

    if (! class_exists('PublishPress\BundledTranslations\Versions')) {
        require_once __DIR__ . '/Versions.php';

        add_action('plugins_loaded', [Versions::class, 'initializeLatestVersion'], -190, 0);
    }

    add_action('plugins_loaded', __NAMESPACE__ . '\register1Dot0Dot0', -200, 0);

    function register1Dot0Dot0()
    {
        if (! class_exists('PublishPress\BundledTranslations\BundledTranslations')) {
            $versions = Versions::getInstance();
            $versions->register('1.0.0', __NAMESPACE__ . '\initialize1Dot0Dot0');
        }
    }

    function initialize1Dot0Dot0()
    {
        require_once __DIR__ . '/autoload.php';

        if (! defined('PUBLISHPRESS_BUNDLED_TRANSLATIONS_VERSION')) {
            define('PUBLISHPRESS_BUNDLED_TRANSLATIONS_VERSION', '1.0.0');
        }

        do_action('publishpress_bundled_translations_1Dot0Dot0_initialized');
    }
}
