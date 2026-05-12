The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

[1.0.2] - 8 May, 2026

- Fixed BundledTranslations issue when WP passes false as $file; improved filterScriptTranslationFile for false inputs and JS translation file handling.

[1.0.1] - 7 May, 2026

- Added: Added support for bundled .l10n.php (performant) translation file redirection via load_translation_file.
- Added: Now supports script translation file overrides via `load_script_translation_file` filter
- Improved: Refined detection of global and plugin translation paths; avoids redirecting Loco Translate overrides
- Improved: Defensive code for edge-case file paths (JIT translation loads in WP 6.7+)

[1.0.0] - 15 Apr, 2026

- Added: Initial release of the Bundled Translations library
- Added: `BundledTranslations` class to redirect `.mo` file loading from `wp-content/languages/plugins/` to the plugin's bundled `languages/` directory
- Added: `load_textdomain_mofile` filter integration with fail-fast pattern
- Added: Bypass option via `PUBLISHPRESS_BUNDLED_TRANSLATIONS_ENABLED` constant
- Added: Bypass option via `publishpress_bundled_translations_enabled` filter with per-plugin control
- Added: PSR-4 autoloader
