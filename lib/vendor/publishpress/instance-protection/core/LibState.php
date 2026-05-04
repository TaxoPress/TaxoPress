<?php

namespace PublishPressInstanceProtection;

abstract class LibState
{
    private static $states = [];

    public static function setState($stateName, $value = true)
    {
        self::$states[$stateName] = $value;
    }

    public static function getState($stateName)
    {
        return isset(self::$states[$stateName]) ? self::$states[$stateName] : false;
    }

    public static function setPluginState($pluginSlug, $stateName, $value = true)
    {
        self::setState($pluginSlug . '/' . $stateName, $value);
    }

    public static function getPluginState($pluginSlug, $stateName)
    {
        return self::getState($pluginSlug . '/' . $stateName);
    }
}
