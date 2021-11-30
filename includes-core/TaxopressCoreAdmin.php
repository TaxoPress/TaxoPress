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
                                    ['base' => 'taxopress_page_st_suggestterms',   'id'  => 'taxopress_page_st_suggestterms']
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
                <div class="postbox">
                    <div class="postbox-header">
                        <h3 class="hndle is-non-sortable">
                            <span><?php echo __('Upgrade to TaxoPress Pro', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside">
                        <p><?php echo __('Enhance the power of TaxoPress with the Pro version:', 'simple-tags'); ?>
                        </p>
                        <ul>
                            <li><?php echo __('Unlimited “Term Display”', 'simple-tags'); ?></li>
                            <li><?php echo __('Unlimited “Terms for Current Posts”', 'simple-tags'); ?></li>
                            <li><?php echo __('Unlimited “Related Posts”', 'simple-tags'); ?></li>
                            <li><?php echo __('Unlimited “Auto Links”', 'simple-tags'); ?></li>
                            <li><?php echo __('Unlimited “Auto Terms”', 'simple-tags'); ?></li>
                            <li><?php echo __('Unlimited “Suggest Terms”', 'simple-tags'); ?></li>
                            <li><?php echo __('Fast, professional support', 'simple-tags'); ?></li>
                            <li><?php echo __('No ads inside the plugin', 'simple-tags'); ?></li>
                        </ul>
                        <div class="upgrade-btn">
                            <a href="https://taxopress.com/pro" target="__blank"><?php echo __('Upgrade to Pro', 'simple-tags'); ?></a>
                        </div>
                    </div>
                </div>
                <div class="postbox">
                    <div class="postbox-header">
                        <h3 class="hndle is-non-sortable">
                            <span><?php echo __('Need TaxoPress Support?', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside">
                        <p><?php echo __('If you need help or have a new feature request, let us know.', 'simple-tags'); ?>
                            <a class="advert-link" href="https://wordpress.org/support/plugin/simple-tags/" target="_blank">
                            <?php echo __('Request Support', 'simple-tags'); ?> 
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" class="linkIcon">
                                    <path
                                        d="M18.2 17c0 .7-.6 1.2-1.2 1.2H7c-.7 0-1.2-.6-1.2-1.2V7c0-.7.6-1.2 1.2-1.2h3.2V4.2H7C5.5 4.2 4.2 5.5 4.2 7v10c0 1.5 1.2 2.8 2.8 2.8h10c1.5 0 2.8-1.2 2.8-2.8v-3.6h-1.5V17zM14.9 3v1.5h3.7l-6.4 6.4 1.1 1.1 6.4-6.4v3.7h1.5V3h-6.3z"
                                    ></path>
                                </svg>
                            </a>
                        </p>
                        <p>
                        <?php echo __('Detailed documentation is also available on the plugin website.', 'simple-tags'); ?> 
                            <a class="advert-link" href="https://taxopress.com/docs/" target="_blank">
                            <?php echo __('View Knowledge Base', 'simple-tags'); ?> 
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
            <div class="upgrade-pro">
            <h3 class="hndle is-non-sortable" style="padding-left: 0;"><span><?php echo __('Schedule Auto Terms for your content', 'simple-tags'); ?></span></h3>
                <p style="line-height: 23px;"><?php echo __('TaxoPress Pro allows you to schedule the "Auto Terms to existing content" feature. This is helpful if you regularly import content into WordPress. TaxoPress Pro can run either daily or hourly and add terms to your imported content.', 'simple-tags'); ?></p>
            </div>
            </td>
        </tr>
        <?php 
    }

}
