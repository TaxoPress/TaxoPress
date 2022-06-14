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
            esc_html__('Terms for Current Post', 'simple-tags'),
            esc_html__('Current Post', 'simple-tags'),
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
            'label'   => esc_html__('Number of items per page', 'simple-tags'),
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
                <h1 class="wp-heading-inline"><?php _e('Terms for Current Post', 'simple-tags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_post_tags&add=new_item')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simple-tags'); ?></a>

                <div class="taxopress-description"><?php _e('This feature allows you create a customizable display of all the terms assigned to the current post.', 'simple-tags'); ?></div>


                <?php
                if (isset($_REQUEST['s']) && $search = sanitize_text_field(wp_unslash($_REQUEST['s']))) {
                    /* translators: %s: search keywords */
                    printf(' <span class="subtitle">' . esc_html__('Search results for &#8220;%s&#8221;', 'simple-tags') . '</span>', esc_html($search));
                }
                ?>
                <?php

                //the terms table instance
                $this->terms_table->prepare_items();
                ?>


                <hr class="wp-header-end">
                <div id="ajax-response"></div>
                <form class="search-form wp-clearfix st-taxonomies-search-form" method="get">
                    <?php $this->terms_table->search_box(esc_html__('Search Terms for Current Post', 'simple-tags'), 'term'); ?>
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
                <h1><?php echo esc_html__('Manage Terms for Current Post', 'simple-tags'); ?></h1>
                <div class="wp-clearfix"></div>

                <form method="post" action="">


                <div class="tagcloudui st-tabbed">


<div class="posttags-postbox-container">
    <div id="poststuff">
        <div class="taxopress-section postbox">
            <div class="postbox-header">
                <h2 class="hndle ui-sortable-handle">
                    <?php
                    if ($post_tags_edit) {
                        $active_tab = ( isset($current['active_tab']) && !empty(trim($current['active_tab'])) ) ? $current['active_tab'] : 'posttags_general';
                        echo esc_html__('Edit Terms for Current Post', 'simple-tags');
                        echo '<input type="hidden" name="edited_posttags" value="' . esc_attr($current['ID']) . '" />';
                        echo '<input type="hidden" name="taxopress_post_tags[ID]" value="' . esc_attr($current['ID']) . '" />';
                        echo '<input type="hidden" name="taxopress_post_tags[active_tab]" class="taxopress-active-subtab" value="'.esc_attr($active_tab).'" />';
                    } else {
                        $active_tab = 'posttags_general';
                        echo '<input type="hidden" name="taxopress_post_tags[active_tab]" class="taxopress-active-subtab" value="" />';
                        echo esc_html__('Add new Terms for Current Post', 'simple-tags');
                    }
                    ?>
                </h2>
            </div>
            <div class="inside">
                <div class="main">


                    <?php if ($post_tags_limit) {
                        echo '<div class="taxopress-warning upgrade-pro">
                        <p>

                        <h2 style="margin-bottom: 5px;">' . esc_html__('To create more Terms for Current Post, please upgrade to TaxoPress Pro.',
                                        'simple-tags') . '</h2>
                        ' . esc_html__('With TaxoPress Pro, you can create unlimited Terms for Current Post. You can create Terms for Current Post for any taxonomy and then display those Terms for Current Post anywhere on your site.',
                                        'simple-tags') . '

                        </p>
                        </div>';

                    } else {
                        ?>


                        <ul class="taxopress-tab">
                            <li class="posttags_general_tab <?php echo $active_tab === 'posttags_general' ? 'active' : ''; ?>" data-content="posttags_general">
                                <a href="#posttags_general"><span><?php esc_html_e('General',
                                            'simple-tags'); ?></span></a>
                            </li>

                            <li class="posttags_display_tab <?php echo $active_tab === 'posttags_display' ? 'active' : ''; ?>" data-content="posttags_display">
                                <a href="#posttags_display"><span><?php esc_html_e('Display',
                                            'simple-tags'); ?></span></a>
                            </li>

                            <li class="posttags_options_tab <?php echo $active_tab === 'posttags_options' ? 'active' : ''; ?>" data-content="posttags_options">
                                <a href="#posttags_options"><span><?php esc_html_e('Options',
                                            'simple-tags'); ?></span></a>
                            </li>

                            <li class="posttags_advanced_tab <?php echo $active_tab === 'posttags_advanced' ? 'active' : ''; ?>" data-content="posttags_advanced">
                                <a href="#posttags_advanced"><span><?php esc_html_e('Advanced',
                                            'simple-tags'); ?></span></a>
                            </li>

                        </ul>

                        <div class="st-taxonomy-content taxopress-tab-content">


                            <table class="form-table taxopress-table posttags_general"
                                   style="<?php echo $active_tab === 'posttags_general' ? '' : 'display:none;'; ?>">
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
                                                                    'text'    => $tax->labels->name. ' ('.$tax->name.')',
                                                                    'default' => 'true'
                                                                ];
                                                            } else {
                                                                $other_option[] = [
                                                                    'attr' => $tax->name,
                                                                    'text' => $tax->labels->name. ' ('.$tax->name.')'
                                                                ];
                                                            }
                                                        }
                                                        $options = array_merge($main_option, $other_option);

                                                        $select             = [
                                                            'options' => $options,
                                                        ];
                                                        $selected           = isset($current) ? taxopress_disp_boolean($current['taxonomy']) : '';
                                                        $select['selected'] = !empty($selected) ? $current['taxonomy'] : '';
                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_select_checkbox_input_main([
                                                            'namearray'  => 'taxopress_post_tags',
                                                            'name'       => 'taxonomy',
                                                            'labeltext'  => esc_html__('Taxonomy', 'simple-tags'),
                                                            'required'   => true,
                                                            'selections' => $select, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);


                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                            echo $ui->get_td_end() . $ui->get_tr_end();
                                ?>
                            </table>




                            <table class="form-table taxopress-table posttags_display"
                                   style="<?php echo $active_tab === 'posttags_display' ? '' : 'display:none;'; ?>">
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

                                                        $term_auto_locations = [
                                                            'homeonly' => esc_attr__('Homepage', 'simple-tags'),
                                                            'blogonly' => esc_attr__('Blog display', 'simple-tags'),
                                                        ];
                                                        foreach ($post_types as $post_type) {
                                                            $term_auto_locations[$post_type->name] = $post_type->label;
                                                        }

                                                        echo '<tr valign="top"><th scope="row"><label>' . esc_html__('Attempt to automatically display terms',
                                                                'simple-tags') . '</label><br /><small style=" color: #646970;">' . esc_html__('TaxoPress will attempt to automatically display terms in this content. It may not be successful for all post types and layouts.',
                                                                'simple-tags') . '</small></th><td>
                                                                <table class="visbile-table">';
                                                        foreach ($term_auto_locations as $key => $value) {


                                                            echo '<tr valign="top"><th scope="row"><label for="' . esc_attr($key) . '">' . esc_html($value) . '</label></th><td>';

                                                            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                            echo $ui->get_check_input([
                                                                'checkvalue' => $key,
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
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'number',
                                                            'textvalue' => isset($current['number']) ? esc_attr($current['number']) : '0',
                                                            'labeltext' => esc_html__('Maximum terms to display',
                                                                'simple-tags'),
                                                            'helptext'  => esc_html__('You must set zero (0) to display all post tags.',
                                                            'simple-tags'),
                                                            'min'       => '0',
                                                            'required'  => true,
                                                        ]);

                                ?>
                            </table>


                            <table class="form-table taxopress-table posttags_options"
                                   style="<?php echo $active_tab === 'posttags_options' ? '' : 'display:none;'; ?>">
                                <?php

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'separator',
                                                            'textvalue' => isset($current['separator']) ? esc_attr($current['separator']) : ', ',
                                                            'labeltext' => esc_html__('Post term separator string:	',
                                                                'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'before',
                                                            'textvalue' => isset($current['before']) ? esc_attr($current['before']) : 'Tags: ',
                                                            'labeltext' => esc_html__('Text to display before terms list',
                                                                'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'after',
                                                            'textvalue' => isset($current['after']) ? esc_attr($current['after']) : '<br />',
                                                            'labeltext' => esc_html__('Text to display after terms list',
                                                                'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'notagtext',
                                                            'textvalue' => isset($current['notagtext']) ? esc_attr($current['notagtext']) : 'No tags for this post.',
                                                            'labeltext' => esc_html__('Text to display if no terms found',
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
                                                            'namearray'  => 'taxopress_post_tags',
                                                            'name'       => 'hide_output',
                                                            'labeltext'  => esc_html__('Hide display output if no terms ?',
                                                                'simple-tags'),
                                                            'selections' => $select, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ]);
                                ?>

                            </table>


                            <table class="form-table taxopress-table posttags_advanced"
                                   style="<?php echo $active_tab === 'posttags_advanced' ? '' : 'display:none;'; ?>">
                                   <?php


                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'wrap_class',
                                                            'class'     => '',
                                                            'textvalue' => isset($current['wrap_class']) ? esc_attr($current['wrap_class']) : '',
                                                            'labeltext' => esc_html__('Terms for Current Post div class', 'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_text_input([
                                                            'namearray' => 'taxopress_post_tags',
                                                            'name'      => 'link_class',
                                                            'class'     => '',
                                                            'textvalue' => isset($current['link_class']) ? esc_attr($current['link_class']) : '',
                                                            'labeltext' => esc_html__('Term link class', 'simple-tags'),
                                                            'helptext'  => '',
                                                            'required'  => false,
                                                        ]);

                                                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        echo $ui->get_textarea_input([
                                                                'namearray' => 'taxopress_post_tags',
                                                                'name'      => 'xformat',
                                                                'class'     => 'st-full-width',
                                                                'rows'      => '4',
                                                                'cols'      => '40',
                                                                'textvalue' => isset($current['xformat']) ? esc_attr($current['xformat']) : esc_attr('<a href="%tag_link%" title="%tag_name%" %tag_rel%>%tag_name%</a>'),
                                                            'labeltext' => esc_html__('Term link format', 'simple-tags'),
                                                            'helptext'  => sprintf(esc_html__('You can find markers and explanations %1sin the documentation%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/format-terms-current-post/">', '</a>'),
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


                                <?php if ($post_tags_limit) { ?>

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
                            if (!$post_tags_limit){ ?>
                            <p class="submit">

                                <?php
                                wp_nonce_field('taxopress_addedit_posttags_nonce_action',
                                    'taxopress_addedit_posttags_nonce_field');
                                if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit  taxopress-posttags-submit"
                                           name="posttags_submit"
                                           value="<?php echo esc_attr(esc_attr__('Save Terms for Current Post',
                                               'simple-tags')); ?>"/>
                                    <?php
                                } else { ?>
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit  taxopress-posttags-submit"
                                           name="posttags_submit"
                                           value="<?php echo esc_attr(esc_attr__('Add Terms for Current Post',
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
                                    style="resize: none;padding: 5px;" readonly>[taxopress_postterms id="<?php echo (int)$current['ID']; ?>"]</textarea>
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
                            <span><?php echo esc_html__('Terms for Current Post', 'simple-tags'); ?></span>
                        </h3>
                    </div>

                    <div class="inside">
                        <p><?php echo esc_html__('Here are the tokens you can use for Terms for Current Post format', 'simple-tags'); ?>:</p>
                        <ul>
                            <li><code>%tag_link%</code><?php echo esc_html__('The URL of the tag', 'simple-tags'); ?></li>
                            <li><code>%tag_name%</code><?php echo esc_html__('The name of the tag', 'simple-tags'); ?></li>
                            <li><code>%tag_rel% </code><?php echo esc_html__('This provides rel tag markup', 'simple-tags'); ?> (it creates <code>rel="tag"</code>)</li>
                            <li><code>%tag_feed%</code><?php echo esc_html__('Replaced by the RSS tag link', 'simple-tags'); ?></li>
                            <li><code>%tag_id%</code><?php echo esc_html__('Replaced by the tag ID', 'simple-tags'); ?></li>
                            <li><code>%tag_name_attribute%</code><?php echo esc_html__('Replaced by the tag’s name, formatted for attribute HTML', 'simple-tags'); ?></li>
                        </ul>
                        <p><?php echo esc_html__('You can also add HTML elements to the formatting.', 'simple-tags'); ?></p>
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
