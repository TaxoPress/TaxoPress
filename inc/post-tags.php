<?php

class SimpleTags_Post_Tags
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
        if (isset($_GET['page']) && $_GET['page'] == 'st_post_tags') {
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
            __('Terms for Current Post', 'simpletags'),
            __('Terms for Current Post', 'simpletags'),
            'simple_tags',
            'st_post_tags',
            [
                $this,
                'page_manage_posttags',
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
            'label'   => __('Number of items per page', 'simpletags'),
            'default' => 20,
            'option'  => 'st_post_tags_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new PostTags_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_posttags()
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
                <h1 class="wp-heading-inline"><?php _e('Terms for Current Post', 'simpletags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_post_tags&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simpletags'); ?></a>

                <div class="taxopress-description">This feature allows you show all the terms assigned to the current
                    post.
                </div>


                <?php
                if (isset($_REQUEST['s']) && $search = esc_attr(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . __('Search results for &#8220;%s&#8221;',
                            'simpletags') . '</span>', $search);
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Terms for Current Post', 'simpletags'), 'term'); ?>
                </form>
                <div class="clear"></div>

                <div id="col-container" class="wp-clearfix">

                    <div class="col-wrap">
                        <form action="<?php echo add_query_arg('', '') ?>" method="post">
                            <?php $this->terms_table->display(); //Display the table ?>
                        </form>
                        <div class="form-wrap edit-term-notes">
                            <p><?php __('Description here.', 'simpletags') ?></p>
                        </div>
                    </div>


                </div>


            </div>
        <?php } else {
            if ($_GET['add'] == 'new_item') {
                //add/edit taxonomy
                $this->taxopress_manage_posttags();
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
    public function taxopress_manage_posttags()
    {

        $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
        $tab_class = 'taxopress-' . $tab;
        $current   = null;

        ?>

        <div class="wrap <?php echo esc_attr($tab_class); ?>">

            <?php

            $posttags        = taxopress_get_posttags_data();
            $post_tags_edit  = false;
            $post_tags_limit = false;

            if ('edit' === $tab) {


                $selected_posttags = taxopress_get_current_posttags();

                if ($selected_posttags && array_key_exists($selected_posttags, $posttags)) {
                    $current        = $posttags[$selected_posttags];
                    $post_tags_edit = true;
                }

            }


            if (!isset($current['title']) && count($posttags) > 0 && apply_filters('taxopress_post_tags_create_limit',
                    true)) {
                $post_tags_limit = true;
            }


            $ui = new taxopress_admin_ui();
            ?>


            <div class="wrap <?php echo esc_attr($tab_class); ?>">
                <h1><?php echo __('Manage Terms for Current Post', 'simpletags'); ?></h1>
                <div class="wp-clearfix"></div>

                <form method="post" action="">


                    <div class="tagcloudui">


                        <div class="posttags-postbox-container">
                            <div id="poststuff">
                                <div class="taxopress-section postbox">
                                    <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle">
                                            <?php
                                            if ($post_tags_edit) {
                                                echo esc_html__('Edit Terms for Current Post', 'simpletags');
                                                echo '<input type="hidden" name="edited_posttags" value="' . $current['ID'] . '" />';
                                                echo '<input type="hidden" name="taxopress_post_tags[ID]" value="' . $current['ID'] . '" />';
                                            } else {
                                                echo esc_html__('Add new Terms for Current Post', 'simpletags');
                                            }
                                            ?>
                                        </h2>
                                    </div>
                                    <div class="inside">
                                        <div class="main">


                                            <div class="st-taxonomy-content">

                                                <?php if ($post_tags_limit) {
                                                    echo '<div class="taxopress-warning upgrade-pro">
                                            <p>

                                            <h2 style="margin-bottom: 5px;">' . __('To create more Terms for Current Post, please upgrade to TaxoPress Pro.',
                                                            'simpletags') . '</h2>
                                            ' . __('With TaxoPress Pro, you can create unlimited Terms for Current Post. You can create Terms for Current Post for any taxonomy and then display those Terms for Current Post anywhere on your site.',
                                                            'simpletags') . '

                                            </p>
                                            </div>';

                                                } else {
                                                    ?>
                                                    <table class="form-table taxopress-table">
                                                        <?php
                                                        echo $ui->get_tr_start();

                                                        echo $ui->get_th_start();
                                                        echo $ui->get_label('name', esc_html__('Title',
                                                                'simpletags')) . $ui->get_required_span();
                                                        echo $ui->get_th_end() . $ui->get_td_start();

                                                        echo $ui->get_text_input([
                                                            'namearray'   => 'taxopress_post_tags',
                                                            'name'        => 'title',
                                                            'textvalue'   => isset($current['title']) ? esc_attr($current['title']) : '',
                                                            'maxlength'   => '32',
                                                            'helptext'    => '',
                                                            'required'    => true,
                                                            'placeholder' => false,
                                                            'wrap'        => false,
                                                        ]);

                                                        $options      = [];
                                                        $main_option  = [];
                                                        $other_option = [];
                                                        foreach (get_all_taxopress_taxonomies() as $_taxonomy) {
                                                            $_taxonomy = $_taxonomy->name;
                                                            $tax       = get_taxonomy($_taxonomy);
                                                            if (empty($tax->labels->name)) {
                                                                continue;
                                                            }
                                                            if ($tax->name === 'post_tag') {
                                                                $main_option[] = [
                                                                    'attr'    => $tax->name,
                                                                    'text'    => $tax->labels->name,
                                                                    'default' => 'true'
                                                                ];
                                                            } else {
                                                                $other_option[] = [
                                                                    'attr' => $tax->name,
                                                                    'text' => $tax->labels->name
                                                                ];
                                                            }
                                                        }
                                                        $options = array_merge($main_option, $other_option);

                                                        $select             = [
                                                            'options' => $options,
                                                        ];
                                                        $selected           = isset($current) ? taxopress_disp_boolean($current['taxonomy']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['taxonomy'] : '';
                                                        echo $ui->get_select_checkbox_input_main([
                                                            'namearray'  => 'taxopress_post_tags',
                                                            'name'       => 'taxonomy',
                                                            'labeltext'  => esc_html__('Taxonomy', 'simpletags'),
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

                                                        $term_auto_locations = [
                                                            'homeonly' => esc_attr__('Homepage', 'simpletags'),
                                                            'blogonly' => esc_attr__('Blog display', 'simpletags'),
                                                        ];
                                                        foreach ($post_types as $post_type) {
                                                            $term_auto_locations[$post_type->name] = $post_type->label;
                                                        }

                                                        echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Attempt to automatically display terms',
                                                                'simpletags') . '</label><br /><small style=" color: #646970;">' . esc_html__('TaxoPress will attempt to automatically display terms in this content. It may not be successful for all post types and layouts.',
                                                                'simpletags') . '</small></th><td>
                                                    <table>';
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

                                                            if ($key === 'blogonly') {
                                                                echo '<tr valign="top"><th style="padding: 0;" scope="row"><hr /></th><td style="padding: 0;"><hr /></td></tr>';
                                                            }


                                                        }
                                                        echo '</table></td></tr>';


                                                        echo $ui->get_number_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'number',
                                                            'textvalue' => isset($current['number']) ? esc_attr($current['number']) : '0',
                                                            'labeltext' => esc_html__('Maximum terms to display',
                                                                'simpletags'),
                                                            'helptext'  => 'You must set zero (0) to display all post tags.',
                                                            'min'       => '0',
                                                            'required'  => true,
                                                        ]);

                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'separator',
                                                            'textvalue' => isset($current['separator']) ? esc_attr($current['separator']) : ', ',
                                                            'labeltext' => esc_html__('Post term separator string:	',
                                                                'simpletags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'after',
                                                            'textvalue' => isset($current['after']) ? esc_attr($current['after']) : '<br />',
                                                            'labeltext' => esc_html__('Text to display after tags list',
                                                                'simpletags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'before',
                                                            'textvalue' => isset($current['before']) ? esc_attr($current['before']) : 'Tags: ',
                                                            'labeltext' => esc_html__('Text to display before tags list',
                                                                'simpletags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'notagtext',
                                                            'textvalue' => isset($current['notagtext']) ? esc_attr($current['notagtext']) : 'No tags for this post.',
                                                            'labeltext' => esc_html__('Text to display if no tags found',
                                                                'simpletags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        $select             = [
                                                            'options' => [
                                                                [
                                                                    'attr'    => '0',
                                                                    'text'    => esc_attr__('False', 'simpletags'),
                                                                    'default' => 'true',
                                                                ],
                                                                [
                                                                    'attr' => '1',
                                                                    'text' => esc_attr__('True', 'simpletags'),
                                                                ],
                                                            ],
                                                        ];
                                                        $selected           = (isset($current) && isset($current['hide_output'])) ? taxopress_disp_boolean($current['hide_output']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['hide_output'] : '';
                                                        echo $ui->get_select_checkbox_input([
                                                            'namearray'  => 'taxopress_post_tags',
                                                            'name'       => 'hide_output',
                                                            'labeltext'  => esc_html__('Hide display output if no terms ?',
                                                                'simpletags'),
                                                            'selections' => $select,
                                                        ]);

                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'xformat',
                                                            'class'     => 'st-full-width',
                                                            'textvalue' => isset($current['xformat']) ? esc_attr($current['xformat']) : esc_attr('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>'),
                                                            'labeltext' => esc_html__('Term link format', 'simpletags'),
                                                            'helptext'  => __('You can find markers and explanations <a target="blank" href="https://taxopress.com/docs/format-terms-current-post/">in the online documentation.</a>',
                                                                'simpletags'),
                                                            'required'  => false,
                                                        ]);

                                                        echo $ui->get_td_end() . $ui->get_tr_end();
                                                        ?>
                                                    </table>

                                                <?php }//end new fields
                                                ?>


                                            </div>
                                            <div class="clear"></div>


                                        </div>
                                    </div>
                                </div>


                                <?php if ($post_tags_limit) { ?>

                                    <div class="pp-version-notice-bold-purple" style="margin-left:0px;">
                                        <div class="pp-version-notice-bold-purple-message">You're using TaxoPress Free.
                                            The Pro version has more features and support.
                                        </div>
                                        <div class="pp-version-notice-bold-purple-button"><a
                                                href="https://taxopress.com/pro" target="_blank">Upgrade to Pro</a>
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
                            if (!$post_tags_limit){ ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_posttags_nonce_action',
                                    'taxopress_addedit_posttags_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                           name="posttags_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Terms for Current Post',
                                               'simpletags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                           name="posttags_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Terms for Current Post',
                                               'simpletags')); ?>"/>
                                <?php } ?>

                                <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                       value="<?php echo esc_attr($tab); ?>"/>
                            </p>

                            <?php if (!empty($current)) {
                            ?>
                            <p>
                                <?php echo '<div class="taxopress-warning" style="">' . __('Shortcode: ',
                                        'simpletags'); ?> &nbsp;
                                <textarea
                                    style="resize: none;padding: 5px;">[taxopress_postterms id="<?php echo $current['ID']; ?>"]</textarea>
                        </div>
                        </p>
                        <?php } ?>

                        <?php
                        }
                        ?>

                    </div>

            </div>

            <div class="clear"></div>


            </form>

        </div><!-- End .wrap -->

        <div class="clear"></div>
        <?php
    }

}
