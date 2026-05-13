<?php

namespace PublishPressInstanceProtection;

/**
 * Check if the plugin is on the standard path.
 */
class Config
{
    /**
     * The plugin slug.
     *
     * @var string
     */
    public $pluginSlug;

    /**
     * The plugin name.
     *
     * @var string
     */
    public $pluginName;

    /**
     * The plugin folder
     *
     * @var [type]
     */
    public $pluginFolder = '';

    /**
     * True if the plugin is Pro.
     *
     * @var boolean
     */
    public $isProPlugin = false;

    /**
     * The name of the free plugin.
     *
     * @var string
     */
    public $freePluginName = '';
}
