#!/usr/bin/php
<?php

define('BASE_PATH', '/project');
define('COMPOSER_JSON_PATH', BASE_PATH . '/composer.json');

function getExtraInfoFromComposerJson($composerJsonPath): array
{
    $composerJson = json_decode(file_get_contents($composerJsonPath), true);

    return $composerJson['extra'] ?? [];
}

$extra = getExtraInfoFromComposerJson(COMPOSER_JSON_PATH);
$pluginSlug = $extra['plugin-slug'] ?? '';
$versionConstant = $extra['version-constant'] ?? '';

define('PLUGIN_SLUG', $extra['plugin-slug'] ?? '');
define('VERSION_CONSTANT', $extra['version-constant'] ?? '');

if (empty(PLUGIN_SLUG) || empty(VERSION_CONSTANT)) {
    echo "Plugin slug or version constant not found in composer.json\n";
    exit(1);
}

function isStableVersion($version): bool
{
    return preg_match('/^\d+\.\d+\.\d+$/', $version);
}

function isValideVersion($version): bool
{
    return preg_match('/^\d+\.\d+\.\d+(-(alpha|beta|rc)\.[0-9]+)?$/', $version);
}

function askForNewVersion(): string
{
    $newVersion = readline("Enter new version: ");

    return $newVersion;
}

if (isset($argv[1])) {
    $newVersion = $argv[1];
} else {
    $newVersion = askForNewVersion();
}

if (!isValideVersion($newVersion)) {
    echo "Invalid version format. Please use x.y.z[-(alpha|beta|rc).w] format\n";
    exit(1);
}

define('NEW_VERSION', $newVersion);

function updateVersionConstantInMainPluginFile($versionConstant, $newVersion): void
{
    $mainPluginFilePath = BASE_PATH . '/' . PLUGIN_SLUG . '.php';

    $mainPluginFile = file_get_contents($mainPluginFilePath);

    $mainPluginFile = preg_replace_callback(
        "/define\('$versionConstant', '.*?'\);/",
        function ($matches) use ($versionConstant, $newVersion) {
            return "define('$versionConstant', '" . $newVersion . "');";
        },
        $mainPluginFile
    );

    file_put_contents($mainPluginFilePath, $mainPluginFile);
}

function updateVersionInMainPluginFileHeader($newVersion): void
{
    $mainPluginFilePath = BASE_PATH . '/' . PLUGIN_SLUG . '.php';

    $mainPluginFile = file_get_contents($mainPluginFilePath);

    $mainPluginFile = preg_replace(
        '/\*\s+Version: .*/',
        '* Version: ' . $newVersion,
        $mainPluginFile
    );

    file_put_contents($mainPluginFilePath, $mainPluginFile);
}

function updateVersionInReadme($newVersion): void
{
    $readmeFilePath = BASE_PATH . '/readme.txt';

    $readmeFile = file_get_contents($readmeFilePath);

    $readmeFile = preg_replace(
        '/Stable tag: .*/',
        'Stable tag: ' . $newVersion,
        $readmeFile
    );

    file_put_contents($readmeFilePath, $readmeFile);
}

function updateVersionInComposerJson($newVersion): void
{
    $composerJson = json_decode(file_get_contents(COMPOSER_JSON_PATH), true);

    $composerJson['version'] = $newVersion;

    file_put_contents(COMPOSER_JSON_PATH, json_encode($composerJson, JSON_PRETTY_PRINT));
}

updateVersionConstantInMainPluginFile(VERSION_CONSTANT, NEW_VERSION);
updateVersionInMainPluginFileHeader(NEW_VERSION);
// updateVersionInComposerJson(NEW_VERSION);

if (isStableVersion(NEW_VERSION)) {
    updateVersionInReadme(NEW_VERSION);
}

echo "Version updated to " . NEW_VERSION . "\n";
