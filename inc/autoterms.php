<?php

class SimpleTags_Autoterms
{

    const MENU_SLUG = 'st_options';

    // class instance
    static $instance;

    // WP_List_Table object
    public $terms_table;

    // WP_List_Table object
    public $logs_table;

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
            esc_html__('Auto Terms', 'simple-tags'),
            esc_html__('Auto Terms', 'simple-tags'),
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

    public function autoterms_logs_count(){

        $count = taxopress_autoterms_logs_data(1)['counts'];
        return '<span class="update-plugins count-'. (int)$count .'"><span class="plugin-count">('. number_format_i18n($count) .')</span></span>';
    }

    /**
     * Screen options
     */
    public function screen_option()
    {

        $option = 'per_page';

        if (isset($_GET['tab']) && $_GET['tab'] === 'logs') {
            $args   = [
            'label'   => esc_html__('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_autoterms_logs_per_page'
            ];
            $this->logs_table = new Autoterms_Logs();
        }else{
            $args   = [
            'label'   => esc_html__('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_autoterms_per_page'
            ];
            $this->terms_table = new Autoterms_List();
        }

        add_screen_option($option, $args);
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

        if (isset($_GET['tab']) && $_GET['tab'] === 'logs') {
            //autoterms logs

            $delete_all_link = add_query_arg([
                'page'                   => 'st_autoterms',
                'tab'                    => 'logs',
                'action'                 => 'taxopress-delete-autoterm-logs',
                '_wpnonce'               => wp_create_nonce('autoterm-action-request-nonce')
                ],
                admin_url('admin.php')
            );

            $enable_log_link = add_query_arg([
                'page'                   => 'st_autoterms',
                'tab'                    => 'logs',
                'action'                 => 'taxopress-enable-autoterm-logs',
                '_wpnonce'               => wp_create_nonce('autoterm-action-request-nonce')
                ],
                admin_url('admin.php')
            );

            $disable_log_link = add_query_arg([
                'page'                   => 'st_autoterms',
                'tab'                    => 'logs',
                'action'                 => 'taxopress-disable-autoterm-logs',
                '_wpnonce'               => wp_create_nonce('autoterm-action-request-nonce')
                ],
                admin_url('admin.php')
            );
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php esc_html_e('Auto Terms Logs', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms')); ?>"
                   class="page-title-action"><?php esc_html_e('Auto Terms List', 'simple-tags'); ?></a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New Auto Terms', 'simple-tags'); ?></a>

                   <?php if(get_option('taxopress_autoterms_logs_disabled')){ ?>
                    <a href="<?php echo esc_url($enable_log_link); ?>" class="page-title-action taxopress-logs-tablenav-enable-logs"><?php esc_html_e('Enable Logs', 'simple-tags'); ?></a>
                <?php } else { ?>
                    <a href="<?php echo esc_url($disable_log_link); ?>" class="page-title-action taxopress-logs-tablenav-disable-logs" onclick="return confirm('<?php esc_attr_e('Are you sure you want to disable logs?', 'simple-tags'); ?>')"><?php esc_html_e('Disable Logs', 'simple-tags'); ?></a>
                <?php } ?>

                <a href="<?php echo esc_url($delete_all_link); ?>" class="page-title-action taxopress-logs-tablenav-purge-logs"  onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete all logs?', 'simple-tags'); ?>')"><?php esc_html_e('Delete All Logs', 'simple-tags'); ?></a>

                <div class="taxopress-description">
                    <?php esc_html_e('Auto Terms logs history.', 'simple-tags'); ?>
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
                $this->logs_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->logs_table->search_box(esc_html__('Search Auto Terms Logs', 'simple-tags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo esc_url(add_query_arg('', '')); ?>" method="post">
                            <?php $this->logs_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php esc_html__('Description here.', 'simple-tags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php }elseif (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-taxonomies-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php esc_html_e('Auto Terms List', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New Auto Terms', 'simple-tags'); ?></a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&tab=logs')); ?>"
                   class="page-title-action"><?php esc_html_e('Logs', 'simple-tags'); ?> <?php echo esc_html($this->autoterms_logs_count()); ?></a>

                <div class="taxopress-description">
                    <?php esc_html_e('Auto Terms can scan your content and automatically assign new and existing terms.', 'simple-tags'); ?>
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
                    <?php $this->terms_table->search_box(esc_html__('Search Auto Terms', 'simple-tags'), 'term'); ?>
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
            <h1><?php echo esc_html__('Manage Auto Terms', 'simple-tags'); ?>

            <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms')); ?>"
                   class="page-title-action"><?php esc_html_e('Auto Terms List', 'simple-tags'); ?></a>

                <a href="<?php echo esc_url(admin_url('admin.php?page=st_autoterms&tab=logs')); ?>"
                   class="page-title-action"><?php esc_html_e('Logs', 'simple-tags'); ?> <?php echo esc_html($this->autoterms_logs_count()); ?></a>

                   </h1>

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
                                            echo '<input type="hidden" name="edited_autoterm" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[ID]" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_autoterm[active_tab]" class="taxopress-active-subtab" value="'.esc_attr($active_tab).'" />';
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

                                            <h2 style="margin-bottom: 5px;">' . esc_html__('To create more Auto Terms, please upgrade to TaxoPress Pro.',
                                                    'simple-tags') . '</h2>
                                            ' . esc_html__('With TaxoPress Pro, you can create unlimited Auto Terms. You can create Auto Terms for any taxonomy.',
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

                                                <li class="autoterm_advanced_tab <?php echo $active_tab === 'autoterm_advanced' ? 'active' : ''; ?>" data-content="autoterm_advanced">
                                                    <a href="#autoterm_advanced"><span><?php esc_html_e('Advanced',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table autoterm_general"
                                                       style="<?php echo $active_tab === 'autoterm_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_tr_start();

                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_th_start();
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_label('title', esc_html__('Title', 'simple-tags')) . $ui->get_required_span();
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_th_end() . $ui->get_td_start();

                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
                                                        'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                        'required'   => true,
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input_main([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_from',
                                                        'labeltext'  => esc_html__('Find term in:',
                                                            'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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


                                                        echo '<tr valign="top"><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($value) . '</label></th><td>';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_check_input([
                                                            'checkvalue' => esc_attr($key),
                                                            'checked'    => (!empty($current['post_types']) && is_array($current['post_types']) && in_array($key, $current['post_types'], true)) ? 'true' : 'false',
                                                            'name'       => esc_attr($key),
                                                            'namearray'  => 'post_types',
                                                            'textvalue'  => esc_attr($key),
                                                            'labeltext'  => "",
                                                            'wrap'       => false,
                                                        ]);

                                                        echo '</td></tr>';


                                                    }
                                                    echo '</table></td></tr>';

                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>




                                                <table class="form-table taxopress-table autoterm_terms"
                                                       style="<?php echo $active_tab === 'autoterm_terms' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                    if($autoterm_edit){
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
                                                }else{
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

                                                }
                                                    $selected           = (isset($current) && isset($current['autoterm_use_taxonomy'])) ? taxopress_disp_boolean($current['autoterm_use_taxonomy']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_use_taxonomy'] : '';
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_use_taxonomy',
                                                        'class'      => 'autoterm_use_taxonomy',
                                                        'labeltext'  => esc_html__('Existing taxonomy terms', 'simple-tags'),
                                                        'aftertext'  => esc_html__('This will add existing terms from the taxonomy selected in the "General" tab.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        'required'    => true,
                                                    ]);



                                                    if($autoterm_edit){
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
                                                    }else{
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
    
                                                    }

                                                    $selected           = (isset($current) && isset($current['autoterm_useall'])) ? taxopress_disp_boolean($current['autoterm_useall']) : '';
                                                    $select['selected'] = !empty($selected) ? $current['autoterm_useall'] : '';
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useall',
                                                        'class'      => 'autoterm_useall autoterm-terms-to-use-field',
                                                        'labeltext'  => '',
                                                        'aftertext'  => esc_html__('Use all the terms in the selected taxonomy. Please test this option carefully as it can use significant server resources if you have many terms.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_useonly',
                                                        'class'      => 'autoterm_useonly autoterm-terms-to-use-field',
                                                        'labeltext'  => ' ',
                                                        'aftertext'  => esc_html__('Use only some terms in the selected taxonomy.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    ]);

                                                    $specific_terms = ( isset($current) && isset($current['specific_terms'])) ? taxopress_change_to_strings($current['specific_terms']) : '';
                                                    echo '<tr class="autoterm_useonly_options '. ($selected === 'false' ? 'st-hide-content' : '') .'" valign="top"><th scope="row"><label for=""></label></th><td>';
                                                    echo '<div class="auto-terms-to-use-error" style="display:none;"> '.esc_html__('Please choose an option for "Sources"', 'simple-tags').' </div>';

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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_number_input([
                                                        'namearray' => 'taxopress_autoterm',
                                                        'name'      => 'terms_limit',
                                                        'textvalue' => isset($current['terms_limit']) ? esc_attr($current['terms_limit']) : '',
                                                        'labeltext' => esc_html__('Auto Terms Limit',
                                                            'simple-tags'),
                                                        'helptext'  => esc_html__('Limit the number of generated Auto Terms. \'0\' for unlimited terms', 'simple-tags'),
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_target',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Target content', 'simple-tags'),
                                                        'aftertext'  => esc_html__('Only use Auto Terms on posts with no added terms.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_word',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Whole words', 'simple-tags'),
                                                        'aftertext'  => esc_html__('Only add terms when the word is an exact match. Do not make matches for partial words.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_select_checkbox_input([
                                                        'namearray'  => 'taxopress_autoterm',
                                                        'name'       => 'autoterm_hash',
                                                        'class'      => '',
                                                        'labeltext'  => esc_html__('Hashtags', 'simple-tags'),
                                                        'aftertext'  => esc_html__('Support hashtags symbols # in Auto Terms.', 'simple-tags'),
                                                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    ]);


                                                    $post_status_options = ['publish' => esc_html__('Add terms for published content.', 'simple-tags'), 'draft' => esc_html__('Add terms for unpublished content.', 'simple-tags')];

                                                    echo '<tr valign="top"><th scope="row"><label for="">' . esc_html__('Content statuses', 'simple-tags') . '</label>  <span class="required">*</span></th><td>';

                                                    echo '<div class="auto-terms-post-status-error" style="display:none;"> '.esc_html__('Please choose an option for "Content statuses"', 'simple-tags').' </div>';

                                                    $autoterms_post_status = (!empty($current['post_status'])) ? (array)$current['post_status'] : ['publish'];
                                                    foreach ($post_status_options as $key => $value) {
                                                        $checked_status = (in_array($key, $autoterms_post_status)) ? 'checked' : '';
                                                        echo '<input class="autoterm_post_status_'.esc_attr($key).'" type="checkbox" name="post_status[]" value="'.esc_attr($key).'" '.esc_attr($checked_status).'> ' . esc_html($value) . ' <br /><br />';

                                                    }
                                                   echo '</td></tr>';

                                                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                   echo $ui->get_tr_start();

                                                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                   echo $ui->get_th_start();
                                                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                   echo $ui->get_label('autoterm_exclude', esc_html__('Stop words', 'simple-tags'));
                                                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                   echo $ui->get_th_end() . $ui->get_td_start();

                                                   // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                   echo $ui->get_text_input([
                                                       'labeltext'   => esc_html__('Stop words', 'simple-tags'),
                                                       'namearray'   => 'taxopress_autoterm',
                                                       'name'        => 'autoterm_exclude',
                                                       'textvalue'   => isset($current['autoterm_exclude']) ? esc_attr($current['autoterm_exclude']) : '',
                                                       'maxlength'   => '',
                                                       'helptext'    => esc_html__('Choose terms to be excluded from auto terms.', 'simple-tags'),
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
                                                            $selected           = (isset($current) && isset($current['autoterm_existing_content_exclude'])) ? taxopress_disp_boolean($current['autoterm_existing_content_exclude']) : '';
                                                            $select['selected'] = !empty($selected) ? $current['autoterm_existing_content_exclude'] : '';
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_select_checkbox_input([
                                                                'namearray'  => 'taxopress_autoterm',
                                                                'name'       => 'autoterm_existing_content_exclude',
                                                                'class'      => '',
                                                                'labeltext'  => esc_html__('Exclude previously analyzed content', 'simple-tags'),
                                                                'aftertext'  => esc_html__('This enables you to skip posts that have already been analyzed by the Existing Content feature.', 'simple-tags'),
                                                                'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            ]);

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_number_input([
                                                                'namearray' => 'taxopress_autoterm',
                                                                'name'      => 'existing_terms_batches',
                                                                'textvalue' => isset($current['existing_terms_batches']) ? esc_attr($current['existing_terms_batches']) : '20',
                                                                'labeltext' => esc_html__('Limit per batches',
                                                                    'simple-tags'),
                                                                'helptext'  => esc_html__('This enables you to add Auto Terms to existing content in batches. If you have a lot of existing content, set this to a lower number to avoid timeouts.', 'simple-tags'),
                                                                'min'       => '1',
                                                                'required'  => true,
                                                            ]);

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_number_input([
                                                                'namearray' => 'taxopress_autoterm',
                                                                'name'      => 'existing_terms_sleep',
                                                                'textvalue' => isset($current['existing_terms_sleep']) ? esc_attr($current['existing_terms_sleep']) : '10',
                                                                'labeltext' => esc_html__('Batches wait time', 'simple-tags'),
                                                                'helptext'  => esc_html__('This is the wait time (in seconds) between processing batches of Auto Terms. If you have a lot of existing content, set this to a higher number to avoid timeouts.', 'simple-tags'),
                                                                'min'       => '0',
                                                                'required'  => true,
                                                            ]);

                                                            $select             = [
                                                                'options' => [
                                                                    [
                                                                        'attr' => '1',
                                                                        'text' => esc_attr__('24 hours ago', 'simple-tags')
                                                                    ],
                                                                    [
                                                                        'attr' => '7',
                                                                        'text' => esc_attr__('7 days ago', 'simple-tags')
                                                                    ],
                                                                    [
                                                                        'attr' => '14',
                                                                        'text' => esc_attr__('2 weeks ago', 'simple-tags')
                                                                    ],
                                                                    [
                                                                        'attr' => '30',
                                                                        'text' => esc_attr__('1 month ago', 'simple-tags'),
                                                                        'default' => 'true'
                                                                    ],
                                                                    [
                                                                        'attr' => '180',
                                                                        'text' => esc_attr__('6 months ago', 'simple-tags')
                                                                    ],
                                                                    [
                                                                        'attr' => '365',
                                                                        'text' => esc_attr__('1 year ago', 'simple-tags')
                                                                    ],
                                                                    [
                                                                        'attr'    => '0',
                                                                        'text'    => esc_attr__('No limit', 'simple-tags')
                                                                    ],
                                                                ],
                                                            ];

                                                            if(isset($current) && is_array($current)){
                                                                $select             = [
                                                                    'options' => [
                                                                        [
                                                                            'attr' => '1',
                                                                            'text' => esc_attr__('24 hours ago', 'simple-tags')
                                                                        ],
                                                                        [
                                                                            'attr' => '7',
                                                                            'text' => esc_attr__('7 days ago', 'simple-tags')
                                                                        ],
                                                                        [
                                                                            'attr' => '14',
                                                                            'text' => esc_attr__('2 weeks ago', 'simple-tags')
                                                                        ],
                                                                        [
                                                                            'attr' => '30',
                                                                            'text' => esc_attr__('1 month ago', 'simple-tags'),
                                                                        ],
                                                                        [
                                                                            'attr' => '180',
                                                                            'text' => esc_attr__('6 months ago', 'simple-tags')
                                                                        ],
                                                                        [
                                                                            'attr' => '365',
                                                                            'text' => esc_attr__('1 year ago', 'simple-tags')
                                                                        ],
                                                                        [
                                                                            'attr'    => '0',
                                                                            'text'    => esc_attr__('No limit', 'simple-tags'),
                                                                            'default' => 'true'
                                                                        ],
                                                                    ],
                                                                ];
                                                            }

                                                            $selected           = (isset($current) && isset($current['limit_days'])) ? taxopress_disp_boolean($current['limit_days']) : '';
                                                            $select['selected'] = !empty($selected) ? $current['limit_days'] : '';
                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_select_number_select([
                                                                'namearray'  => 'taxopress_autoterm',
                                                                'name'       => 'limit_days',
                                                                'labeltext'  => esc_html__('Limit Auto Terms, based on published date',
                                                                    'simple-tags'),
                                                                    'aftertext'  => esc_html__('This setting allows you to add Auto Terms only to recent content.', 'simple-tags'),
                                                                'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            ]);
                                                        ?>

                                                       <tr valign="top"><th scope="row"><label><?php echo esc_html__('Existing Content', 'simple-tags'); ?></label></th>

                                                       <td>
                                                           <input type="submit" class="button taxopress-autoterm-all-content" value="<?php echo esc_attr(__('Add Auto Terms to existing content', 'simple-tags')); ?>">
                                                           <span class="spinner taxopress-spinner"></span>

                                                           <p class="taxopress-field-description description">
                                                               <?php echo esc_html__('Click the button to add Auto Terms to existing content.', 'simple-tags'); ?>
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


                                                <table class="form-table taxopress-table autoterm_advanced"
                                                       style="<?php echo $active_tab === 'autoterm_advanced' ? '' : 'display:none;'; ?>">

                                                        <?php do_action('taxopress_autoterms_after_autoterm_advanced', $current); ?>

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
                                    <div class="pp-version-notice-bold-purple-message"><?php echo esc_html__('You\'re using TaxoPress Free.
                                        The Pro version has more features and support.', 'simple-tags'); ?>
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/pro" target="_blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
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
     <div class="" style="color:red;"><?php echo esc_html__('Please complete the following required fields to save your changes:', 'simple-tags'); ?></div>
    <div id="taxopress-modal-alert-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo esc_html__('Okay', 'simple-tags'); ?></button>
</div>

<div class="remodal" data-remodal-id="taxopress-modal-confirm"
     data-remodal-options="hashTracking: false, closeOnOutsideClick: false">
    <div id="taxopress-modal-confirm-content"></div>
    <br>
    <button data-remodal-action="cancel" class="remodal-cancel"><?php echo esc_html__('No', 'simple-tags'); ?></button>
    <button data-remodal-action="confirm"
            class="remodal-confirm"><?php echo esc_html__('Yes', 'simple-tags'); ?></button>
</div>

        <?php
    }

}
