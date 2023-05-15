<?php

class SimpleTags_Tag_Clouds
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_terms_display') {
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
            esc_html__('Terms Display', 'simple-tags'),
            esc_html__('Terms Display', 'simple-tags'),
            'simple_tags',
            'st_terms_display',
            [
                $this,
                'page_manage_tagclouds',
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
            'option'  => 'st_terms_display_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new TagClouds_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_tagclouds()
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
                <h1 class="wp-heading-inline"><?php _e('Terms Display', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_terms_display&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                   <div class="taxopress-description"><?php esc_html_e('This feature allows you to create a customizable display of all the terms in one taxonomy.', 'simple-tags'); ?></div>


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
                    <?php $this->terms_table->search_box(esc_html__('Search Terms Display', 'simple-tags'), 'term'); ?>
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
                $this->taxopress_manage_tagclouds();
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
    public function taxopress_manage_tagclouds()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

    <div class="wrap <?php echo esc_attr($tab_class); ?>">

        <?php

        $tagclouds = taxopress_get_tagcloud_data();
        $tag_cloud_edit = false;
        $tag_cloud_limit = false;

        if ('edit' === $tab) {


            $selected_tagcloud = taxopress_get_current_tagcloud();

            if ($selected_tagcloud && array_key_exists($selected_tagcloud, $tagclouds)) {
                $current       = $tagclouds[$selected_tagcloud];
                $tag_cloud_edit = true;
            }

        }


        if(!isset($current['title']) && count($tagclouds) > 0 && apply_filters('taxopress_tag_clouds_create_limit', true)){
            $tag_cloud_limit = true;
        }


        $ui = new taxopress_admin_ui();
        ?>




<div class="wrap <?php echo esc_attr($tab_class); ?>">
            <h1><?php echo esc_html__('Manage Terms Display', 'simple-tags'); ?></h1>
            <div class="wp-clearfix"></div>

            <form method="post" action="">


                <div class="tagcloudui st-tabbed">


                    <div class="tagclouds-postbox-container">
                        <div id="poststuff">
                            <div class="taxopress-section postbox">
                                <div class="postbox-header">
                                    <h2 class="hndle ui-sortable-handle">
                                        <?php
                                        if ($tag_cloud_edit) {
                                            $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'tagcloud_general';
                                            echo esc_html__('Edit Terms Display', 'simple-tags');
                                            echo '<input type="hidden" name="edited_tagcloud" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_tag_cloud[ID]" value="' . esc_attr($current['ID']) . '" />';
                                            echo '<input type="hidden" name="taxopress_tag_cloud[active_tab]" class="taxopress-active-subtab" value="'.esc_attr($active_tab).'" />';
                                        } else {
                                            $active_tab = 'tagcloud_general';
                                            echo '<input type="hidden" name="taxopress_tag_cloud[active_tab]" class="taxopress-active-subtab" value="" />';
                                            echo esc_html__('Add new Terms Display', 'simple-tags');
                                        }
                                        ?>
                                    </h2>
                                </div>
                                <div class="inside">
                                    <div class="main">


                                        <?php if ($tag_cloud_limit) {
                                            echo '<div class="st-taxonomy-content promo-box-area"><div class="taxopress-warning upgrade-pro">
                                            <h2 style="margin-bottom: 5px;">' . esc_html__('To create more Terms Display, please upgrade to TaxoPress Pro.','simple-tags').'</h2>
                                            <p>

                                            ' . esc_html__('With TaxoPress Pro, you can create unlimited Terms Display. You can create Terms Display for any taxonomy and then display those Terms Display anywhere on your site.','simple-tags').'

                                            </p>
                                            </div></div>';

                                        } else {
                                            ?>


                                            <ul class="taxopress-tab">
                                                <li aria-current="<?php echo $active_tab === 'tagcloud_general' ? 'true' : 'false'; ?>" class="tagcloud_general_tab <?php echo $active_tab === 'tagcloud_general' ? 'active' : ''; ?>" data-content="tagcloud_general">
                                                    <a href="#tagcloud_general"><span><?php esc_html_e('General',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'tagcloud_terms' ? 'true' : 'false'; ?>" class="tagcloud_terms_tab <?php echo $active_tab === 'tagcloud_terms' ? 'active' : ''; ?>" data-content="tagcloud_terms">
                                                    <a href="#tagcloud_terms"><span><?php esc_html_e('Choose Terms',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'tagcloud_design' ? 'true' : 'false'; ?>" class="tagcloud_design_tab <?php echo $active_tab === 'tagcloud_design' ? 'active' : ''; ?>" data-content="tagcloud_design">
                                                    <a href="#tagcloud_design"><span><?php esc_html_e('Design',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                                <li aria-current="<?php echo $active_tab === 'tagcloud_advanced' ? 'true' : 'false'; ?>" class="tagcloud_advanced_tab <?php echo $active_tab === 'tagcloud_advanced' ? 'active' : ''; ?>" data-content="tagcloud_advanced">
                                                    <a href="#tagcloud_advanced"><span><?php esc_html_e('Display Format',
                                                                'simple-tags'); ?></span></a>
                                                </li>

                                            </ul>

                                            <div class="st-taxonomy-content taxopress-tab-content">


                                                <table class="form-table taxopress-table tagcloud_general"
                                                       style="<?php echo $active_tab === 'tagcloud_general' ? '' : 'display:none;'; ?>">
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
                                                        'namearray'   => 'taxopress_tag_cloud',
                                                        'name'        => 'title',
                                                        'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                        'maxlength'   => '32',
                                                        'helptext'  => '',
                                                        'required'    => true,
                                                        'placeholder' => false,
                                                        'wrap'        => false,
                                                    ]);



                                                $options[] = [ 'attr' => '', 'text' => esc_html__('All post types', 'simple-tags'), 'default' => 'true' ];
                                                foreach ( get_post_types(['public' => true], 'objects') as $post_type ) {
                                                    $options[] = [ 'attr' => $post_type->name, 'text' => $post_type->label ];
                                                }

                                                $select = [
								                    'options' => $options,
							                    ];
							                    $selected = ( isset( $current ) && isset($current['post_type']) ) ? taxopress_disp_boolean( $current['post_type'] ) : '';
							                    $select['selected'] = ! empty( $selected ) ? $current['post_type'] : '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_select_checkbox_input_main( [
								                        'namearray'  => 'taxopress_tag_cloud',
								                        'name'       => 'post_type',
                                                        'class'      => 'st-post-type-select',
								                        'labeltext'  => esc_html__( 'Post Type', 'simple-tags' ),
								                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							                    ] );

                                                $options = [];
                                                foreach ( get_all_taxopress_taxonomies() as $_taxonomy ) {
                                                    $_taxonomy = $_taxonomy->name;
						                            $tax = get_taxonomy( $_taxonomy );
						                            if ( ! $tax->show_tagcloud || empty( $tax->labels->name ) ) {
                                                        continue;
                                                    }
                                                    if($tax->name === 'post_tag'){
                                                        $options[] = [ 'post_type' => join(',', $tax->object_type), 'attr' => $tax->name, 'text' => $tax->labels->name. ' ('.$tax->name.')', 'default' => 'true' ];
                                                    }else{
                                                        $options[] = [ 'post_type' => join(',', $tax->object_type), 'attr' => $tax->name, 'text' => $tax->labels->name. ' ('.$tax->name.')' ];
                                                    }
                                                }

                                                $select = [
								                    'options' => $options,
							                    ];
							                    $selected = isset( $current ) ? taxopress_disp_boolean( $current['taxonomy'] ) : '';
							                    $select['selected'] = ! empty( $selected ) ? $current['taxonomy'] : '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_select_checkbox_input_main( [
								                        'namearray'  => 'taxopress_tag_cloud',
								                        'name'       => 'taxonomy',
                                                        'class'      => 'st-post-taxonomy-select',
								                        'labeltext'  => esc_html__( 'Taxonomy', 'simple-tags' ),
                                                        'required'   => true,
								                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							                    ] );

                                                $select = [
								                    'options' => [
									                    [ 'attr' => 'flat', 'text' => esc_attr__( 'Cloud', 'simple-tags' ), 'default' => 'true' ],
									                    [ 'attr' => 'list', 'text' => esc_attr__( 'List (UL/LI)', 'simple-tags' ) ],
								                    ],
							                    ];
							                    $selected = isset( $current ) ? taxopress_disp_boolean( $current['format'] ) : '';
							                    $select['selected'] = ! empty( $selected ) ? $current['format'] : '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_select_checkbox_input_main( [
								                        'namearray'  => 'taxopress_tag_cloud',
								                        'name'       => 'format',
								                        'labeltext'  => esc_html__( 'Display format', 'simple-tags' ),
								                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							                    ] );
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_td_end() . $ui->get_tr_end();
                                                    ?>
                                                </table>




                                                <table class="form-table taxopress-table tagcloud_terms"
                                                       style="<?php echo $active_tab === 'tagcloud_terms' ? '' : 'display:none;'; ?>">
                                                    <?php


                                            $select = [
                                                'options' => [
                                                    [ 'attr' => '1', 'text' => esc_attr__( '24 hours', 'simple-tags' ) ],
                                                    [ 'attr' => '7', 'text' => esc_attr__( '7 days', 'simple-tags' ) ],
                                                    [ 'attr' => '14', 'text' => esc_attr__( '2 weeks', 'simple-tags' ) ],
                                                    [ 'attr' => '30', 'text' => esc_attr__( '1 month', 'simple-tags' ) ],
                                                    [ 'attr' => '180', 'text' => esc_attr__( '6 months', 'simple-tags' ) ],
                                                    [ 'attr' => '365', 'text' => esc_attr__( '1 year', 'simple-tags' ) ],
                                                    [ 'attr' => '0', 'text' => esc_attr__( 'No limit', 'simple-tags'), 'default' => 'true' ],
                                                ],
                                            ];
                                            $selected = isset( $current ) ? taxopress_disp_boolean( $current['limit_days'] ) : '';
                                            $select['selected'] = ! empty( $selected ) ? $current['limit_days'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_number_select( [
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'limit_days',
                                                    'labeltext'  => esc_html__( 'Limit terms based on timeframe', 'simple-tags' ),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ] );


                                            $select = [
                                                'options' => [
                                                    [ 'attr' => 'name', 'text' => esc_attr__( 'Name', 'simple-tags' ) ],
                                                    [ 'attr' => 'slug', 'text' => esc_attr__( 'Slug', 'simple-tags' ) ],
                                                    [ 'attr' => 'count', 'text' => esc_attr__( 'Counter', 'simple-tags'), 'default' => 'true' ],
                                                    [ 'attr' => 'random', 'text' => esc_attr__( 'Random', 'simple-tags' ) ],
                                                ],
                                            ];
                                            $selected = isset( $current ) ? taxopress_disp_boolean( $current['selectionby'] ) : '';
                                            $select['selected'] = ! empty( $selected ) ? $current['selectionby'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input_main( [
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'selectionby',
                                                    'labeltext'  => esc_html__( 'Method for choosing terms from the database', 'simple-tags' ),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ] );


                                            $select = [
                                                'options' => [
                                                    [ 'attr' => 'asc', 'text' => esc_attr__( 'Ascending', 'simple-tags' ) ],
                                                    [ 'attr' => 'desc', 'text' => esc_attr__( 'Descending', 'simple-tags'), 'default' => 'true' ],
                                                ],
                                            ];
                                            $selected = isset( $current ) ? taxopress_disp_boolean( $current['selection'] ) : '';
                                            $select['selected'] = ! empty( $selected ) ? $current['selection'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input_main( [
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'selection',
                                                    'labeltext'  => esc_html__( 'Ordering for choosing term from the database', 'simple-tags' ),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ] );


                                            $select = [
                                                'options' => [
                                                    [ 'attr' => 'name', 'text' => esc_attr__( 'Name', 'simple-tags' ) ],
                                                    [ 'attr' => 'count', 'text' => esc_attr__( 'Counter', 'simple-tags') ],
                                                    [ 'attr' => 'random', 'text' => esc_attr__( 'Random', 'simple-tags' ), 'default' => 'true' ],
                                                ],
                                            ];
                                            $selected = isset( $current ) ? taxopress_disp_boolean( $current['orderby'] ) : '';
                                            $select['selected'] = ! empty( $selected ) ? $current['orderby'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input_main( [
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'orderby',
                                                    'labeltext'  => esc_html__( 'Method for choosing terms for display', 'simple-tags' ),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ] );


                                            $select = [
                                                'options' => [
                                                    [ 'attr' => 'asc', 'text' => esc_attr__( 'Ascending', 'simple-tags' ) ],
                                                    [ 'attr' => 'desc', 'text' => esc_attr__( 'Descending', 'simple-tags'), 'default' => 'true' ],
                                                ],
                                            ];
                                            $selected = isset( $current ) ? taxopress_disp_boolean( $current['order'] ) : '';
                                            $select['selected'] = ! empty( $selected ) ? $current['order'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input_main( [
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'order',
                                                    'labeltext'  => esc_html__( 'Ordering for choosing terms for display', 'simple-tags' ),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ] );



                                                    ?>
                                                </table>


                                                <table class="form-table taxopress-table tagcloud_design"
                                                       style="<?php echo $active_tab === 'tagcloud_design' ? '' : 'display:none;'; ?>">
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
                                            $selected           = ( isset($current) && isset($current['hide_title']) ) ? taxopress_disp_boolean($current['hide_title']) : '';
                                            $select['selected'] = !empty($selected) ? $current['hide_title'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'taxopress_tag_cloud',
                                                'name'       => 'hide_title',
                                                'labeltext'  => esc_html__('Hide title in output?', 'simple-tags'),
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
                                            $selected           = ( isset($current) && isset($current['hide_output']) ) ? taxopress_disp_boolean($current['hide_output']) : '';
                                            $select['selected'] = !empty($selected) ? $current['hide_output'] : '';
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_select_checkbox_input([
                                                'namearray'  => 'taxopress_tag_cloud',
                                                'name'       => 'hide_output',
                                                'labeltext'  => esc_html__('Hide display output if no terms?', 'simple-tags'),
                                                'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            ]);
                                            
                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_number_input([
                                                'namearray' => 'taxopress_tag_cloud',
                                                'name'      => 'max',
                                                'textvalue' => isset($current['max']) ? esc_attr($current['max']) : '45',
                                                'labeltext' => esc_html__('Maximum terms to display', 'simple-tags'),
                                                'helptext'    => '',
                                                'required'  => true,
                                            ]);
                                            
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_number_input([
                                                    'namearray' => 'taxopress_tag_cloud',
                                                    'name'      => 'smallest',
                                                    'textvalue' => isset($current['smallest']) ? esc_attr($current['smallest']) : '8',
                                                    'labeltext' => esc_html__('Font size minimum', 'simple-tags'),
                                                    'helptext'    => '',
                                                    'required'  => true,
                                                ]);

                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_number_input([
                                                    'namearray' => 'taxopress_tag_cloud',
                                                    'name'      => 'largest',
                                                    'textvalue' => isset($current['largest']) ? esc_attr($current['largest']) : '22',
                                                    'labeltext' => esc_html__('Font size maximum', 'simple-tags'),
                                                    'helptext'    => '',
                                                    'required'  => true,
                                                ]);

                                                $select = [
								                    'options' => [
									                    [ 'attr' => 'pt', 'text' => esc_attr__( 'Point', 'simple-tags' ), 'default' => 'true' ],
									                    [ 'attr' => 'px', 'text' => esc_attr__( 'Pixel', 'simple-tags' ) ],
									                    [ 'attr' => 'em', 'text' => esc_attr__( 'Em', 'simple-tags') ],
									                    [ 'attr' => '%', 'text' => esc_attr__( 'Percent', 'simple-tags') ],
								                    ],
							                    ];
							                    $selected = isset( $current ) ? taxopress_disp_boolean( $current['unit'] ) : '';
							                    $select['selected'] = ! empty( $selected ) ? $current['unit'] : '';
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_select_checkbox_input_main( [
								                        'namearray'  => 'taxopress_tag_cloud',
								                        'name'       => 'unit',
								                        'labeltext'  => esc_html__( 'Unit font size', 'simple-tags' ),
								                        'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							                    ] );



                                                $select             = [
                                                    'options' => [
                                                        [
                                                            'attr'    => '0',
                                                            'text'    => esc_attr__('False', 'simple-tags'),
                                                        ],
                                                        [
                                                            'attr' => '1',
                                                            'text' => esc_attr__('True', 'simple-tags'),
                                                            //'default' => 'true', removed when default value is checked as this mean box is always checked even when user uncheck it since it's defau;t
                                                        ],
                                                    ],
                                                ];
                                                $selected           = ( isset($current) && isset($current['color']) ) ? taxopress_disp_boolean($current['color']) : '';
    
                                                if($tag_cloud_edit){
                                                    $select['selected'] = !empty($selected) ? $current['color'] : '';
                                                }else{
                                                    $select['selected'] = 1; //makeup for default when creating new term display
                                                }
                                                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                echo $ui->get_select_checkbox_input([
                                                    'namearray'  => 'taxopress_tag_cloud',
                                                    'name'       => 'color',
                                                    'class'     => 'tag-cloud-color-option',
                                                    'labeltext'  => esc_html__('Enable colors for terms', 'simple-tags'),
                                                    'selections' => $select,// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                ]);

                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_text_input([
                                                    'namearray' => 'taxopress_tag_cloud',
                                                    'name'      => 'mincolor',
                                                    'class'     => 'text-color tag-cloud-min',
                                                    'textvalue' => isset($current['mincolor']) ? esc_attr($current['mincolor']) : '#CCCCCC',
                                                    'labeltext' => esc_html__('Font color minimum', 'simple-tags'),
                                                    'helptext' => esc_html__('This is the color of the least popular term', 'simple-tags'),
                                                    'required'  => true,
                                                ]);

                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                            echo $ui->get_text_input([
                                                    'namearray' => 'taxopress_tag_cloud',
                                                    'name'      => 'maxcolor',
                                                    'class'     => 'text-color tag-cloud-max',
                                                    'textvalue' => isset($current['maxcolor']) ? esc_attr($current['maxcolor']) : '#000000',
                                                    'labeltext' => esc_html__('Font color maximum', 'simple-tags'),
                                                    'helptext' => esc_html__('This is the color of the most popular term', 'simple-tags'),
                                                    'required'  => true,
                                                ]);


                                                    ?>

                                                </table>


                                                <table class="form-table taxopress-table tagcloud_advanced"
                                                       style="<?php echo $active_tab === 'tagcloud_advanced' ? '' : 'display:none;'; ?>">
                                                       <?php 
                                                       
                                                       // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                       echo $ui->get_text_input([
                                                                    'namearray' => 'taxopress_tag_cloud',
                                                                    'name'      => 'wrap_class',
                                                                    'class'     => '',
                                                                    'textvalue' => isset($current['wrap_class']) ? esc_attr($current['wrap_class']) : '',
                                                                    'labeltext' => esc_html__('Term display div class', 'simple-tags'),
                                                                    'helptext'  => '',
                                                                    'required'  => false,
                                                                ]);

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_text_input([
                                                                    'namearray' => 'taxopress_tag_cloud',
                                                                    'name'      => 'link_class',
                                                                    'class'     => '',
                                                                    'textvalue' => isset($current['link_class']) ? esc_attr($current['link_class']) : '',
                                                                    'labeltext' => esc_html__('Term link class', 'simple-tags'),
                                                                    'helptext'  => '',
                                                                    'required'  => false,
                                                                ]);

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_textarea_input([
                                                                    'namearray' => 'taxopress_tag_cloud',
                                                                    'name'      => 'xformat',
                                                                    'class'     => 'st-full-width',
                                                                    'rows'      => '4',
                                                                    'cols'      => '40',
                                                                    'textvalue' => isset($current['xformat']) ? esc_attr($current['xformat']) : esc_attr('<a href="%tag_link%" id="tag-link-%tag_id%" class="st-tags t%tag_scale%" title="%tag_count% topics" %tag_rel% style="%tag_size% %tag_color%">%tag_name%</a>'),
                                                                    'labeltext' => esc_html__('Term link format', 'simple-tags'),
                                                                    'helptext'  => sprintf(esc_html__('This option allows you to change the format of your terms. You can use any of the tokens in the "Terms Display format" sidebar". Find more details %1sin the documentation%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/format-tag-clouds/">', '</a>'),
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


                            <?php if ($tag_cloud_limit) { ?>

                                <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                    <div class="pp-version-notice-bold-purple-message"><?php echo esc_html__('You\'re using TaxoPress Free.
                                        The Pro version has more features and support.', 'simple-tags'); ?>
                                    </div>
                                    <div class="pp-version-notice-bold-purple-button"><a
                                            href="https://taxopress.com/taxopress/" target="_blank"><?php echo esc_html__('Upgrade to Pro', 'simple-tags'); ?></a>
                                    </div>
                                </div>

                            <?php } ?>
                            <input type="hidden" class="pp-terms-display-fontsize-warning" value="<?php echo esc_attr__('Font size minimum must not be greater than Font size maximum value.', 'simple-tags'); ?>" />
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
                    <div class="taxopress-right-sidebar-wrapper" style="min-height: 205px;<?php echo ($tag_cloud_limit) ? 'display: none;' : ''; ?>">


                    <?php
                    if(!$tag_cloud_limit){ ?>
                        <p class="submit">

                            <?php
                            wp_nonce_field('taxopress_addedit_tagcloud_nonce_action',
                                'taxopress_addedit_tagcloud_nonce_field');
                            if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-tag-cloud-submit" name="tagcloud_submit"
                                       value="<?php echo esc_attr(esc_attr__('Save Terms Display', 'simple-tags')); ?>"/>
                                <?php
                            } else { ?>
                            <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-tag-cloud-submit" name="tagcloud_submit"
                                   value="<?php echo esc_attr(esc_attr__('Add Terms Display', 'simple-tags')); ?>"/>
                    <?php } ?>

                        <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                               value="<?php echo esc_attr($tab); ?>"/>
                        </p>

                        <?php if (!empty($current)) {
                            ?>
                        <p>
                            <?php echo '<div class="taxopress-warning" style="">' . esc_html__('Shortcode: ','simple-tags'); ?> &nbsp;
                            <textarea style="resize: none;padding: 5px;" readonly>[taxopress_termsdisplay id="<?php echo (int)$current['ID']; ?>"]</textarea>
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
                            <span><?php echo esc_html__('Terms Display format', 'simple-tags'); ?></span>
                        </h3>
                    </div>
        
                    <div class="inside">
                        <p><?php echo esc_html__('Here are the tokens you can use for Term link format', 'simple-tags'); ?>:</p>
                        <ul>
                            <li><code>%tag_link%</code> – <?php echo esc_html__('The URL of the term', 'simple-tags'); ?></li>
                            <li><code>%tag_id%</code> – <?php echo esc_html__('The ID of the term', 'simple-tags'); ?></li>
                            <li><code>t%tag_scale%</code> – <?php echo esc_html__('The weighted size of the term in the display', 'simple-tags'); ?></li>
                            <li><code>%tag_count%</code> – <?php echo esc_html__('The number of times the term is used', 'simple-tags'); ?></li>
                            <li><code>%tag_rel%</code> – <?php echo esc_html__('This provides rel tag markup', 'simple-tags'); ?> (<?php echo esc_html__('it creates', 'simple-tags'); ?> <code>rel="tag"</code>)</li>
                            <li><code>%tag_size%</code> – <?php echo esc_html__('The font size for the term', 'simple-tags'); ?></li>
                            <li><code>%tag_color%</code> – <?php echo esc_html__('The font color for the term', 'simple-tags'); ?></li>
                            <li><code>%tag_name%</code> – <?php echo esc_html__('The name of the term', 'simple-tags'); ?></li>
                            <li><code>%tag_name_attribute%</code> – <?php echo esc_html__('The name of the term with any HTML stripped out', 'simple-tags'); ?></li>
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
