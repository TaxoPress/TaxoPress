<?php

namespace PublishPress\WordpressVersionNotices;

class Autoloader
{
    /**
     * Register the autoloader with spl_autoload_register
     *
     * @return void
     */
    public static function register()
    {
        spl_autoload_register(array(new self(), 'autoload'));
    }

    /**
     * Autoload function that loads classes based on the namespace and class name
     *
     * @param string $class The fully-qualified class name
     *
     * @return void
     */
    public static function autoload($class)
    {
        // base directory for the namespace prefix
        $base_dir = __DIR__ . '/';

        // namespace prefix
        $prefix = 'PublishPress\\WordpressVersionNotices\\';

        // does the class use the namespace prefix?
        $len = strlen($prefix);

        if (strncmp($prefix, $class, $len) !== 0) {
            // no, move to the next registered autoloader
            return;
        }

        // get the relative class name
        $relative_class = substr($class, $len);

        // replace the namespace prefix with the base directory, replace namespace separators with directory separators
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        // if the file exists, require it
        if (file_exists($file)) {
            require $file;
        }
    }
}
