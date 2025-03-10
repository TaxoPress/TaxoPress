<?php

/**
 * This is project's console commands configuration for Robo task runner.
 *
 * @see http://robo.li/
 */
class RoboFile extends \PublishPressBuilder\PackageBuilderTasks
{
    public function __construct()
    {
        $this->setPluginFileName('simple-tags.php');
        $this->setVersionConstantName('STAGS_VERSION');
        $this->appendToFileToIgnore(
            [
                'dist',
                '.phplint.yml',
                'webpack.config.js',
                'vendor/pimple/pimple/.gitignore',
                'vendor/pimple/pimple/.php_cs.dist',
                'vendor/psr/container/.gitignore',
                'vendor/publishpress/wordpress-version-notices/.gitignore',
                'vendor/publishpress/wordpress-version-notices/README.md',
                'vendor/publishpress/wordpress-version-notices/bin',
                'vendor/publishpress/wordpress-version-notices/codeception.dist.yml',
                'vendor/publishpress/wordpress-version-notices/codeception.yml',
                'vendor/publishpress/wordpress-version-notices/tests',
                'vendor/pimple/pimple/.github',
                'vendor/publishpress/wordpress-version-notices/.env.testing.dist',
                'vendor/publishpress/wordpress-version-notices/composer.json',
                'vendor/publishpress/wordpress-version-notices/composer.lock',
            ]
        );

        parent::__construct();
    }
}
