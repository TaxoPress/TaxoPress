The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

[1.0.0] - 15 Apr, 2025

- Added: Initial release of the Bundled Translations library
- Added: `BundledTranslations` class to redirect `.mo` file loading from `wp-content/languages/plugins/` to the plugin's bundled `languages/` directory
- Added: `load_textdomain_mofile` filter integration with fail-fast pattern
- Added: Bypass option via `PUBLISHPRESS_BUNDLED_TRANSLATIONS_ENABLED` constant
- Added: Bypass option via `publishpress_bundled_translations_enabled` filter with per-plugin control
- Added: PSR-4 autoloader
