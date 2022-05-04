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
                                    ['base' => 'taxopress_page_st_autoterms',   'id'  => 'taxopress_page_st_autoterms'],
                                    ['base' => 'taxopress_page_st_suggestterms','id'  => 'taxopress_page_st_suggestterms'],
                                    ['base' => 'taxopress_page_st_terms',       'id'  => 'taxopress_page_st_terms']
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


        add_action('taxopress_admin_class_before_assets_register', [$this, 'taxopress_load_admin_core_assets']);
        add_action('taxopress_admin_class_after_styles_enqueue', [$this, 'taxopress_load_admin_core_styles']);
        add_action('taxopress_admin_after_sidebar', [$this, 'taxopress_admin_advertising_sidebar_banner']);
        add_action('taxopress_autoterms_after_autoterm_schedule', [$this, 'taxopress_pro_autoterm_schedule_field']);
        add_action('taxopress_autoterms_after_autoterm_terms_to_use', [$this, 'taxopress_pro_autoterm_terms_to_use_field']);
        add_action('taxopress_autoterms_after_autoterm_advanced', [$this, 'taxopress_core_autoterm_advanced_field']);
    }

    function taxopress_load_admin_core_assets(){
        wp_register_style( 'st-admin-core', STAGS_URL . '/includes-core/assets/css/core.css', array(), STAGS_VERSION, 'all' );
    }
    
    function taxopress_load_admin_core_styles(){
        wp_enqueue_style( 'st-admin-core' );
    }
    
    function taxopress_admin_advertising_sidebar_banner(){ 
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
                            <li><?php echo esc_html__('Unlimited “Suggest Terms”', 'simple-tags'); ?></li>
                            <li><?php echo esc_html__('Automatically create new terms', 'simple-tags'); ?></li>
                            <li><?php echo esc_html__('Automatically add terms to imported content', 'simple-tags'); ?></li>
                            <li><?php echo esc_html__('Fast, professional support', 'simple-tags'); ?></li>
                            <li><?php echo esc_html__('No ads inside the plugin', 'simple-tags'); ?></li>
                        </ul>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/pro" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
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
                                    <path
                                        d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                                    ></path>
                                </svg>
                            </a>
                        </p>
                        <p>
                        <?php echo esc_html__('Detailed documentation is also available on the plugin website.', 'simple-tags'); ?> 
                            <a class="advert-link" href="https://taxopress.com/docs/" target="_blank">
                            <?php echo esc_html__('View Knowledge Base', 'simple-tags'); ?> 
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                    <path
                                        d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                                    ></path>
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

    function taxopress_pro_autoterm_schedule_field($current){
        ?>
        <tr>
            <td>
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Schedule Auto Terms for your content', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to schedule the "Auto Terms to existing content" feature. This is helpful if you regularly import content into WordPress. TaxoPress Pro can run either daily or hourly and add terms to your imported content.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/pro" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php 
    }
    
    function taxopress_pro_autoterm_terms_to_use_field($current){
        ?>
        <tr>
            <td colspan="2">
                <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                    <div class="postbox-header">
                        <h3 class="advertisement-box-header hndle is-non-sortable">
                            <span><?php echo esc_html__('Automatically add new terms to your content', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside-content">
                        <p><?php echo esc_html__('TaxoPress Pro allows you to generate new terms for your content using the Dandelion and Open Calais services. These services can analyze your content and add new terms.', 'simple-tags'); ?></p>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/pro" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php 
    }

    function taxopress_core_autoterm_advanced_field($current){
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
                            <a href="https://taxopress.com/pro" target="__blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
            </td>
        </tr>
        <?php 
    }

}
