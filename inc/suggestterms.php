<?php

class SimpleTags_SuggestTerms
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_suggestterms') {
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
            __('Suggest Terms', 'simple-tags'),
            __('Suggest Terms', 'simple-tags'),
            'simple_tags',
            'st_suggestterms',
            [
                $this,
                'page_manage_suggestterms',
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
            'label'   => __('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_suggestterms_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new SuggestTerms_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_suggestterms()
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
                <h1 class="wp-heading-inline"><?php _e('Suggest Terms', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_suggestterms&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description">
                    <?php esc_html_e('This feature helps when you\'re writing content. "Suggest Terms" can show a metabox where you can browse all your existing terms. "Suggest Terms" can also analyze your content and find new ideas for terms.',
                        'simple-tags'); ?>
                </div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(sanitize_text_field(wp_unslash($_REQUEST['s'])))) {
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
                    <?php $this->terms_table->search_box(__('Search Suggest Terms', 'simple-tags'), 'term'); ?>
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
                $this->taxopress_manage_suggestterms();
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
    public function taxopress_manage_suggestterms()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $suggestterms      = taxopress_get_suggestterm_data();
        $suggestterm_edit  = false;
        $suggestterm_limit = false;

        if ('edit' === $tab) {


            $selected_suggestterm = taxopress_get_current_suggestterm();

            if ($selected_suggestterm && array_key_exists($selected_suggestterm, $suggestterms)) {
                $current          = $suggestterms[$selected_suggestterm];
                $suggestterm_edit = true;
            }

        }


        if (!isset($current['title']) && count($suggestterms) > 0 && apply_filters('taxopress_suggestterms_create_limit',
                true)) {
            $suggestterm_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Suggest Terms', 'simple-tags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="suggestterms-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($suggestterm_edit) {
                                            $active_tab = (isset($current['active_tab']) && !empty(trim($current['active_tab']))) ? $current['active_tab'] : 'suggestterm_general';
                                            echo esc_html__('Edit Suggest Terms', 'simple-tags');
                                            echo '<input type="hidden" name="edited_suggestterm" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_suggestterm[ID]" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_suggestterm[active_tab]" class="taxopress-active-subtab" value="' . $active_tab . '" />';
                                        } else {
                                            $active_tab = 'suggestterm_general';
                                            echo '<input type="hidden" name="taxopress_suggestterm[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Suggest Terms', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($suggestterm_limit) {
                                            echo '<div class="st-taxonomy-content"><div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Suggest Terms, please upgrade to TaxoPress Pro.',
                                                    'simple-tags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Suggest Terms. You can create Suggest Terms for any taxonomy.',
                                                    'simple-tags') . '

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li class="suggestterm_general_tab <?php echo $active_tab === 'suggestterm_general' ? 'active' : ''; ?>"
                                                    data-content="suggestterm_general">
                                                    <a href="#suggestterm_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="suggestterm_external_tab <?php echo $active_tab === 'suggestterm_external' ? 'active' : ''; ?>"
                                                    data-content="suggestterm_external">
                                                    <a href="#suggestterm_external"><span><?php esc_html_e('Automatic Term Suggestions',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="suggestterm_local_tab <?php echo $active_tab === 'suggestterm_local' ? 'active' : ''; ?>"
                                                    data-content="suggestterm_local">
                                                    <a href="#suggestterm_local"><span><?php esc_html_e('Show Existing Terms',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table suggestterm_general"
                                                       style="<?php echo $active_tab === 'suggestterm_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                    echo $ui->get_tr_start();


                                                    echo $ui->get_th_start();
                                                    echo $ui->get_label('title', esc_html__('Title',
                                                            'simple-tags')) . $ui->get_required_span();
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    echo $ui->get_text_input([
                                                        'namearray'   => 'taxopress_suggestterm',
                                                        'name'        => 'title',
                                                        'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                        'maxlength'   => '32',
                                                        'helptext'    => '',
                                                        'required'    => true,
                                                        'placeholder' => false,
                                                        'wrap'        => false,
                                                    ]);


                                                    $options = [];
                                                    foreach (get_all_taxopress_public_taxonomies() as $_taxonomy) {
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
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Default Taxonomy', 'simple-tags'),
                                                        'required'   => true,
                                                        'selections' => $select,
                                                    ]);


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

                                                    echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Post Types',
                                                            'simple-tags') . '</label> </th><td>
                                                    <table class="visbile-table">';
                                                    foreach ($term_auto_locations as $key => $value) {


                                                        echo '<tr valign="top"><th scope="row"><label for="' . $key . '">' . $value . '</label></th><td>';

                                                        echo $ui->get_check_input([
                                                            'checkvalue' => $key,
                                                            'checked'    => (!empty($current['post_types']) && is_array($current['post_types']) && in_array($key,
                                                                    $current['post_types'], true)) ? 'true' : 'false',
                                                            'name'       => $key,
                                                            'namearray'  => 'post_types',
                                                            'textvalue'  => $key,
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';


                                                    }
                                                    echo '</table></td></tr>';


                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table suggestterm_local"
                                                       style="<?php echo $active_tab === 'suggestterm_local' ? '' : 'display:none;'; ?>">

                                                   <tr valign="top"><td style="padding-left: 0;" colspan="2"><?php echo esc_html__('This feature shows a metabox where you can browse all your existing terms.', 'simple-tags'); ?></td></tr>

                                                    <?php

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
                                                    $selected           = (isset($current) && isset($current['disable_local'])) ? taxopress_disp_boolean($current['disable_local']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['disable_local'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'disable_local',
                                                        'labeltext'  => esc_html__('Disable "Show existing terms" feature',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    if (!isset($current)) {
                                                        $maximum_terms = 100;
                                                    } else {
                                                        $maximum_terms = isset($current['number']) ? esc_attr($current['number']) : '0';
                                                    }

                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_suggestterm',
                                                        'name'      => 'number',
                                                        'textvalue' => $maximum_terms,
                                                        'labeltext' => esc_html__('Maximum terms', 'simple-tags'),
                                                        'helptext'  => 'Set (0) for no limit.',
                                                        'min'       => '0',
                                                        'required'  => true,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr' => 'name',
                                                                'text' => esc_attr__('Name', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr'    => 'count',
                                                                'text'    => esc_attr__('Counter', 'simple-tags'),
                                                                'default' => 'true'
                                                            ],
                                                            [
                                                                'attr' => 'random',
                                                                'text' => esc_attr__('Random', 'simple-tags')
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['orderby']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['orderby'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'orderby',
                                                        'labeltext'  => esc_html__('Method for choosing terms',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr' => 'asc',
                                                                'text' => esc_attr__('Ascending', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr'    => 'desc',
                                                                'text'    => esc_attr__('Descending', 'simple-tags'),
                                                                'default' => 'true'
                                                            ],
                                                        ],
                                                    ];
                                                    $selected           = isset($current) ? taxopress_disp_boolean($current['order']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['order'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'order',
                                                        'labeltext'  => esc_html__('Ordering for choosing terms',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table suggestterm_external"
                                                       style="<?php echo $active_tab === 'suggestterm_external' ? '' : 'display:none;'; ?>">


                                                   <tr class="suggestterm_external_description" valign="top"><td style="padding-left: 0;" colspan="2"><?php echo esc_html__('This feature can analyze your content and find new ideas for terms.', 'simple-tags'); ?></td></tr>

                                                    <?php



                                                    if(!isset($current)){
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
                                                    }else{
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

                                                    }

                                                    $selected           = (isset($current) && isset($current['suggest_term_use_local'])) ? taxopress_disp_boolean($current['suggest_term_use_local']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['suggest_term_use_local'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'suggest_term_use_local',
                                                        'class'      => 'suggest_term_use_local',
                                                        'labeltext'  => esc_html__('Suggest existing terms on your site',
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
                                                    $selected           = (isset($current) && isset($current['suggest_term_use_dandelion'])) ? taxopress_disp_boolean($current['suggest_term_use_dandelion']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['suggest_term_use_dandelion'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'suggest_term_use_dandelion',
                                                        'class'      => 'suggest_term_use_dandelion',
                                                        'labeltext'  => esc_html__('Suggest new terms from the Dandelion service',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_suggestterm',
                                                        'name'      => 'terms_datatxt_access_token',
                                                        'class'     => 'terms_datatxt_access_token',
                                                        'textvalue' => isset($current['terms_datatxt_access_token']) ? esc_attr($current['terms_datatxt_access_token']) : '',
                                                        'labeltext' => esc_html__('Dandelion API token', 'simple-tags'),
                                                        'helptext'  => __('You need an API key to use Dandelion to suggest terms. <br /> <a href="https://taxopress.com/docs/dandelion-api/">Click here for documentation.</a>',
                                                            'simple-tags'),
                                                        'required'  => false,
                                                    ]);

                                                    if (!isset($current)) {
                                                        $terms_datatxt_min_confidence = '0.6';
                                                    } else {
                                                        $terms_datatxt_min_confidence = isset($current['terms_datatxt_min_confidence']) ? esc_attr($current['terms_datatxt_min_confidence']) : '0';
                                                    }

                                                    echo $ui->get_number_input([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'terms_datatxt_min_confidence',
                                                        'class'      => 'terms_datatxt_min_confidence',
                                                        'textvalue'  => $terms_datatxt_min_confidence,
                                                        'labeltext'  => esc_html__('Dandelion API confidence value',
                                                            'simple-tags'),
                                                        'helptext'   => __('Choose a value between 0 and 1. A high value such as 0.8 will provide a few, accurate suggestions. A low value such as 0.2 will produce more suggestions, but they may be less accurate.',
                                                            'simple-tags'),
                                                        'min'        => '0',
                                                        'max'        => '1',
                                                        'other_attr' => 'step=".1"',
                                                        'required'   => false,
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
                                                    $selected           = (isset($current) && isset($current['suggest_term_use_opencalais'])) ? taxopress_disp_boolean($current['suggest_term_use_opencalais']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['suggest_term_use_opencalais'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_suggestterm',
                                                        'name'       => 'suggest_term_use_opencalais',
                                                        'class'      => 'suggest_term_use_opencalais',
                                                        'labeltext'  => esc_html__('Suggest new terms from the Open Calais service',
                                                            'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    echo $ui->get_text_input([
                                                        'namearray' => 'taxopress_suggestterm',
                                                        'name'      => 'terms_opencalais_key',
                                                        'class'     => 'terms_opencalais_key',
                                                        'textvalue' => isset($current['terms_opencalais_key']) ? esc_attr($current['terms_opencalais_key']) : '',
                                                        'labeltext' => esc_html__('OpenCalais API Key', 'simple-tags'),
                                                        'helptext'  => __('You need an API key to use OpenCalais to suggest terms. <br /> <a href="https://taxopress.com/docs/opencalais/">Click here for documentation.</a>',
                                                            'simple-tags'),
                                                        'required'  => false,
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


                            <?php if ($suggestterm_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message"><?php echo esc_html__('You\'re using TaxoPress Free.
                                        The Pro version has more features and support.',
                                                            'simple-tags'); ?>
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank"><?php echo esc_html__('Upgrade to Pro',
                                                            'simple-tags'); ?></a>
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
                        if (!$suggestterm_limit) { ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_suggestterm_nonce_action',
                                    'taxopress_addedit_suggestterm_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit"
                                           class="button-primary taxopress-taxonomy-submit taxopress-suggestterm-submit"
                                           name="suggestterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Suggest Terms',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit"
                                           class="button-primary taxopress-taxonomy-submit taxopress-suggestterm-submit"
                                           name="suggestterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Suggest Terms',
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

        <?php # Modal Windows;
        ?>
        <div class="remodal" data-remodal-id="taxopress-modal-alert"
             data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
            <div class=""
                 style="color:red;"><?php echo __('Please complete the following required fields to save your changes:',
                    'simple-tags'); ?></div>
            <div id="taxopress-modal-alert-content"></div>
            <br>
            <button data-remodal-action="cancel" class="remodal-cancel"><?php echo __('Okay',
                    'simple-tags'); ?></button>
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
