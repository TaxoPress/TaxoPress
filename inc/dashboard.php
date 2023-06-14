<?php

class SimpleTags_Dashboard
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    /**
     * Constructor
     *
     * @return void
     * @author Olatechpro
     */
    public function __construct()
    {
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
        // Javascript
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'], 11);
    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author Olatechpro
     */
    public static function admin_enqueue_scripts()
    {

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_dashboard') {
            //wp_enqueue_style('st-taxonomies-css');
            //wp_enqueue_script('admin-tags');
            //wp_enqueue_script('inline-edit-tax');
        }
    }

    /** Singleton instance */
    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Add WP admin menu for Tags
     *
     * @return void
     * @author Olatechpro
     */
    public function admin_menu()
    {
        $hook = add_submenu_page(
            self::MENU_SLUG,
            esc_html__('Dashboard', 'simple-tags'),
            esc_html__('Dashboard', 'simple-tags'),
            'simple_tags',
            'st_dashboard',
            [
                $this,
                'page_manage_dashboard',
            ]
        );
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Ojopaul
     */
    public function page_manage_dashboard()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        // Display message
        settings_errors(__CLASS__);
?>
        <h1 class="wp-heading-inline"><?php esc_html_e('Dashboard', 'simple-tags'); ?></h1>
        <div class="taxopress-description">
            <?php esc_html_e('This screen allows you to enable or disable TaxoPress features.', 'simple-tags'); ?>
        </div>

        <div class="wrap st_wrap tagcloudui st_mass_terms-page admin-settings">
            <form id="taxopress-capabilities-dashboard-form">
                <div class="taxopress-dashboard-settings-boxes">
                    <?php foreach (taxopress_dashboard_options() as $feature => $option) :
                        $feature_option_key = $option['option_key'];
                    ?>
                        <div class="taxopress-dashboard-settings-box">
                            <h3><?php echo esc_html($option['label']); ?></h3>
                            <div class="taxopress-dashboard-settings-description"><?php echo esc_html($option['description']); ?></div>
                            <div class="taxopress-dashboard-settings-control">
                                <div class="taxopress-switch-button">
                                    <label class="switch">
                                        <input type="checkbox" value="1" data-option_key="<?php echo esc_attr($feature_option_key); ?>" data-feature="<?php echo esc_attr($feature); ?>" <?php checked((int) SimpleTags_Plugin::get_option_value($feature_option_key), 1); ?> />
                                        <span class="slider"></span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </form>
        </div>

        <div class="taxopress-right-sidebar admin-settings-sidebar">
            <?php do_action('taxopress_admin_after_sidebar'); ?>
        </div>

<?php
    }
}
