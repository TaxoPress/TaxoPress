<?php

if (! class_exists('PublishPressInstanceProtection\\InstanceChecker')) {
    if (! class_exists('PublishPressInstanceProtection\\Autoloader')) {
        require_once __DIR__ . '/core/Autoloader.php';
    }

    $autoloader = new PublishPressInstanceProtection\Autoloader();
    $autoloader->register();
    $autoloader->addNamespace('PublishPressInstanceProtection', __DIR__ . '/core');
}
