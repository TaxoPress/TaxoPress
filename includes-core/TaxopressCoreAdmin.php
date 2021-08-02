<?php
namespace PublishPress\Taxopress;

class TaxopressCoreAdmin {
    function __construct() {

        if ( current_user_can( 'simple_tags' ) ) {
        if (is_admin()) {
            $autoloadPath = TAXOPRESS_ABSPATH . '/vendor/autoload.php';
			if (file_exists($autoloadPath)) {
				require_once $autoloadPath;
			}

            require_once TAXOPRESS_ABSPATH . '/vendor/publishpress/wordpress-version-notices/includes.php';
            add_filter(
                        \PPVersionNotices\Module\TopNotice\Module::SETTINGS_FILTER,
                        function ($settings) {
                            $settings['publishpress-taxopress'] = [
                                'message' => 'You\'re using TaxoPress Free. The Pro version has more features and support. %sUpgrade to Pro%s',
                                'link'    => 'https://taxopress.com/pro',
                                'screens' => [
                                    ['base' => 'taxopress_page_st_taxonomies', 'id'   => 'taxopress_page_st_taxonomies'],
                                    ['base' => 'taxopress_page_st_mass_terms', 'id'   => 'taxopress_page_st_mass_terms'],
                                    ['base' => 'taxopress_page_st_manage',     'id'   => 'taxopress_page_st_manage'],
                                    ['base' => 'taxopress_page_st_auto',       'id'   => 'taxopress_page_st_auto'],
                                    ['base' => 'toplevel_page_st_options',     'id'   => 'toplevel_page_st_options'],
                                    ['base' => 'taxopress_page_st_terms_display','id' => 'taxopress_page_st_terms_display'],
                                    ['base' => 'taxopress_page_st_post_tags',   'id'  => 'taxopress_page_st_post_tags'],
                                    ['base' => 'taxopress_page_st_related_posts','id' => 'taxopress_page_st_related_posts'],
                                    ['base' => 'taxopress_page_st_autolinks',   'id'  => 'taxopress_page_st_autolinks'],
                                ]
                            ];

                            return $settings;
                        }
                    );


        }
                    add_filter(
                        \PPVersionNotices\Module\MenuLink\Module::SETTINGS_FILTER,
                        function ($settings) {
                            $settings['publishpress-taxopress'] = [
                                'parent' => 'st_options',
                                'label'  => 'Upgrade to Pro',
                                'link'   => 'https://taxopress.com/pro',
                            ];

                            return $settings;
                        });

        }


    }

}
