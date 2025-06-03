<?php

namespace PublishPress\Taxopress;

class TaxopressCoreAdmin
{
    function __construct()
    {

        if (current_user_can('simple_tags')) {
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
                            'message' => esc_html__("You're using TaxoPress Free. The Pro version has more features and support. %sUpgrade to Pro%s", 'simple-tags'),
                            'link'    => 'https://taxopress.com/taxopress/',
                            'screens' => [
                                ['base' => 'taxopress_page_st_dashboard', 'id'   => 'taxopress_page_st_dashboard'],
                                ['base' => 'taxopress_page_st_taxonomies', 'id'   => 'taxopress_page_st_taxonomies'],
                                ['base' => 'taxopress_page_st_mass_terms', 'id'   => 'taxopress_page_st_mass_terms'],
                                ['base' => 'taxopress_page_st_manage',     'id'   => 'taxopress_page_st_manage'],
                                ['base' => 'taxopress_page_st_auto',       'id'   => 'taxopress_page_st_auto'],
                                ['base' => 'toplevel_page_st_options',     'id'   => 'toplevel_page_st_options'],
                                ['base' => 'taxopress_page_st_terms_display', 'id' => 'taxopress_page_st_terms_display'],
                                ['base' => 'toplevel_page_st_posts',     'id'   => 'toplevel_page_st_posts'],
                                ['base' => 'taxopress_page_st_posts',       'id'  => 'taxopress_page_st_posts'],
                                ['base' => 'taxopress_page_st_post_tags',   'id'  => 'taxopress_page_st_post_tags'],
                                ['base' => 'taxopress_page_st_related_posts', 'id' => 'taxopress_page_st_related_posts'],
                                ['base' => 'taxopress_page_st_autolinks',   'id'  => 'taxopress_page_st_autolinks'],
                                ['base' => 'taxopress_page_st_autoterms',   'id'  => 'taxopress_page_st_autoterms'],
                                ['base' => 'taxopress_page_st_autoterms_content',   'id'  => 'taxopress_page_st_autoterms_content'],
                                ['base' => 'taxopress_page_st_suggestterms', 'id'  => 'taxopress_page_st_suggestterms'],
                                ['base' => 'taxopress_page_st_terms',       'id'  => 'taxopress_page_st_terms'],
                                ['base' => 'taxopress_page_st_taxopress_ai', 'id'  => 'taxopress_page_st_taxopress_ai']
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
                        'label'  => __('Upgrade to Pro', 'simple-tags'),
                        'link'   => 'https://taxopress.com/taxopress/',
                    ];

                    return $settings;
                }
            );
        }


        add_action('taxopress_admin_class_before_assets_register', [$this, 'taxopress_load_admin_core_assets']);
        add_action('taxopress_admin_class_after_styles_enqueue', [$this, 'taxopress_load_admin_core_styles']);
        add_action('taxopress_admin_after_sidebar', [$this, 'taxopress_admin_advertising_sidebar_banner']);
        add_action('taxopress_autoterms_schedule_autoterm_terms_to_use', [$this, 'taxopress_core_schedule_autoterm_tab_content']);
        add_action('taxopress_autoterms_after_autoterm_terms_to_use', [$this, 'taxopress_core_autoterm_terms_to_use_field']);
        add_action('taxopress_suggestterm_after_api_fields', [$this, 'taxopress_core_suggestterm_after_api_fields']);
        add_action('taxopress_autoterms_after_autoterm_advanced', [$this, 'taxopress_core_autoterm_advanced_field']);
        add_action('taxopress_autolinks_after_html_exclusions_tr', [$this, 'taxopress_core_autolinks_after_html_exclusions_promo']);
        add_action('taxopress_settings_linked_terms_pro_notice', [$this, 'taxopress_core_linked_terms_content']);
        add_action('taxopress_settings_synonyms_terms_pro_notice', [$this, 'taxopress_core_synonyms_terms_content']);
        add_action('taxopress_ai_after_open_ai_fields', [$this, 'taxopress_core_ai_after_open_ai_fields']);
        add_action('taxopress_ai_after_ibm_watson_fields', [$this, 'taxopress_core_ai_after_ibm_watson_fields']);
        add_action('taxopress_ai_after_dandelion_fields', [$this, 'taxopress_core_ai_after_dandelion_fields']);
        add_action('taxopress_ai_after_open_calais_fields', [$this, 'taxopress_core_ai_after_open_calais_fields']);
        add_action('load_taxopress_ai_term_results', [$this, 'taxopress_core_ai_term_results_banner']);
        add_filter('taxopress_dashboard_features', [$this, 'taxopress_core_linked_terms_feature']);
        add_filter('taxopress_dashboard_features', [$this, 'taxopress_core_synonyms_terms_feature']);
        add_filter('taxopress_autolink_row_actions', [$this, 'taxopress_core_copy_action'], 10, 2);
        add_filter('taxopress_posttags_row_actions', [$this, 'taxopress_core_copy_action'], 10, 2);
        add_filter('taxopress_autoterm_row_actions', [$this, 'taxopress_core_copy_action'], 10, 2);
        add_filter('taxopress_relatedpost_row_actions', [$this, 'taxopress_core_copy_action'], 10, 2);
        add_filter('taxopress_tagclouds_row_actions', [$this, 'taxopress_core_copy_action'], 10, 2);
        add_filter('taxopress_terms_row_actions', [$this, 'taxopress_core_copy_with_metadata_action'], 10, 2);
        add_filter('taxopress_settings_post_type_ai_fields', [$this, 'filter_settings_post_type_ai_fields'], 10, 2);
        add_action('taxopress_terms_copy_with_metadata_promo', [$this, 'taxopress_terms_copy_with_metadata_promo']);
    }

    function taxopress_load_admin_core_assets()
    {
        wp_register_style('st-admin-core', STAGS_URL . '/includes-core/assets/css/core.css', array(), STAGS_VERSION, 'all');
    }

    function taxopress_load_admin_core_styles()
    {
        wp_enqueue_style('st-admin-core');
    }

    function taxopress_admin_advertising_sidebar_banner()
    {
?>

        <div class="taxopress-advertisement-right-sidebar">
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <div class="advertisement-box-content postbox">
                        <div class="postbox-header">
                            <h3 class="advertisement-box-header hndle is-non-sortable">
                                <span><?php echo esc_html__('Upgrade to TaxoPress Pro', 'simple-tags'); ?></span>
                            </h3>
                        </div>

                        <div class="inside">
                            <p><?php echo esc_html__('Enhance the power of TaxoPress with the Pro version:', 'simple-tags'); ?>
                            </p>
                            <ul>
                                <li><?php echo esc_html__('Unlimited “Term Display”', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Unlimited “Terms for Current Posts”', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Unlimited “Related Posts”', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Unlimited “Auto Links”', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Unlimited “Auto Terms”', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Create new terms using OpenAI and other sources', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Use Linked Terms', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Use Synonyms', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Schedule Auto Terms to run automatically', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Control the display of term metaboxes', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('Fast, professional support', 'simple-tags'); ?></li>
                                <li><?php echo esc_html__('No ads inside the plugin', 'simple-tags'); ?></li>
                            </ul>
                            <div class="upgrade-btn">
                                <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                            </div>
                        </div>
                    </div>
                    <div class="advertisement-box-content postbox">
                        <div class="postbox-header">
                            <h3 class="advertisement-box-header hndle is-non-sortable">
                                <span><?php echo esc_html__('Need TaxoPress Support?', 'simple-tags'); ?></span>
                            </h3>
                        </div>

                        <div class="inside">
                            <p><?php echo esc_html__('If you need help or have a new feature request, let us know.', 'simple-tags'); ?>
                                <a class="advert-link" href="https://wordpress.org/support/plugin/simple-tags/" target="_blank">
                                    <?php echo esc_html__('Request Support', 'simple-tags'); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                        <path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path>
                                    </svg>
                                </a>
                            </p>
                            <p>
                                <?php echo esc_html__('Detailed documentation is also available on the plugin website.', 'simple-tags'); ?>
                                <a class="advert-link" href="https://taxopress.com/docs/taxopress/" target="_blank">
                                    <?php echo esc_html__('View Knowledge Base', 'simple-tags'); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                        <path d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"></path>
                                    </svg>
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php
    }

    function taxopress_core_ai_after_open_ai_fields($current)
    {
    ?>
        <tr>
            <td>
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('OpenAI Integration', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to integrate OpenAI to analyze your content and suggest terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php
    }

    function taxopress_core_ai_after_ibm_watson_fields($current)
    {
    ?>
    <tr>
        <td>
            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('IBM Watson Integration', 'simple-tags'); ?></span>
                    </h3>
                </div>

                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to integrate Watson to analyze your content and suggest terms.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    <?php
    }

    function taxopress_core_ai_after_dandelion_fields($current)
    {
    ?>
    <tr>
        <td>
            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('Dandelion Integration', 'simple-tags'); ?></span>
                    </h3>
                </div>

                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to integrate Dandelion to analyze your content and suggest terms.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    <?php
    }

    function taxopress_core_ai_after_open_calais_fields($current)
    {
    ?>
    <tr>
        <td>
            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('LSEG / Refinitiv Integration', 'simple-tags'); ?></span>
                    </h3>
                </div>

                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to integrate LSEG / Refinitiv to analyze your content and suggest terms.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        </td>
    </tr>
    <?php
    }

    

	function taxopress_core_ai_term_results_banner($args) {
        $ai_group     = $args['ai_group'];

        if ($ai_group == 'open_ai') : ?>
            <br />
            <br />
            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('Suggest terms using AI', 'simple-tags'); ?></span>
                    </h3>
                </div>
                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to suggest new terms for your content using the OpenAI. This service can analyze your content and suggest new terms.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        <?php
        elseif ($ai_group == 'ibm_watson') :
            ?>
            <br />
            <br />
            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable">
                        <span><?php echo esc_html__('Suggest terms using AI', 'simple-tags'); ?></span>
                    </h3>
                </div>

                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to suggest new terms for your content using the IBM Watson. This service can analyze your content and suggest new terms.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        <?php
        endif;
    }

    function taxopress_core_copy_action($actions, $item) {
        $allowed_pages = ['st_autolinks', 'st_terms_display', 'st_post_tags', 'st_related_posts', 'st_autoterms'];
    
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
        if (in_array($current_page, $allowed_pages, true)) { 
                $copy_action = [
                    'copy' => sprintf(
                        '<a href="%s" class="copy-action-pages">%s</a>',
                        add_query_arg([
                            'page' => $current_page,
                            'add'  => 'new_item',
                        ], admin_url('admin.php')),
                        __('Copy', 'simple-tags')
                    )
                ];
    
                // Ensure "Copy" appears before "Delete"
                if (isset($actions['delete'])) {
                    $new_actions = [];
                    foreach ($actions as $key => $action) {
                        if ($key === 'delete') {
                            $new_actions['copy'] = $copy_action['copy'];
                        }
                        $new_actions[$key] = $action;
                    }
                    $actions = $new_actions;
                } else {
                    $actions = $copy_action + $actions;
                }
            
        }
    
        return $actions;
    }

    function taxopress_core_copy_with_metadata_action($actions, $item) {
        $allowed_pages = ['st_terms'];
    
        $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
        if (in_array($current_page, $allowed_pages, true)) { 
                $copy_action = [
                    'copy_term_with_meta' => sprintf(
                        '<a href="%s" class="copy-action-pages">%s</a>',
                        add_query_arg([
                            'page' => $current_page,
                            'action' => 'new_item',
                            'term_id' => $item->term_id,
                            '_wpnonce' => wp_create_nonce('term-copy-metadata-nonce')
                        ], admin_url('admin.php')),
                        __('Copy with Metadata', 'simple-tags')
                    )
                ];
    
                // Ensure "Copy with Metadata" appears after "Copy"
                if (isset($actions['copy_term'])) {
                    $new_actions = [];
                    foreach ($actions as $key => $action) {
                        $new_actions[$key] = $action;
                        if ($key === 'copy_term') {
                            $new_actions['copy_term_with_meta'] = $copy_action['copy_term_with_meta'];
                        }
                    }
                    $actions = $new_actions;
                } else {
                    $actions = $copy_action + $actions;
                }
        }
    
        return $actions;
    }

    function taxopress_core_schedule_autoterm_tab_content($current)
    {
        
        ?>
        <tr>
            <td>
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro autoterm-terms-when-schedule-notice">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Schedule Auto Terms', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to schedule the Auto Terms feature to run either hourly or daily. This is really useful if you are regularly updating your posts, or if you’re automatically importing new posts.', 'si’ple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="_blank"><?php echo esc_html__("Upgrade to Pro", "simple-tags"); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php
    }

    function taxopress_core_autoterm_terms_to_use_field($current)
    {
    ?>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro autoterm-terms-use-openai-notice">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Auto terms using AI', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to generate new terms for your content using the OpenAI, IBM Watson, Dandelion and Open Calais services. These services can analyze your content and add new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro autoterm-terms-use-ibm-watson-notice">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Auto terms using AI', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to generate new terms for your content using the OpenAI, IBM Watson, Dandelion and Open Calais services. These services can analyze your content and add new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro autoterm-terms-use-dandelion-notice">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Auto terms using AI', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to generate new terms for your content using the OpenAI, IBM Watson, Dandelion and Open Calais services. These services can analyze your content and add new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro autoterm-terms-use-lseg-refinitiv-notice">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Auto terms using AI', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to generate new terms for your content using the OpenAI, IBM Watson, Dandelion and Open Calais services. These services can analyze your content and add new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php
    }

    function taxopress_core_suggestterm_after_api_fields($current)
    {
    ?>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Suggest terms using AI', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to suggest new terms for your content using the OpenAI, IBM Watson, Dandelion and Open Calais services. These services can analyze your content and suggest new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php
    }
    
    function taxopress_core_autolinks_after_html_exclusions_promo($current)
    {
    ?>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Add Custom Elements', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you complete control over where Auto Links are added. You can choose to skip any HTML elements that appear in your content.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    <?php
    }

    function taxopress_core_autoterm_advanced_field($current)
    {
    ?>
        <tr>
            <td>
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Use Regular Expressions to modify Auto Terms', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to change how Auto Terms analyzes your posts. You will need to know how to write Regular Expressions to use this feature.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
<?php
    }
    /**
     * Add Linked Terms feature to dashboard
     */
    function taxopress_core_linked_terms_feature($features) {
        $st_terms_position = array_search('st_terms', array_keys($features));
        
       if ($st_terms_position !== false) {
            $new_features = array_slice($features, 0, $st_terms_position + 1, true);
            
            $new_features['st_linked_terms'] = [
                'label'        => esc_html__('Linked Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to connect terms. When the main term or any of these terms are added to the post, all the other terms will be added also.', 'simple-tags'),
                'option_key'   => 'active_features_core_linked_terms',
                'class'       => 'feature-pro-locked',
            ];
            
            $new_features += array_slice($features, $st_terms_position + 1, null, true);
            return $new_features;
        }

        return $features;
    }

    function taxopress_core_linked_terms_content($content)
    {
        ob_start();
        ?>

            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable taxopress-core-terms-promobox">
                        <span><?php echo esc_html__('Linked Terms Feature', 'simple-tags'); ?></span>
                    </h3>
                </div>
                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to create powerful connections between your terms. When one term is added to a post, its linked terms can be automatically added too. This helps maintain consistent organization across your content.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }

     /**
     * Add Synonyms Terms feature to dashboard
     */
    function taxopress_core_synonyms_terms_feature($features) {

        $features['st_features_synonyms'] = [
            'label'        => esc_html__('Synonyms', 'simple-tags'),
            'description'  => esc_html__('This feature allows you to associate additional words with each term. For example, "website" can have synonyms such as "websites", "web site", and "web pages".', 'simple-tags'),
            'option_key'   => 'active_features_core_synonyms_terms',
            'class'       => 'feature-pro-locked',
        ];

        return $features;
    }

    function taxopress_core_synonyms_terms_content($content)
    {
        ob_start();
        ?>

            <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                <div class="postbox-header">
                    <h3 class="advertisement-box-header hndle is-non-sortable taxopress-core-terms-promobox">
                        <span><?php echo esc_html__('Synonyms Terms Feature', 'simple-tags'); ?></span>
                    </h3>
                </div>
                <div class="inside-content">
                    <p><?php echo esc_html__('TaxoPress Pro allows you to have multiple words associated with a single term. If TaxoPress scans your content and finds a synonym, it will act as if it has found the main term.', 'simple-tags'); ?></p>
                    <div class="upgrade-btn">
                        <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                    </div>
                </div>
            </div>
        <?php
        return ob_get_clean();
    }

    public function filter_settings_post_type_ai_fields($taxopress_ai_fields, $post_type)
        {

            $default_taxonomy_display_options = [
                'default' => esc_html__('Default', 'simple-tags'),
            ];
            
            $new_entry = array(
                'taxopress_ai_' . $post_type . '_metabox_display_option',
                '<div class="taxopress-ai-tab-content-sub taxopress-settings-subtab-title taxopress-ai-' . $post_type . '-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content">' .
                    esc_html__('Metabox Taxonomy Display', 'simple-tags') .
                '</div>',
                'select_with_icon',
                $default_taxonomy_display_options,
                '<div class="taxopress-select-icon-wrapper">
                    <span class="pp-tooltips-library" data-toggle="tooltip">
                        <span class="dashicons dashicons-lock taxopress-select-icon"></span>
                        <span class="taxopress tooltip-text">' .
                            esc_html__('This feature is available in TaxoPress Pro', 'simple-tags') .
                        '</span>
                    </span>
                </div>
                <div class="taxopress-stpexplan">' .
                    esc_html__('Customize the display of terms in the TaxoPress metabox.', 'simple-tags') . '<br />' .
                    esc_html__('Options include checkboxes and a dropdown list.', 'simple-tags') .
                '</div>',
                'taxopress-select-with-icon taxopress-ai-tab-content-sub taxopress-ai-' . $post_type . '-content-sub enable_taxopress_ai_' . $post_type . '_metabox_field st-subhide-content',
                array(
                    'icon' => '',
                    'modal' => '',
                    'icon_wrapper_class' => '',
                    'modal_wrapper_class' => '',
                ),
            );
            // Get the index of 'taxopress_ai_post_metabox_default_taxonomy' if it exists
            $field_to_find = 'taxopress_ai_' . $post_type . '_metabox_default_taxonomy';
            $keys = array_column($taxopress_ai_fields, 0);
            $insert_after_key = array_search($field_to_find, $keys);
        
            // Determine the insertion position adding fallback incase the setting doesn't exist
            $position = ($insert_after_key !== false) ? $insert_after_key + 1 : count($taxopress_ai_fields);
        
            // Insert new entry at the determined position
            $taxopress_ai_fields = array_merge(
                array_slice($taxopress_ai_fields, 0, $position, true),
                [$new_entry],
                array_slice($taxopress_ai_fields, $position, null, true)
            );

            return $taxopress_ai_fields;
    }

    function taxopress_terms_copy_with_metadata_promo() {
        ?>
        <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
            <div class="postbox-header">
                <h3 class="advertisement-box-header hndle is-non-sortable taxopress-core-terms-promobox">
                    <span><?php echo esc_html__('Copy Term with Metadata', 'simple-tags'); ?></span>
                </h3>
            </div>

            <div class="inside-content">
                <h2><?php echo esc_html__('To Copy terms with their metadata, please upgrade to pro.', 'simple-tags') ?></h2>
                <p><?php echo esc_html__('With TaxoPress Pro, you can duplicate taxonomy terms along with all their metadata. This includes term descriptions, images, and custom fields. You can copy terms between taxonomies to maintain consistent organization across your site.', 'simple-tags'); ?></p>
                <div class="upgrade-btn">
                    <a href="https://taxopress.com/taxopress/" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                </div>
            </div>
        </div>
        <?php
    }
}
