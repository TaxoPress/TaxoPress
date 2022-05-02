<?php

class SimpleTags_Terms
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    // WP_List_Table object
    public $terms_table;

    /**
     * Constructor
     *
     * @return void
     * @author Olatechpro
     */
    public function __construct()
    {

        add_filter('set-screen-option', [__CLASS__, 'set_screen'], 10, 3);
        // Admin menu
        add_action('admin_menu', [$this, 'admin_menu']);
        // Javascript
        add_action('admin_enqueue_scripts', [__CLASS__, 'admin_enqueue_scripts'], 11);
        //support post type for terms query
        add_filter('terms_clauses', [__CLASS__, 'taxopress_terms_clauses'], 10, 3);

    }

    // Handle the post_type parameter given in get_terms function
    public static function taxopress_terms_clauses($clauses, $taxonomy, $args) {
        if (isset($_REQUEST['page']) && $_REQUEST['page'] === 'st_terms' && !empty($args['post_types']) && is_array($args['post_types']))	{
            global $wpdb;

            $post_types = array();

            foreach($args['post_types'] as $cpt)	{
                $post_types[] = "'".$cpt."'";
            }

            if(!empty($post_types))	{
                $clauses['fields'] = 'DISTINCT '.str_replace('tt.*', 'tt.term_taxonomy_id, tt.term_id, tt.taxonomy, tt.description, tt.parent', $clauses['fields']).', COUNT(t.term_id) AS count';
                $clauses['join'] .= ' INNER JOIN '.$wpdb->term_relationships.' AS r ON r.term_taxonomy_id = tt.term_taxonomy_id INNER JOIN '.$wpdb->posts.' AS p ON p.ID = r.object_id';
                $clauses['where'] .= ' AND p.post_type IN ('.implode(',', $post_types).')';
                $clauses['orderby'] = 'GROUP BY t.term_id '.$clauses['orderby'];
            }
        }
        return $clauses;
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_terms') {
            wp_enqueue_style('st-taxonomies-css');
        }
    }

    public static function set_screen($status, $option, $value)
    {
        return $value;
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
            esc_html__('Terms', 'simple-tags'),
            esc_html__('Terms', 'simple-tags'),
            'simple_tags',
            'st_terms',
            [
                $this,
                'page_manage_terms',
            ]
        );

        if (taxopress_is_screen_main_page()) {
            add_action("load-$hook", [$this, 'screen_option']);
        }
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';
        $args   = [
            'label'   => esc_html__('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_terms_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Taxopress_Terms_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_terms()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);

            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php esc_html_e('Terms', 'simple-tags'); ?></h1>
                <div class="taxopress-description">
                    <?php esc_html_e('This feature list all the terms on your site.', 'simple-tags'); ?>
                </div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(sanitize_text_field(wp_unslash($_REQUEST['s'])))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . esc_html__('Search results for &#8220;%s&#8221;',
                            'simple-tags') . '</span>', esc_html($search));
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(esc_html__('Search Terms', 'simple-tags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php esc_html__('Description here.', 'simple-tags') ?></p>
                        </div>
                    </div>


                </div>


            </div>

        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
    }

}
