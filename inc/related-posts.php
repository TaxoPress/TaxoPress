<?php

class SimpleTags_Related_Post
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_related_posts') {
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
            esc_html__('Related Posts', 'simple-tags'),
            esc_html__('Related Posts', 'simple-tags'),
            'simple_tags',
            'st_related_posts',
            [
                $this,
                'page_manage_relatedposts',
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
            'label'   => esc_html__('Number of items per page', 'simple-tags'),
            'default' => 20,
            'option'  => 'st_related_posts_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new RelatedPosts_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_relatedposts()
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
                <h1 class="wp-heading-inline"><?php _e('Related Posts', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_related_posts&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description"><?php esc_html_e('The Related Posts feature works by checking for shared taxonomy terms. If your post has the terms “WordPress” and “Website”, then Related Posts will display other posts that also have the terms “WordPress” and “Website”.', 'simple-tags'); ?></div>


                <?php
                if (isset($_REQUEST['s']) && $search = sanitize_text_field(wp_unslash($_REQUEST['s']))) {
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
                    <?php $this->terms_table->search_box(__('Search Related Posts', 'simple-tags'), 'term'); ?>
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
                $this->taxopress_manage_relatedposts();
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
    public function taxopress_manage_relatedposts()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

        <div class="wrap <?php echo esc_attr($tab_class); ?>">

            <?php

            $relatedposts       = taxopress_get_relatedpost_data();
            $related_post_edit  = false;
            $related_post_limit = false;

            if ('edit' === $tab) {


                $selected_relatedpost = taxopress_get_current_relatedpost();

                if ($selected_relatedpost && array_key_exists($selected_relatedpost, $relatedposts)) {
                    $current           = $relatedposts[$selected_relatedpost];
                    $related_post_edit = true;
                }

            }


            if (!isset($current['title']) && count($relatedposts) > 0 && apply_filters('taxopress_related_posts_create_limit', true)) {
                $related_post_limit = true;
            }


            $ui = new taxopress_admin_ui();
            ?>


            <div class="wrap <?php echo esc_attr($tab_class); ?>">
                <h1><?php echo esc_html__('Manage Related Posts', 'simple-tags'); ?></h1>
                <div class="wp-clearfix"></div>

                <form method="post" action="">


                    <div class="tagcloudui st-tabbed">

                    <div class="relatedposts-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($related_post_edit) {
                                            $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'relatedpost_general';
                                            echo esc_html__('Edit Related Posts', 'simple-tags');
                                            echo '<input type="hidden" name="edited_relatedpost" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_related_post[ID]" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_related_post[active_tab]" class="taxopress-active-subtab" value="'.esc_attr($active_tab).'" />';
                                        } else {
                                            $active_tab = 'relatedpost_general';
                                            echo '<input type="hidden" name="taxopress_related_post[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Related Posts', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($related_post_limit) {
                                            echo '<div class="st-taxonomy-content promo-box-area"><div class="taxopress-warning upgrade-pro">
                                            <h2 style="margin-bottom: 5px;">' . esc_html__('To create more Related Posts, please upgrade to TaxoPress Pro.',
                                                            'simple-tags') . '</h2>
                                                            <p>
                
                                            ' . esc_html__('With TaxoPress Pro, you can create unlimited Related Posts. You can create Related Posts for any taxonomy and then display those Related Posts anywhere on your site.',
                                                            'simple-tags') . '

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li aria-current="<?php echo $active_tab === 'relatedpost_general' ? 'true' : 'false'; ?>" class="relatedpost_general_tab <?php echo $active_tab === 'relatedpost_general' ? 'active' : ''; ?>" data-content="relatedpost_general">
                                                    <a href="#relatedpost_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'relatedpost_display' ? 'true' : 'false'; ?>" class="relatedpost_display_tab <?php echo $active_tab === 'relatedpost_display' ? 'active' : ''; ?>" data-content="relatedpost_display">
                                                    <a href="#relatedpost_display"><span><?php esc_html_e('Display',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'relatedpost_option' ? 'true' : 'false'; ?>" class="relatedpost_option_tab <?php echo $active_tab === 'relatedpost_option' ? 'active' : ''; ?>" data-content="relatedpost_option">
                                                    <a href="#relatedpost_option"><span><?php esc_html_e('Options',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'relatedpost_advanced' ? 'true' : 'false'; ?>" class="relatedpost_advanced_tab <?php echo $active_tab === 'relatedpost_advanced' ? 'active' : ''; ?>" data-content="relatedpost_advanced">
                                                    <a href="#relatedpost_advanced"><span><?php esc_html_e('Advanced',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table relatedpost_general"
                                                       style="<?php echo $active_tab === 'relatedpost_general' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_tr_start();

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_th_start();
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_label('name', esc_html__('Title', 'simple-tags')) . $ui->get_required_span();
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_th_end() . $ui->get_td_start();
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray'   => 'taxopress_related_post',
                                                            'name'        => 'title',
                                                            'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                            'maxlength'   => '32',
                                                            'helptext'    => '',
                                                            'required'    => true,
                                                            'placeholder' => false,
                                                            'wrap'        => false,
                                                        ]);


                                                        $select             = [
                                                            'options' => [
                                                                [
                                                                    'attr' => '',
                                                                    'text' => esc_attr__('None', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'h1',
                                                                    'text' => esc_attr__('H1', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'h2',
                                                                    'text' => esc_attr__('H2', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'h3',
                                                                    'text' => esc_attr__('H3', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr'    => 'h4',
                                                                    'text'    => esc_attr__('H4', 'simple-tags'),
                                                                    'default' => 'true'
                                                                ],
                                                                [
                                                                    'attr' => 'h5',
                                                                    'text' => esc_attr__('H5', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'h6',
                                                                    'text' => esc_attr__('H6', 'simple-tags')
                                                                ],
                                                            ],
                                                        ];
                                                        $selected           = isset($current) ? taxopress_disp_boolean($current['title_header']) : '';

                                                        $select['selected'] = !empty($selected) ? $current['title_header'] : '';
                                                        if(isset($current['title_header']) && empty($current['title_header'])){
                                                          $select['selected'] = 'none';
                                                        }
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input_main([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'title_header',
                                                            'labeltext'  => esc_html__('Title header', 'simple-tags'),
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);

                                                        $options[] = [
                                                            'attr' => 'st_all_posttype',
                                                            'text' => esc_html__('All post types', 'simple-tags')
                                                        ];
                                                        $options[] = [
                                                            'attr'    => 'st_current_posttype',
                                                            'text'    => esc_html__('Current post type', 'simple-tags'),
                                                            'default' => 'true'
                                                        ];
                                                        foreach (get_post_types(['public' => true],
                                                            'objects') as $post_type) {
                                                            $options[] = [
                                                                'attr' => $post_type->name,
                                                                'text' => $post_type->label
                                                            ];
                                                        }

                                                        $select             = [
                                                            'options' => $options,
                                                        ];
                                                        $selected           = (isset($current) && isset($current['post_type'])) ? taxopress_disp_boolean($current['post_type']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['post_type'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input_main([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'post_type',
                                                            'class'      => 'st-post-type-select',
                                                            'labeltext'  => esc_html__('Post Type', 'simple-tags'),
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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
                                                                    'post_type' => join(',', $tax->object_type),
                                                                ];
                                                            } else {
                                                                $options[] = [
                                                                    'attr' => $tax->name,
                                                                    'text' => $tax->labels->name. ' ('.$tax->name.')',
                                                                    'post_type' => join(',', $tax->object_type),
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
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'taxonomy',
                                                            'class'      => 'st-post-taxonomy-select',
                                                            'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                            'required'   => true,
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);
                                                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                    echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table relatedpost_display"
                                                       style="<?php echo $active_tab === 'relatedpost_display' ? '' : 'display:none;'; ?>">
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
                                                        $selected           = (isset($current) && isset($current['hide_title'])) ? taxopress_disp_boolean($current['hide_title']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['hide_title'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'hide_title',
                                                            'labeltext'  => esc_html__('Hide title in output ?',
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

                                                        $term_auto_locations = [
                                                            'homeonly' => esc_attr__('Homepage', 'simple-tags'),
                                                            'blogonly' => esc_attr__('Blog display', 'simple-tags'),
                                                        ];
                                                        foreach ($post_types as $post_type) {
                                                            $term_auto_locations[$post_type->name] = $post_type->label;
                                                        }

                                                        echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Attempt to automatically display related posts',
                                                                'simple-tags') . '</label><br /><small style=" color: #646970;">' . esc_html__('TaxoPress will attempt to automatically display related posts in this content. It may not be successful for all post types and layouts.',
                                                                'simple-tags') . '</small></th><td>
                                                                <table class="visbile-table">';
                                                        foreach ($term_auto_locations as $key => $value) {


                                                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr($key) . '">' .esc_html($value) . '</label></th><td>';

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_check_input([
                                                                'checkvalue' => esc_attr($key),
                                                                'checked'    => (!empty($current['embedded']) && is_array($current['embedded']) && in_array($key,
                                                                        $current['embedded'], true)) ? 'true' : 'false',
                                                                'name'       => esc_attr($key),
                                                                'namearray'  => 'embedded',
                                                                'textvalue'  => esc_attr($key),
                                                                'labeltext'  => "",
                                                                'wrap'       => false,
                                                            ]);

                                                            echo '</td></tr>';

                                                            if ($key === 'blogonly') {
                                                                echo '<tr valign="top"><th style="padding: 0;" scope="row"><hr /></th><td style="padding: 0;"><hr /></td></tr>';
                                                            }


                                                        }
                                                        echo '</table></td></tr>';

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_number_input([
                                                            'namearray' => 'taxopress_related_post',
                                                            'name'      => 'number',
                                                            'textvalue' => isset($current['number']) ? esc_attr($current['number']) : '5',
                                                            'labeltext' => esc_html__('Maximum related posts to display',
                                                                'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => true,
                                                        ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table relatedpost_option"
                                                       style="<?php echo $active_tab === 'relatedpost_option' ? '' : 'display:none;'; ?>">
                                                    <?php

                                                        $select             = [
                                                            'options' => [
                                                                [
                                                                    'attr' => '1',
                                                                    'text' => esc_attr__('24 hours', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => '7',
                                                                    'text' => esc_attr__('7 days', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => '14',
                                                                    'text' => esc_attr__('2 weeks', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => '30',
                                                                    'text' => esc_attr__('1 month', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => '180',
                                                                    'text' => esc_attr__('6 months', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => '365',
                                                                    'text' => esc_attr__('1 year', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr'    => '0',
                                                                    'text'    => esc_attr__('No limit', 'simple-tags'),
                                                                    'default' => 'true'
                                                                ],
                                                            ],
                                                        ];
                                                        $selected           = isset($current) ? taxopress_disp_boolean($current['limit_days']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['limit_days'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_number_select([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'limit_days',
                                                            'labeltext'  => esc_html__('Limit related posts based on timeframe',
                                                                'simple-tags'),
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);


                                                        $select             = [
                                                            'options' => [
                                                                [
                                                                    'attr' => 'count-asc',
                                                                    'text' => esc_attr__('Least common tags between posts',
                                                                        'simple-tags')
                                                                ],
                                                                [
                                                                    'attr'    => 'count-desc',
                                                                    'text'    => esc_attr__('Most common tags between posts',
                                                                        'simple-tags'),
                                                                    'default' => 'true'
                                                                ],
                                                                [
                                                                    'attr' => 'date-asc',
                                                                    'text' => esc_attr__('Older Entries', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'date-desc',
                                                                    'text' => esc_attr__('Newer Entries', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'name-asc',
                                                                    'text' => esc_attr__('Alphabetical', 'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'name-desc',
                                                                    'text' => esc_attr__('Inverse Alphabetical',
                                                                        'simple-tags')
                                                                ],
                                                                [
                                                                    'attr' => 'random',
                                                                    'text' => esc_attr__('Random', 'simple-tags')
                                                                ],
                                                            ],
                                                        ];
                                                        $selected           = isset($current) ? taxopress_disp_boolean($current['order']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['order'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input_main([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'order',
                                                            'labeltext'  => esc_html__('Related Posts Order',
                                                                'simple-tags'),
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_related_post',
                                                            'name'      => 'nopoststext',
                                                            'textvalue' => isset($current['nopoststext']) ? esc_attr($current['nopoststext']) : 'No related posts.',
                                                            'labeltext' => esc_html__('Text to show when there is no related post',
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
                                                        $selected           = (isset($current) && isset($current['hide_output'])) ? taxopress_disp_boolean($current['hide_output']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['hide_output'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input([
                                                            'namearray'  => 'taxopress_related_post',
                                                            'name'       => 'hide_output',
                                                            'labeltext'  => esc_html__('Hide output if no related post is found ?',
                                                                'simple-tags'),
                                                            'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table relatedpost_advanced"
                                                       style="<?php echo $active_tab === 'relatedpost_advanced' ? '' : 'display:none;'; ?>">
                                                    <?php
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_related_post',
                                                            'name'      => 'wrap_class',
                                                            'class'     => '',
                                                            'textvalue' => isset($current['wrap_class']) ? esc_attr($current['wrap_class']) : '',
                                                            'labeltext' => esc_html__('Related Posts div class', 'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_related_post',
                                                            'name'      => 'link_class',
                                                            'class'     => '',
                                                            'textvalue' => isset($current['link_class']) ? esc_attr($current['link_class']) : '',
                                                            'labeltext' => esc_html__('Term link class', 'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_textarea_input([
                                                                'namearray' => 'taxopress_related_post',
                                                                'name'      => 'xformat',
                                                                'class'     => 'st-full-width',
                                                                'rows'      => '4',
                                                                'cols'      => '40',
                                                                'textvalue' => isset($current['xformat']) ? esc_attr($current['xformat']) : esc_attr('<a href="%post_permalink%" title="%post_title% (%post_date%)">%post_title%</a>'),
                                                                'labeltext' => esc_html__('Term link format', 'simple-tags'),
                                                                'helptext'  => sprintf(esc_html__('You can find markers and explanations %1sin the documentation%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/format-related-posts/">', '</a>'),
                                                                'required'  => false,
                                                            ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>


                                            </div>


                                        <?php }//end new fields
                                        ?>


                                        <div class="clear"></div>


                                    </div>
                                </div>
                            </div>


                                <?php if ($related_post_limit) { ?>

                                    <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                        <div class="pp-version-notice-bold-purple-message"><?php echo esc_html__('You\'re using TaxoPress Free.
                                            The Pro version has more features and support.', 'simple-tags'); ?>
                                        </div>
                                        <div class="pp-version-notice-bold-purple-button"><a
                                                href="https://taxopress.com/taxopress/" target="_blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
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
                        <div class="taxopress-right-sidebar-wrapper" style="min-height: 205px;<?php echo ($related_post_limit) ? 'display: none;' : ''; ?>">


                            <?php
                            if (!$related_post_limit){ ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_relatedpost_nonce_action',
                                    'taxopress_addedit_relatedpost_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-relatedposts-submit"
                                           name="relatedpost_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Related Posts',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-relatedposts-submit"
                                           name="relatedpost_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Related Posts',
                                               'simple-tags')); ?>"/>
                                <?php } ?>

                                <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                       value="<?php echo esc_attr($tab); ?>"/>
                            </p>

                            <?php if (!empty($current)) {
                            ?>
                             <p>
                                <?php echo '<div class="taxopress-warning" style="">' . esc_html__('Shortcode: ',
                                        'simple-tags'); ?> &nbsp;
                                <textarea
                                    style="resize: none;padding: 5px;" readonly>[taxopress_relatedposts id="<?php echo (int)$current['ID']; ?>"]</textarea>
                        </div>
                        </p>
                        <?php } ?>

                        <?php
                        }
                        ?>

                    </div>

                    <div class="taxopress-token-right-sidebar">
            <div id="postbox-container-1" class="postbox-container">
            <div class="meta-box-sortables">
                <div class="postbox">
                    <div class="postbox-header">
                        <h3 class="hndle is-non-sortable">
                            <span><?php echo esc_html__('Related Posts format', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside">
                        <p><?php echo esc_html__('Here are the tokens you can use for related posts format', 'simple-tags'); ?>:</p>
                        <ul>
                            <li><code>%post_permalink%</code> <?php echo esc_html__('The URL of the post', 'simple-tags'); ?></li>
                            <li><code>%post_title%</code> <?php echo esc_html__('The title of the post', 'simple-tags'); ?></li>
                            <li><code>%post_date% </code><?php echo esc_html__('The date of the post (this shows inside a tooltip)', 'simple-tags'); ?></li>
                            <li><code>%post_tagcount%</code> <?php echo esc_html__('The number of tags used by both posts', 'simple-tags'); ?></li>
                            <li><code>%post_comment%</code> <?php echo esc_html__('The number of comments on the post', 'simple-tags'); ?></li>
                            <li><code>%post_id%</code> <?php echo esc_html__('The ID of the post', 'simple-tags'); ?></li>
                            <li><code>%post_relatedtags%</code> <?php echo esc_html__('A list of tags used by both the current post and the related post', 'simple-tags'); ?></li>
                            <li><code>%post_excerpt%</code> <?php echo esc_html__('The post excerpt', 'simple-tags'); ?></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>    
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
