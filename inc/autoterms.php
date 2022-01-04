<?php

class SimpleTags_Autoterms
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_autoterms') {
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
            __('Auto Terms', 'simple-tags'),
            __('Auto Terms', 'simple-tags'),
            'simple_tags',
            'st_autoterms',
            [
                $this,
                'page_manage_autoterms',
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
            'option'  => 'st_autoterms_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Autoterms_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_autoterms()
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
                <h1 class="wp-heading-inline"><?php _e('Auto Terms', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description">
                    <?php esc_html_e('Auto Terms can scan your content and automatically assign terms. For example, you have a term called "WordPress". Auto Terms can analyze your posts and when it finds the word "WordPress", it can add that term to your post.', 'simple-tags'); ?>
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
                    <?php $this->terms_table->search_box(__('Search Auto Terms', 'simple-tags'), 'term'); ?>
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
                $this->taxopress_manage_autoterms();
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
    public function taxopress_manage_autoterms()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $autoterms      = taxopress_get_autoterm_data();
        $autoterm_edit  = false;
        $autoterm_limit = false;

        if ('edit' === $tab) {


            $selected_autoterm = taxopress_get_current_autoterm();

            if ($selected_autoterm && array_key_exists($selected_autoterm, $autoterms)) {
                $current       = $autoterms[$selected_autoterm];
                $autoterm_edit = true;
            }

        }


        if (!isset($current['title']) && count($autoterms) > 0 && apply_filters('taxopress_autoterms_create_limit', true)) {
            $autoterm_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>


        <div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo __('Manage Auto Terms', 'simple-tags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="autoterms-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($autoterm_edit) {
                                            $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'autoterm_general';
                                            echo esc_html__('Edit Auto Terms', 'simple-tags');
                                            echo '<input type="hidden" name="edited_autoterm" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[ID]" value="' . $current['ID'] . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[active_tab]" class="taxopress-active-subtab" value="'.$active_tab.'" />';
                                        } else {
                                            $active_tab = 'autoterm_general';
                                            echo '<input type="hidden" name="taxopress_autoterm[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Auto Terms', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($autoterm_limit) {
                                            echo '<div class="st-taxonomy-content"><div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Auto Terms, please upgrade to TaxoPress Pro.',
                                                    'simple-tags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Auto Terms. You can create Auto Terms for any taxonomy.',
                                                    'simple-tags') . '

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li class="autoterm_general_tab <?php echo $active_tab === 'autoterm_general' ? 'active' : ''; ?>" data-content="autoterm_general">
                                                    <a href="#autoterm_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_terms_tab <?php echo $active_tab === 'autoterm_terms' ? 'active' : ''; ?>" data-content="autoterm_terms">
                                                    <a href="#autoterm_terms"><span><?php esc_html_e('Sources',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_options_tab <?php echo $active_tab === 'autoterm_options' ? 'active' : ''; ?>" data-content="autoterm_options">
                                                    <a href="#autoterm_options"><span><?php esc_html_e('Options',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_oldcontent_tab <?php echo $active_tab === 'autoterm_oldcontent' ? 'active' : ''; ?>" data-content="autoterm_oldcontent">
                                                    <a href="#autoterm_oldcontent"><span><?php esc_html_e('Existing Content',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li class="autoterm_schedule_tab <?php echo $active_tab === 'autoterm_schedule' ? 'active' : ''; ?>" data-content="autoterm_schedule">
                                                    <a href="#autoterm_schedule"><span><?php esc_html_e('Schedule',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table autoterm_general"
                                                       style="<?php echo $active_tab === 'autoterm_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                    echo $ui->get_tr_start();


                                                    echo $ui->get_th_start();
                                                    echo $ui->get_label('title', esc_html__('Title',
                                                            'simple-tags')) . $ui->get_required_span();
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    echo $ui->get_text_input([
                                                        'namearray'   => 'taxopress_autoterm',
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
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                        'required'   => true,
                                                        'selections' => $select,
                                                    ]);


                                                    $select             = [
                                                        'options' => [
                                                            [
                                                                'attr'    => 'post_content',
                                                                'text'    => esc_attr__('Post Content', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr' => 'post_title',
                                                                'text' => esc_attr__('Post Title', 'simple-tags')
                                                            ],
                                                            [
                                                                'attr' => 'posts',
                                                                'text' => esc_attr__('Post Content and Title',
                                                                    'simple-tags'),
                                                                'default' => 'true'
                                                            ]
                                                        ],
                                                    ];
                                                    $selected           = (isset($current) && isset($current['autoterm_from'])) ? taxopress_disp_boolean($current['autoterm_from']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_from'] : '';
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_from',
                                                        'labeltext'  => esc_html__('Find term in:',
                                                            'simple-tags'),
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




                                                <table class="form-table taxopress-table autoterm_terms"
                                                       style="<?php echo $active_tab === 'autoterm_terms' ? '' : 'display:none;'; ?>">
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
                                                    $selected           = (isset($current) && isset($current['autoterm_use_taxonomy'])) ? taxopress_disp_boolean($current['autoterm_use_taxonomy']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_use_taxonomy'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_use_taxonomy',
                                                        'class'      => 'autoterm_use_taxonomy',
                                                        'labeltext'  => esc_html__('Existing taxonomy terms', 'simple-tags'),
                                                        'aftertext'  => __('This will add existing terms from the taxonomy selected in the "General" tab.', 'simple-tags'),
                                                        'selections' => $select,
                                                        'required'    => false,
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
                                                    $selected           = (isset($current) && isset($current['autoterm_useall'])) ? taxopress_disp_boolean($current['autoterm_useall']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_useall'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useall',
                                                        'class'      => 'autoterm_useall autoterm-terms-to-use-field',
                                                        'labeltext'  => '',
                                                        'aftertext'  => __('Use all the terms in the selected taxonomy. Please test this option carefully as it can use significant server resources if you have many terms.', 'simple-tags'),
                                                        'selections' => $select,
                                                        'required'    => false,
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
                                                    $selected           = (isset($current) && isset($current['autoterm_useonly'])) ? taxopress_disp_boolean($current['autoterm_useonly']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_useonly'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useonly',
                                                        'class'      => 'autoterm_useonly autoterm-terms-to-use-field',
                                                        'labeltext'  => ' ',
                                                        'aftertext'  => __('Use only some terms in the selected taxonomy.', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);

                                                    $specific_terms = ( isset($current) && isset($current['specific_terms'])) ? taxopress_change_to_strings($current['specific_terms']) : '';
                                                    echo '<tr class="autoterm_useonly_options '. ($selected === 'false' ? 'st-hide-content' : '') .'" valign="top"><th scope="row"><label for=""></label></th><td>';
                                                    echo '<div class="auto-terms-to-use-error" style="display:none;"> '.__('Please choose an option for "Sources"', 'simple-tags').' </div>';

                                                            echo '<div class="st-autoterms-single-specific-term">
                                                            <input autocomplete="off" type="text" class="st-full-width specific_terms_input" name="specific_terms" maxlength="32" placeholder="'. esc_attr(__('Choose the terms to use.', 'simple-tags')) .'" value="'. esc_attr($specific_terms) .'">
                                                        </div>';

                                                    echo '</td></tr>';

                                                    do_action('taxopress_autoterms_after_autoterm_terms_to_use', $current);

                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table autoterm_options"
                                                       style="<?php echo $active_tab === 'autoterm_options' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autoterm',
                                                        'name'      => 'terms_limit',
                                                        'textvalue' => isset($current['terms_limit']) ? esc_attr($current['terms_limit']) : '',
                                                        'labeltext' => esc_html__('Auto Terms Limit',
                                                            'simple-tags'),
                                                        'helptext'  => __('Limit the number of generated Auto Terms. \'0\' for unlimited terms', 'simple-tags'),
                                                        'min'       => '0',
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
                                                    $selected           = (isset($current) && isset($current['autoterm_target'])) ? taxopress_disp_boolean($current['autoterm_target']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_target'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_target',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Target content', 'simple-tags'),
                                                        'aftertext'  => __('Only use Auto Terms on posts with no added terms.', 'simple-tags'),
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
                                                    $selected           = (isset($current) && isset($current['autoterm_word'])) ? taxopress_disp_boolean($current['autoterm_word']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_word'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_word',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Whole words', 'simple-tags'),
                                                        'aftertext'  => __('Only add terms when the word is an exact match. Do not make matches for partial words.', 'simple-tags'),
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
                                                    $selected           = (isset($current) && isset($current['autoterm_hash'])) ? taxopress_disp_boolean($current['autoterm_hash']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_hash'] : '';
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_hash',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Hashtags', 'simple-tags'),
                                                        'aftertext'  => __('Support hashtags symbols # in Auto Terms.', 'simple-tags'),
                                                        'selections' => $select,
                                                    ]);


                                                    $post_status_options = ['publish' => __('Add terms for published content.', 'simple-tags'), 'draft' => __('Add terms for unpublished content.', 'simple-tags')];

                                                    echo '<tr valign="top"><th scope="row"><label for="">' . esc_html__('Content statuses', 'simple-tags') . '</label>  <span class="required">*</span></th><td>';

                                                    echo '<div class="auto-terms-post-status-error" style="display:none;"> '.__('Please choose an option for "Content statuses"', 'simple-tags').' </div>';

                                                    $autoterms_post_status = (!empty($current['post_status'])) ? (array)$current['post_status'] : ['publish'];
                                                    foreach ($post_status_options as $key => $value) {
                                                        $checked_status = (in_array($key, $autoterms_post_status)) ? 'checked' : '';
                                                        echo '<input class="autoterm_post_status_'.$key.'" type="checkbox" name="post_status[]" value="'.$key.'" '.$checked_status.'> ' . $value . ' <br /><br />';

                                                    }
                                                   echo '</td></tr>';


                                                   echo $ui->get_tr_start();


                                                   echo $ui->get_th_start();
                                                   echo $ui->get_label('autoterm_exclude', esc_html__('Stop words', 'simple-tags'));
                                                   echo $ui->get_th_end() . $ui->get_td_start();

                                                   echo $ui->get_text_input([
                                                       'labeltext'   => esc_html__('Stop words', 'simple-tags'),
                                                       'namearray'   => 'taxopress_autoterm',
                                                       'name'        => 'autoterm_exclude',
                                                       'textvalue'   => isset($current['autoterm_exclude']) ? esc_attr($current['autoterm_exclude']) : '',
                                                       'maxlength'   => '',
                                                       'helptext'    => __('Choose terms to be excluded from auto terms.', 'simple-tags'),
                                                       'class'       => 'st-full-width auto-terms-stopwords',
                                                       'aftertext'   => '',
                                                       'required'    => false,
                                                       'placeholder' => false,
                                                       'wrap'        => false,
                                                   ]);

                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table autoterm_oldcontent"
                                                       style="<?php echo $active_tab === 'autoterm_oldcontent' ? '' : 'display:none;'; ?>">

                                                       <tr valign="top"><th scope="row"><label><?php echo __('Previous content', 'simple-tags'); ?></label></th>
                                                       <td>
                                                           <input type="submit" class="button taxopress-autoterm-all-content" value="<?php echo esc_attr(__('Add Auto Terms to all existing content', 'simple-tags')); ?>">
                                                           <span class="spinner taxopress-spinner"></span>

                                                           <p class="taxopress-field-description description">
                                                               <?php echo __('TaxoPress can add Auto Terms to existing content.', 'simple-tags'); ?>

                                                               <br /> <strong style="color:red;"><?php echo __('Please save all changes to your Auto Terms before using this feature.', 'simple-tags'); ?></strong>
                                                            </p>

                                                            <div class="auto-term-content-result-title"></div>

                                                            </div>

                                                            <ul class="auto-term-content-result"></ul>
                                                        </td></tr>

                                                </table>


                                                <table class="form-table taxopress-table autoterm_schedule"
                                                       style="<?php echo $active_tab === 'autoterm_schedule' ? '' : 'display:none;'; ?>">

                                                        <?php do_action('taxopress_autoterms_after_autoterm_schedule', $current); ?>

                                                </table>


                                            </div>


                                        <?php }//end new fields
                                        ?>


                                        <div class="clear"></div>


                                    </div>
                                </div>
                            </div>


                            <?php if ($autoterm_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message"><?php echo esc_attr__('You\'re using TaxoPress Free.
                                        The Pro version has more features and support.', 'simple-tags'); ?>
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank"><?php echo esc_attr__('Upgrade to Pro', 'simple-tags'); ?></a>
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
                        if (!$autoterm_limit) { ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_autoterm_nonce_action',
                                    'taxopress_addedit_autoterm_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autoterm-submit"
                                           name="autoterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Auto Terms',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-autoterm-submit"
                                           name="autoterm_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Auto Terms',
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
