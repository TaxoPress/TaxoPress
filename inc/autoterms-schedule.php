<?php

class SimpleTags_Autoterms_Schedule {

    const MENU_SLUG = 'st_options';

    public function __construct() {
        if ( ! class_exists( 'TaxoPress_Pro_Auto_Terms_Schedule' ) ) {
            add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        }
    }

    public static function get_instance() {
        static $instance = null;
        if ( null === $instance ) {
            $instance = new self();
        }
        return $instance;
    }

    public function admin_menu() {
        add_submenu_page(
            self::MENU_SLUG,
            esc_html__( 'Auto Terms Schedule', 'simple-tags' ),
            esc_html__( 'Auto Terms Schedule', 'simple-tags' ),
            'simple_tags',
            'st_autoterms_schedule',
            [ $this, 'page_autoterms_schedule_promo' ]
        );
    }

    public function page_autoterms_schedule_promo() {
        ?>
        <div class="taxopress-block-wrap">
            <div class="wrap st_wrap taxopress-split-wrap taxopress-autoterm-schedule admin-settings">
                <h1><?php esc_html_e( 'Schedule', 'simple-tags' ); ?></h1>

                <div class="taxopress-description">
                    <?php esc_html_e(
                        'This feature allows you to run the Auto Terms feature on a schedule. In TaxoPress Pro, you can choose which Auto Terms to run, how often to run them, and how many posts to process in each batch.',
                        'simple-tags'
                    ); ?>
                </div>

                <div class="wp-clearfix"></div>

                <div class=" schedule-autoterms-promo st-taxonomy-content promo-box-area">
                    <div class="taxopress-content-promo-box advertisement-box-content postbox postbox upgrade-pro">
                        <div class="postbox-header">
                            <h3 class="advertisement-box-header hndle is-non-sortable">
                                <span><?php esc_html_e( 'Schedule Auto Terms', 'simple-tags' ); ?></span>
                            </h3>
                        </div>

                        <div class="inside-content">
                            <h3>
                                <?php esc_html_e(
                                    'Auto Terms Schedule is available in TaxoPress Pro.',
                                    'simple-tags'
                                ); ?>
                            </h3>
                            <h4>
                                <?php esc_html_e(
                                    'Automatically run Auto Terms hourly or daily on your imported or updated content, with batch limits and date filters.',
                                    'simple-tags'
                                ); ?>
                            </h4>

                            <div class="upgrade-btn">
                                <a href="https://taxopress.com/taxopress/" target="__blank">
                                    <?php esc_html_e( 'Upgrade to Pro', 'simple-tags' ); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php SimpleTags_Admin::printAdminFooter(); ?>
            </div>

            <div class="taxopress-right-sidebar admin-settings-sidebar">
                <?php do_action( 'taxopress_admin_after_sidebar' ); ?>
            </div>

        </div>
        <?php
    }
}