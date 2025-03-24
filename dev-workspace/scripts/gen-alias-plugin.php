#!/usr/bin/php
<?php

const PLUGIN_NAME = 'publishpress-future';
const PLUGIN_SLUG = 'post-expirator';
const PLUGIN_FOLDER = 'post-expirator';

// Define the new path
$newPath = realpath("/project/lib/vendor/publishpress/" . PLUGIN_NAME);

// Create the tree structure
function createTree($path)
{
    // Loop through all files and directories in the given path
    $files = glob($path . '/*');
    foreach ($files as $file) {
        if (is_dir($file)) {
            // If it's a directory, recursively call the function
            createTree($file);
        } elseif (is_file($file) && pathinfo($file, PATHINFO_EXTENSION) === 'php') {
            // If it's a PHP file, create the replacement file
            $relativeFilePath = str_replace('/project', '', $file);
            $deprecatedFilePath = str_replace('/lib/vendor/', '/project/vendor/', $relativeFilePath);
            $newFilePath = ltrim($relativeFilePath, '/');
            $fileLevels = count(explode('/', $newFilePath)) - 2;
            $newFileRelativePath = str_repeat('../', $fileLevels) . $newFilePath;

            // Create the new file with require_once statement
            $newFileContent = "<?php\n\nrequire_once realpath(__DIR__ . '/$newFileRelativePath');\n";

            // Create the new file in the new path
            if (!is_dir(dirname($deprecatedFilePath))) {
                mkdir(dirname($deprecatedFilePath), 0777, true);
            }
            file_put_contents($deprecatedFilePath, $newFileContent);
        }
    }
}

function createComposerFile()
{
    $pluginSlug = PLUGIN_SLUG;
    $pluginName = PLUGIN_NAME;
    $pluginFolder = PLUGIN_FOLDER;

    $path = "/project/vendor/publishpress/$pluginName/composer.json";
    $content = <<<EOT
{
  "name": "publishpress/{$pluginName}",
  "type": "wordpress-plugin",
  "description": "Deprecated plugin code. Will be removed soon.",
  "homepage": "https://publishpress.com/",
  "license": "GPL-2",
  "authors": [
    {
      "name": "PublishPress",
      "email": "help@publishpress.com"
    }
  ],
  "config": {
    "preferred-install": {
      "*": "dist"
    }
  },
  "minimum-stability": "stable",
  "prefer-stable": true,
  "require": {
    "php": ">=7.2.5"
  },
  "extra": {
    "plugin-slug": "{$pluginSlug}",
    "plugin-name": "{$pluginName}",
    "plugin-folder": "{$pluginFolder}"
  }
}

EOT;

    file_put_contents($path, $content);
}

// Call the function with the new path
createTree($newPath);
createComposerFile();
