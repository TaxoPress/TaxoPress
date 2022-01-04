<?php

class SimpleTags_Autolink
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_autolinks') {
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
            __('Auto Links', 'simple-tags'),
            __('Auto Links', 'simple-tags'),
            'simple_tags',
            'st_autolinks',
            [
                $this,
                'page_manage_autolinks',
            ]
        );

        if(taxopress_is_screen_main_page()){
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
            'label'   => __('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_autolinks_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Autolinks_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_autolinks()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);

        if (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php _e('Auto Links', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autolinks&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description"><?php esc_html_e('Auto Links can automatically create links to your defined terms. For example, if you have a term called “WordPress”, the Auto Links feature can find the word “WordPress” in your content and add links to the archive page for that term.', 'simple-tags'); ?></div>


                <?php
                if (isset($_REQUEST['s']) && $search = sanitize_text_field(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;',
                            'simple-tags') . '</span>', $search);
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Auto Links', 'simple-tags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo add_query_arg('', '') ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php __('Description here.', 'simple-tags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php } else {
            if ($_GET['add'] == 'new_item') {
                //add/edit taxonomy
                $this->taxopress_manage_autolinks();
                echo '<div>';
            }
        } ?>


        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
    }


    /**
     * Create our settings page output.
     *
     * @internal
     */
    public function taxopress_manage_autolinks()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $autolinks      = taxopress_get_autolink_data();
        $autolink_edit  = false;
        $autolink_limit = false;

        if ('edit' === $tab) {


            $selected_autolink = taxopress_get_current_autolink();

            if ($selected_autolink && array_key_exists($selected_autolink, $autolinks)) {
                $current       = $autolinks[$selected_autolink];
                $autolink_edit = true;
            }

        }


        if (!isset($current['title']) && count($autolinks) > 0 && apply_filters('taxopress_autolinks_create_limit',
                true)) {
            $autolink_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Auto Links', 'simple-tags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="autolinks-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($autolink_edit) {
                                            $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'autolink_general';
                                            echo esc_html__('Edit Auto Links', 'simple-tags');
                                            echo '<input type="hidden" name="edited_autolink" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autolink[ID]" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autolink[active_tab]" class="taxopress-active-subtab" value="'.$active_tab.'" />';
                                        } else {
                                            $active_tab = 'autolink_general';
                                            echo '<input type="hidden" name="taxopress_autolink[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Auto Links', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($autolink_limit) {
                                            echo '<div class="st-taxonomy-content"><div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Auto Links, please upgrade to TaxoPress Pro.',
                                                    'simple-tags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Auto Links. You can create Auto Links for any taxonomy.',
                                                    'simple-tags') . '

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li class="autolink_general_tab <?php echo $active_tab === 'autolink_general' ? 'active' : ''; ?>" data-content="autolink_general">
                                                    <a href="#autolink_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autolink_display_tab <?php echo $active_tab === 'autolink_display' ? 'active' : ''; ?>" data-content="autolink_display">
                                                    <a href="#autolink_display"><span><?php esc_html_e('Post Types',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autolink_control_tab <?php echo $active_tab === 'autolink_control' ? 'active' : ''; ?>" data-content="autolink_control">
                                                    <a href="#autolink_control"><span><?php esc_html_e('Control',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autolink_exceptions_tab <?php echo $active_tab === 'autolink_exceptions' ? 'active' : ''; ?>" data-content="autolink_exceptions">
                                                    <a href="#autolink_exceptions"><span><?php esc_html_e('Exceptions',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autolink_advanced_tab <?php echo $active_tab === 'autolink_advanced' ? 'active' : ''; ?>" data-content="autolink_advanced">
                                                    <a href="#autolink_advanced"><span><?php esc_html_e('Advanced',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table autolink_general"
                                                       style="<?php echo $active_tab === 'autolink_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                    echo $ui->get_tr_start();


                                                    echo $ui->get_th_start();
                                                    echo $ui->get_label('title', esc_html__('Title',
                                                            'simple-tags')) . $ui->get_required_span();
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    echo $ui->get_text_input([
                                                        'namearray'   => 'taxopress_autolink',
                                                        'name'        => 'title',
                                                        'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                        'maxlength'   => '32',
                                                        'helptext'    => '',
                                                        'required'    => true,
                                                        'placeholder' => false,
                                                        'wrap'        => false,
                                                    ]);


                                                    $options = [];
                                                    foreach (get_all_taxopress_taxonomies() as $_taxonomy) {
                                                        $_taxonomy = $_taxonomy->name;
                                                        $tax       = get_taxonomy($_taxonomy);
                                                        if (empty($tax->labels->name)) {
                                                            continue;
                                                        }
                                                        if ($tax->name === 'post_tag') {
                                                            $options[] = [
                                                                'attr'    => $tax->name,
                                                                'text'    => $tax->labels->name. ' ('.$tax->name.')',
                                                                'default' => 'true',
                                                            ];
                                                        } else {
                                                            $options[] = [
                                                                'attr' => $tax->name,
                                                                'text' => $tax->labels->name. ' ('.$tax->name.')',
                                                            ];
                                                        }
                                                    }

                                                    $select             = [
                                                        'options' => $options,
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['taxonomy']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['taxonomy'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                        'required'   => true,
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => 'none',
                                                                'text'    => esc_attr__('Use case of text in content',
                                                                    'simple-tags'),
                                                                'default' => 'true'
                                                            ],
                                                            [
                                                                'attr' => 'termcase',
                                                                'text' => esc_attr__('Use case of term', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr' => 'uppercase',
                                                                'text' => esc_attr__('All uppercase', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr' => 'lowercase',
                                                                'text' => esc_attr__('All lowercase', 'simple-tags')
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['autolink_case']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_case'] : '';
                                                    echo $ui->get_select_number_select([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_case',
                                                        'labeltext'  => esc_html__('Auto Link case',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => 'post_content',
                                                                'text'    => esc_attr__('Post Content', 'simple-tags'),
                                                                'default' => 'true'
                                                            ],
                                                            [
                                                                'attr' => 'post_title',
                                                                'text' => esc_attr__('Post Title', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr' => 'posts',
                                                                'text' => esc_attr__('Post Content and Title',
                                                                    'simple-tags')
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['autolink_display']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_display'] : '';
                                                    echo $ui->get_select_number_select([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_display',
                                                        'labeltext'  => esc_html__('Auto Link areas',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_title_attribute',
                                                        'textvalue' => isset($current['autolink_title_attribute']) ? esc_attr($current['autolink_title_attribute']) : 'Posts tagged with %s',
                                                        'labeltext' => esc_html__('Auto Link title attribute',
                                                            'simple-tags'),
                                                        'helptext'  => '',
                                                        'required'  => false,
                                                    ]);



                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['unattached_terms'])) ? taxopress_disp_boolean($current['unattached_terms']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['unattached_terms'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'unattached_terms',
                                                        'labeltext'  => esc_html__('Add links for unattached terms',
                                                            'simple-tags'),
                                                        'aftertext'  => __('By default, TaxoPress will only add Auto Links for terms that are attached to the post. If this box is checked, TaxoPress will add links for all terms.',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['ignore_attached'])) ? taxopress_disp_boolean($current['ignore_attached']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['ignore_attached'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'ignore_attached',
                                                        'labeltext'  => esc_html__('Don\'t add links for attached terms',
                                                            'simple-tags'),
                                                        'aftertext'  => __('Don\'t add Auto Links if the term is already attached to the post.',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table autolink_display"
                                                       style="<?php echo $active_tab === 'autolink_display' ? '' : 'display:none;'; ?>">
                                                    <?php


                                                    /**
                                                     * Filters the arguments for post types to list for taxonomy association.
                                                     *
                                                     *
                                                     * @param array $value Array of default arguments.
                                                     */
                                                    $args = apply_filters('taxopress_attach_post_types_to_taxonomy',
                                                        ['public' => true]);

                                                    // If they don't return an array, fall back to the original default. Don't need to check for empty, because empty array is default for $args param in get_post_types anyway.
                                                    if (!is_array($args)) {
                                                        $args = ['public' => true];
                                                    }
                                                    $output = 'objects'; // Or objects.

                                                    /**
                                                     * Filters the results returned to display for available post types for taxonomy.
                                                     *
                                                     * @param array $value Array of post type objects.
                                                     * @param array $args Array of arguments for the post type query.
                                                     * @param string $output The output type we want for the results.
                                                     */
                                                    $post_types = apply_filters('taxopress_get_post_types_for_taxonomies',
                                                        get_post_types($args, $output), $args, $output);

                                                    $term_auto_locations = [];
                                                    foreach ($post_types as $post_type) {
                                                        $term_auto_locations[$post_type->name] = $post_type->label;
                                                    }

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Enable this Auto Links instance for:',
                                                            'simple-tags') . '</label><br /><small style=" color: #646970;">' . esc_html__('TaxoPress will attempt to automatically insert Auto Links in this content. It may not be successful for all post types and layouts.',
                                                            'simple-tags') . '</small></th><td>
                                                    <table class="visbile-table">';
                                                    foreach ($term_auto_locations as $key => $value) {


                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['embedded']) && is_array($current['embedded']) && in_array($key,
                                                                    $current['embedded'], true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'embedded',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';


                                                    }
                                                    echo '</table></td></tr>';


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_control"
                                                       style="<?php echo $active_tab === 'autolink_control' ? '' : 'display:none;'; ?>">
                                                    <?php


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_usage_min',
                                                        'textvalue' => isset($current['autolink_usage_min']) ? esc_attr($current['autolink_usage_min']) : '1',
                                                        'labeltext' => esc_html__('Minimum term usage for Auto Links',
                                                            'simple-tags'),
                                                        'helptext'  => __('To be included in Auto Links, a term must be used at least this many times.',
                                                            'simple-tags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_usage_max',
                                                        'textvalue' => isset($current['autolink_usage_max']) ? esc_attr($current['autolink_usage_max']) : '10',
                                                        'labeltext' => esc_html__('Maximum number of links per post',
                                                            'simple-tags'),
                                                        'helptext'  => __('This setting determines the maximum number of Auto Links in one post.',
                                                            'simple-tags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_same_usage_max',
                                                        'textvalue' => isset($current['autolink_same_usage_max']) ? esc_attr($current['autolink_same_usage_max']) : '1',
                                                        'labeltext' => esc_html__('Maximum number of links for the same term',
                                                            'simple-tags'),
                                                        'helptext'  => __('This setting determines the maximum number of Auto Links for each term in one post.',
                                                            'simple-tags'),
                                                        'min'       => '1',
                                                        'required'  => true,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_min_char',
                                                        'textvalue' => isset($current['autolink_min_char']) ? esc_attr($current['autolink_min_char']) : '',
                                                        'labeltext' => esc_html__('Minimum character length for an Auto Link',
                                                            'simple-tags'),
                                                        'helptext'  => __('For example, \'4\' would only link terms that are of 4 characters or more in length.',
                                                            'simple-tags'),
                                                        'min'       => '0',
                                                        'required'  => false,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_max_char',
                                                        'textvalue' => isset($current['autolink_max_char']) ? esc_attr($current['autolink_max_char']) : '',
                                                        'labeltext' => esc_html__('Maximum character length for an Auto Link',
                                                            'simple-tags'),
                                                        'helptext'  => __('For example, \'4\' would only link terms that are of 4 characters or less in length.',
                                                            'simple-tags'),
                                                        'min'       => '0',
                                                        'required'  => false,
                                                    ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_exceptions"
                                                       style="<?php echo $active_tab === 'autolink_exceptions' ? '' : 'display:none;'; ?>">
                                                    <?php


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'auto_link_exclude',
                                                        'textvalue' => isset($current['auto_link_exclude']) ? esc_attr($current['auto_link_exclude']) : '',
                                                        'labeltext' => esc_html__('Exclude terms from Auto Links',
                                                            'simple-tags'),
                                                        'helptext'  => esc_html__('If you enter the terms "WordPress", "Website" the Auto Links feature will never replace these terms. Separate multiple entries with a comma.',
                                                            'simple-tags'),
                                                        'required'  => false,
                                                    ]);


                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'autolink_exclude_class',
                                                        'textvalue' => isset($current['autolink_exclude_class']) ? esc_attr($current['autolink_exclude_class']) : '',
                                                        'labeltext' => esc_html__('Prevent Auto Links inside classes or IDs',
                                                            'simple-tags'),
                                                        'helptext'  => esc_html__('Separate multiple entries with a comma. For example: .notag, #main-header',
                                                            'simple-tags'),
                                                        'required'  => false,
                                                    ]);

                                                    $html_exclusions = [
                                                        //headers
                                                        'h1'     => esc_attr__('H1', 'simple-tags'),
                                                        'h2'     => esc_attr__('H2', 'simple-tags'),
                                                        'h3'     => esc_attr__('H3', 'simple-tags'),
                                                        'h4'     => esc_attr__('H4', 'simple-tags'),
                                                        'h5'     => esc_attr__('H5', 'simple-tags'),
                                                        'h6'     => esc_attr__('H6', 'simple-tags'),
                                                        //html elements
                                                        'script' => esc_attr__('script', 'simple-tags'),
                                                        'style'  => esc_attr__('style', 'simple-tags'),
                                                        'pre'    => esc_attr__('pre', 'simple-tags'),
                                                        'code'   => esc_attr__('code', 'simple-tags'),
                                                    ];

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Prevent Auto Links inside elements',
                                                            'simple-tags') . '</label><br /><small style=" color: #646970;">' . esc_html__('Terms inside these html tags will not be auto link.',
                                                            'simple-tags') . '</small></th><td>
                                                    <table class="visbile-table st-html-exclusion-table">';
                                                    foreach ($html_exclusions as $key => $value) {

                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['html_exclusion']) && is_array($current['html_exclusion']) && in_array($key,
                                                                    $current['html_exclusion'],
                                                                    true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'html_exclusion',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => $key,
                                                            'labeldescription' => true,
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';

                                                        if ($key === 'h6') {
                                                            echo '<tr valign="top"><th style="padding: 0;" scope="row"><hr /></th><td style="padding: 0;"><hr /></td></tr>';
                                                        }


                                                    }
                                                    echo '</table></td></tr>';

                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autolink_advanced"
                                                       style="<?php echo $active_tab === 'autolink_advanced' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'link_class',
                                                        'class'     => '',
                                                        'textvalue' => isset($current['link_class']) ? esc_attr($current['link_class']) : '',
                                                        'labeltext' => esc_html__('Term link class', 'simple-tags'),
                                                        'helptext'  => '',
                                                        'required'  => false,
                                                    ]);


                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autolink',
                                                        'name'      => 'hook_priority',
                                                        'textvalue' => isset($current['hook_priority']) ? esc_attr($current['hook_priority']) : '12',
                                                        'labeltext' => esc_html__('Priority on the_content and the_title hook',
                                                            'simple-tags'),
                                                        'helptext'  => __('Change the priority of the Auto Links functions on the_content hook. This is useful for fixing conflicts with other plugins. Higher number means autolink will be executed only after hooks with lower number has been executed.',
                                                            'simple-tags'),
                                                        'min'       => '1',
                                                        'required'  => false,
                                                    ]);

                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => '0',
                                                                'text'    => esc_attr__('False', 'simple-tags'),
                                                            ],
                                                            [
                                                                'attr' => '1',
                                                                'text' => esc_attr__('True', 'simple-tags'),
                                                                'default' => 'true',
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autolink_dom'])) ? taxopress_disp_boolean($current['autolink_dom']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autolink_dom'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autolink',
                                                        'name'       => 'autolink_dom',
                                                        'labeltext'  => esc_html__('Use new Auto Links engine',
                                                            'simple-tags'),
                                                        'aftertext'  => __('The new Auto Links engine uses the DOMDocument PHP class and may offer better performance. If your server does not support this functionality, TaxoPress will use the usual engine.',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    ?>
                                                </table>


                                            </div>


                                        <?php }//end new fields
                                        ?>


                                        <div class="clear"></div>


                                    </div>
                                </div>
                            </div>


                            <?php if ($autolink_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message"><?php esc_html_e('You\'re using TaxoPress Free.
                                        The Pro version has more features and support.', 'simple-tags'); ?>
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank"><?php esc_html_e('Upgrade to Pro', 'simple-tags'); ?></a>
                                    </div>
                                </div>

                            <?php } ?>
                            <?php
                            /**
                             * Fires after the default fieldsets on the taxonomy screen.
                             *
                             * @param taxopress_admin_ui $ui Admin UI instance.
                             */
                            do_action('taxopress_taxonomy_after_fieldsets', $ui);
                            ?>

                        </div>
                    </div>


                </div>

                <div class="taxopress-right-sidebar">
                    <div class="taxopress-right-sidebar-wrapper" style="min-height: 205px;">


                        <?php
                        if (!$autolink_limit) { ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_autolink_nonce_action',
                                    'taxopress_addedit_autolink_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autolink-submit"
                                           name="autolink_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Auto Links',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autolink-submit"
                                           name="autolink_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Auto Links',
                                               'simple-tags')); ?>"/>
                                <?php } ?>


                                <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                       value="<?php echo esc_attr($tab); ?>"/>
                            </p>

                            <?php
                        }
                        ?>

                    </div>

                                                    <?php do_action('taxopress_admin_after_sidebar'); ?>
                </div>

                <div class="clear"></div>



            </form>

        </div><!-- End .wrap -->

        <div class="clear"></div>

        <?php # Modal Windows; ?>
<div class="remodal" data-remodal-id="taxopress-modal-alert"
     data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
     <div class="" style="color:red;"><?php echo __('Please complete the following required fields to save your changes:', 'simple-tags'); ?></div>
    <div id="taxopress-modal-alert-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo __('Okay', 'simple-tags'); ?></button>
</div>

<div class="remodal" data-remodal-id="taxopress-modal-confirm"
     data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
    <div id="taxopress-modal-confirm-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo __('No', 'simple-tags'); ?></button>
    <button data-remodal-action="confirm"
            class="remodal-confirm"><?php echo __('Yes', 'simple-tags'); ?></button>
</div>

        <?php
    }

}
