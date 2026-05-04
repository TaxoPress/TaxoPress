<?php
namespace PublishPress\BundledTranslations;

/**
 * Forces WordPress to use a plugin's bundled translations instead of
 * the global translations downloaded from wordpress.org.
 */
class BundledTranslations
{
    /**
     * The plugin text domain.
     *
     * @var string
     */
    private $domain;

    /**
     * Absolute path to the plugin's bundled languages directory.
     *
     * @var string
     */
    private $languagesDir;

    /**
     * Absolute path to the main plugin file.
     *
     * @var string
     */
    private $pluginFile;

    /**
     * @param string $domain       The plugin text domain.
     * @param string $languagesDir Absolute path to the plugin's bundled languages directory.
     * @param string $pluginFile   Absolute path to the main plugin file.
     */
    public function __construct($domain, $languagesDir, $pluginFile)
    {
        $this->domain = $domain;
        $this->languagesDir = rtrim($languagesDir, '/\\');
        $this->pluginFile = $pluginFile;
    }

    /**
     * Initialize the translation override.
     *
     * @return void
     */
    public function init()
    {
        if (! $this->isEnabled()) {
            return;
        }

        add_filter('load_textdomain_mofile', [$this, 'filterMoFile'], 10, 2);
    }

    /**
     * Check whether bundled translations are enabled.
     * @return bool
     */
    private function isEnabled()
    {
        $enabled = defined('PUBLISHPRESS_BUNDLED_TRANSLATIONS_ENABLED')
            ? constant('PUBLISHPRESS_BUNDLED_TRANSLATIONS_ENABLED')
            : true;

        $enabled = apply_filters(
            'publishpress_bundled_translations_enabled',
            $enabled,
            $this->domain,
            $this->pluginFile
        );

        return (bool) $enabled;
    }

    /**
     * Filter the .mo file path to use the plugin's bundled translations
     * when WordPress tries to load from the global languages directory.
     *
     * @param string $mofile Path to the .mo file.
     * @param string $domain Text domain.
     * @return string Filtered path to the .mo file.
     */
    public function filterMoFile($mofile, $domain)
    {
        if ($domain !== $this->domain) {
            return $mofile;
        }

        if (false === strpos($mofile, WP_LANG_DIR . '/plugins/')) {
            return $mofile;
        }

        $locale = determine_locale();
        $pluginMofile = $this->languagesDir . '/' . $this->domain . '-' . $locale . '.mo';

        if (! file_exists($pluginMofile)) {
            return $mofile;
        }

        return $pluginMofile;
    }
}
