<?php

namespace PublishPress\BundledTranslations;

if (! class_exists('PublishPress\\BundledTranslations\\Autoloader')) {
    require_once __DIR__ . '/Autoloader.php';
}

$autoloader = new Autoloader();
$autoloader->register();
$autoloader->addNamespace('PublishPress\\BundledTranslations', __DIR__);
