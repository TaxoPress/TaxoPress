<?php

class SimpleTags_Admin_Taxonomies
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

        //delete_option( 'taxopress_taxonomies' );
    }

    /**
     * Init somes JS and CSS need for this feature
     *
     * @return void
     * @author Olatechpro
     */
    public static function admin_enqueue_scripts()
    {
        wp_register_script('st-taxonomies', STAGS_URL . '/assets/js/taxonomies.js',
            ['jquery', 'jquery-ui-dialog', 'postbox'], STAGS_VERSION);
        wp_register_style('st-taxonomies-css', STAGS_URL . '/assets/css/taxonomies.css', ['wp-jquery-ui-dialog'],
            STAGS_VERSION, 'all');

        // add JS for manage click tags
        if (isset($_GET['page']) && $_GET['page'] == 'st_taxonomies') {
            wp_enqueue_script('st-taxonomies');
            wp_enqueue_style('st-taxonomies-css');


            $core                  = get_taxonomies(['_builtin' => true]);
            $public                = get_taxonomies([
                '_builtin' => false,
                'public'   => true,
            ]);
            $private               = get_taxonomies([
                '_builtin' => false,
                'public'   => false,
            ]);
            $registered_taxonomies = array_merge($core, $public, $private);
            wp_localize_script('st-taxonomies', 'taxopress_tax_data',
                [
                    'confirm'             => esc_html__('Are you sure you want to delete this? Deleting will NOT remove created content.',
                        'simpletags'),
                    'no_associated_type'  => esc_html__('Please select a post type to associate with.', 'simpletags'),
                    'existing_taxonomies' => $registered_taxonomies,
                ]
            );

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
            __('Taxonomies', 'simpletags'),
            __('Taxonomies', 'simpletags'),
            'simple_tags',
            'st_taxonomies',
            [
                $this,
                'page_manage_taxonomies',
            ]
        );

        add_action("load-$hook", [$this, 'screen_option']);
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
            'option'  => 'st_taxonomies_per_page'
        ];

        add_screen_option($option, $args);

        $this->terms_table = new Taxonomy_List();
    }

    /**
     * Method for build the page HTML manage tags
     *
     * @return void
     * @author Olatechpro
     */
    public function page_manage_taxonomies()
    {
        // Default order
        if (!isset($_GET['order'])) {
            $_GET['order'] = 'name-asc';
        }

        settings_errors(__CLASS__);


        //delete_option('taxopress_taxonomies');
//    var_dump(get_all_taxopress_taxonomies());

        if (!isset($_GET['add'])) {
            //all tax
            ?>
            <div class="wrap st_wrap st-manage-terms-page">

            <div id="">
                <h1 class="wp-heading-inline"><?php _e('Taxonomies', 'simpletags'); ?></h1>
                <a href="<?php echo esc_url(admin_url('admin.php?page=st_taxonomies&add=taxonomy')); ?>"
                   class="page-title-action"><?php esc_html_e('Add New', 'simpletags'); ?></a>


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
                <form class="search-form wp-clearfix st-tag-cloud-search-form" method="get">
                    <?php $this->terms_table->search_box(__('Search Taxonomies', 'simpletags'), 'term'); ?>
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
            if ($_GET['add'] == 'taxonomy') {
                //add/edit taxonomy
                taxopress_manage_taxonomies();
                echo '<div>';
            }
        } ?>


        <?php SimpleTags_Admin::printAdminFooter(); ?>
        </div>
        <?php
        do_action('simpletags-taxonomies', SimpleTags_Admin::$taxonomy);
    }

}


//custom functions and methods


/**
 * Create our settings page output.
 *
 * @internal
 */
function taxopress_manage_taxonomies()
{

    $tab       = (!empty($_GET) && !empty($_GET['action']) && 'edit' == $_GET['action']) ? 'edit' : 'new';
    $tab_class = 'taxopress-' . $tab;
    $current   = null;

    ?>

<div class="wrap <?php echo esc_attr($tab_class); ?>">

    <?php
    /**
     * Fires right inside the wrap div for the taxonomy editor screen.
     */
    do_action('taxopress_inside_taxonomy_wrap');

    /**
     * Filters whether or not a taxonomy was deleted.
     *
     * @param bool $value Whether or not taxonomy deleted. Default false.
     */
    $taxonomy_deleted = apply_filters('taxopress_taxonomy_deleted', false);

    /**
     * Fires below the output for the tab menu on the taxonomy add/edit screen.
     */
    do_action('taxopress_below_taxonomy_tab_menu');

    $external_edit = false;
    if ('edit' === $tab) {

        $taxonomies = taxopress_get_taxonomy_data();

        $selected_taxonomy = taxopress_get_current_taxonomy($taxonomy_deleted);
        $request_tax       = sanitize_text_field($_GET['taxopress_taxonomy']);

        if ($selected_taxonomy && array_key_exists($selected_taxonomy, $taxonomies)) {
            $current = $taxonomies[$selected_taxonomy];
        } elseif (taxonomy_exists($request_tax)) {
            //not out taxonomy
            $external_taxonomy = get_taxonomies(['name' => $request_tax], 'objects');
            if (isset($external_taxonomy) > 0) {
                $current       = taxopress_convert_external_taxonomy($external_taxonomy[$request_tax], $request_tax);
                $external_edit = true;
            }
        }
    }


    $ui = new taxopress_admin_ui();
    ?>


    <div class="wrap <?php echo esc_attr($tab_class); ?>">
        <h1><?php echo __('Add/Edit Taxonomies', 'simpletags'); ?></h1>
        <div class="wp-clearfix"></div>

        <form method="post" action="<?php echo esc_url(taxopress_get_post_form_action($ui)); ?>">

            <div class="taxopress-right-sidebar">
                <div class="taxopress-right-sidebar-wrapper">


                    <p class="submit">

                        <?php
                        wp_nonce_field('taxopress_addedit_taxonomy_nonce_action',
                            'taxopress_addedit_taxonomy_nonce_field');
                        if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>


                            <?php

                            $activate_action_link   = add_query_arg(
                                [
                                    'page'               => 'st_taxonomies',
                                    'add'                => 'taxonomy',
                                    'action'             => 'edit',
                                    'action2'            => 'taxopress-reactivate-taxonomy',
                                    'taxonomy'           => esc_attr($request_tax),
                                    '_wpnonce'           => wp_create_nonce('taxonomy-action-request-nonce'),
                                    'taxopress_taxonomy' => $request_tax,
                                ],
                                taxopress_admin_url('admin.php')
                            );
                            $deactivate_action_link = add_query_arg(
                                [
                                    'page'               => 'st_taxonomies',
                                    'add'                => 'taxonomy',
                                    'action'             => 'edit',
                                    'action2'            => 'taxopress-deactivate-taxonomy',
                                    'taxonomy'           => esc_attr($request_tax),
                                    '_wpnonce'           => wp_create_nonce('taxonomy-action-request-nonce'),
                                    'taxopress_taxonomy' => $request_tax,
                                ],
                                taxopress_admin_url('admin.php')
                            );

                            if (in_array($request_tax, taxopress_get_deactivated_taxonomy())) {
                                ?>
                                <span class="action-button reactivate"><a
                                        href="<?php echo $activate_action_link; ?>"><?php echo __('Re-activate Taxonomy',
                                            'simpletags'); ?></a></span>
                            <?php } else { ?>
                                <span class="action-button deactivate"><a
                                        href="<?php echo $deactivate_action_link; ?>"><?php echo __('Deactivate Taxonomy',
                                            'simpletags'); ?></a></span>
                            <?php } ?>

                            <?php

                            /**
                             * Filters the text value to use on the button when editing.
                             *
                             * @param string $value Text to use for the button.
                             */
                            ?>
                            <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                   value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_edit',
                                       esc_attr__('Save Taxonomy', 'simpletags'))); ?>"/>
                            <?php

                            /**
                             * Filters the text value to use on the button when deleting.
                             *
                             * @param string $value Text to use for the button.
                             */
                            if (!$external_edit) {
                                ?>
                                <input type="submit" class="button-secondary taxopress-delete-bottom" name="cpt_delete"
                                       id="cpt_submit_delete"
                                       value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_delete',
                                           __('Delete Taxonomy', 'simpletags'))); ?>"/>
                            <?php }
                        } else { ?>
                            <?php

                            /**
                             * Filters the text value to use on the button when adding.
                             *
                             * @param string $value Text to use for the button.
                             */
                            ?>
                            <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                   value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_add',
                                       esc_attr__('Add Taxonomy', 'simpletags'))); ?>"/>
                        <?php } ?>

                        <?php if (!empty($current)) { ?>
                            <input type="hidden" name="tax_original" id="tax_original"
                                   value="<?php echo esc_attr($current['name']); ?>"/>
                            <?php
                        }

                        // Used to check and see if we should prevent duplicate slugs.
                        ?>
                        <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                               value="<?php echo esc_attr($tab); ?>"/>
                    </p>


                </div>

            </div>

            <?php
            if ($external_edit) {
                echo '<input type="hidden" name="taxonomy_external_edit" class="taxonomy_external_edit" value="1" />';
            }
            ?>
            <div class="taxonomiesui">
                <div class="postbox-container">
                    <div id="poststuff">
                        <div class="taxopress-section postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php esc_html_e('Basic settings', 'simpletags'); ?></span>
                                </h2>
                                <div class="handle-actions hide-if-no-js">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Toggle panel: Basic settings',
                                                'simpletags'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <div class="main">
                                    <?php
                                    if ($external_edit) {
                                        echo '<div class="taxopress-warning">' . __('This is an external taxonomy and not created with taxopress.',
                                                'simpletags') . '</div>';
                                    }
                                    ?>
                                    <table class="form-table taxopress-table">
                                        <?php
                                        echo $ui->get_tr_start() . $ui->get_th_start();
                                        echo $ui->get_label('name',
                                                esc_html__('Taxonomy Slug', 'simpletags')) . $ui->get_required_span();

                                        if ('edit' === $tab) {
                                            echo '<p id="slugchanged" class="hidemessage">' . esc_html__('Slug has changed',
                                                    'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';
                                        }
                                        echo '<p id="slugexists" class="hidemessage">' . esc_html__('Slug already exists',
                                                'simpletags') . '<span class="dashicons dashicons-warning"></span></p>';

                                        echo $ui->get_th_end() . $ui->get_td_start();

                                        echo $ui->get_text_input([
                                            'namearray'   => 'cpt_custom_tax',
                                            'name'        => 'name',
                                            'textvalue'   => isset($current['name']) ? esc_attr($current['name']) : '',
                                            'maxlength'   => '32',
                                            'helptext'    => esc_attr__('The taxonomy name/slug. Used for various queries for taxonomy content.',
                                                'simpletags'),
                                            'required'    => true,
                                            'placeholder' => false,
                                            'wrap'        => false,
                                        ]);

                                        echo '<p class="taxopress-slug-details">';
                                        esc_html_e('Slugs should only contain alphanumeric, latin characters. Underscores should be used in place of spaces. Set "Custom Rewrite Slug" field to make slug use dashes for URLs.',
                                            'simpletags');
                                        echo '</p>';

                                        if ('edit' === $tab) {
                                            echo '<p>';
                                            esc_html_e('DO NOT EDIT the taxonomy slug unless also planning to migrate terms. Changing the slug registers a new taxonomy entry.',
                                                'simpletags');
                                            echo '</p>';

                                            echo '<div class="taxopress-spacer">';
                                            echo $ui->get_check_input([
                                                'checkvalue' => 'update_taxonomy',
                                                'checked'    => 'false',
                                                'name'       => 'update_taxonomy',
                                                'namearray'  => 'update_taxonomy',
                                                'labeltext'  => esc_html__('Migrate terms to newly renamed taxonomy?',
                                                    'simpletags'),
                                                'helptext'   => '',
                                                'default'    => false,
                                                'wrap'       => false,
                                            ]);
                                            echo '</div>';
                                        }
                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'label',
                                            'textvalue' => isset($current['label']) ? esc_attr($current['label']) : '',
                                            'aftertext' => esc_html__('(e.g. Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('Plural Label', 'simpletags'),
                                            'helptext'  => esc_attr__('Used for the taxonomy admin menu item.',
                                                'simpletags'),
                                            'required'  => true,
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'singular_label',
                                            'textvalue' => isset($current['singular_label']) ? esc_attr($current['singular_label']) : '',
                                            'aftertext' => esc_html__('(e.g. Actor)', 'simpletags'),
                                            'labeltext' => esc_html__('Singular Label', 'simpletags'),
                                            'helptext'  => esc_attr__('Used when a singular label is needed.',
                                                'simpletags'),
                                            'required'  => true,
                                        ]);
                                        echo $ui->get_td_end() . $ui->get_tr_end();


                                        echo $ui->get_tr_start() . $ui->get_th_start() . esc_html__('Attach to Post Type',
                                                'simpletags') . $ui->get_required_span();
                                        echo $ui->get_p(esc_html__('Add support for available registered post types. At least one is required. Only public post types listed by default.',
                                            'simpletags'));
                                        echo $ui->get_th_end() . $ui->get_td_start() . $ui->get_fieldset_start();

                                        echo $ui->get_legend_start() . esc_html__('Post type options',
                                                'simpletags') . $ui->get_legend_end();

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

                                        foreach ($post_types as $post_type) {
                                            $core_label = in_array($post_type->name, [
                                                'post',
                                                'page',
                                                'attachment',
                                            ], true) ? esc_html__('(WP Core)', 'simpletags') : '';
                                            echo $ui->get_check_input([
                                                'checkvalue' => $post_type->name,
                                                'checked'    => (!empty($current['object_types']) && is_array($current['object_types']) && in_array($post_type->name,
                                                        $current['object_types'], true)) ? 'true' : 'false',
                                                'name'       => $post_type->name,
                                                'namearray'  => 'cpt_post_types',
                                                'textvalue'  => $post_type->name,
                                                'labeltext'  => "{$post_type->label} {$core_label}",
                                                'wrap'       => false,
                                            ]);
                                        }

                                        echo $ui->get_fieldset_end() . $ui->get_td_end() . $ui->get_tr_end();
                                        ?>
                                    </table>
                                    <p class="submit">
                                        <?php
                                        wp_nonce_field('taxopress_addedit_taxonomy_nonce_action',
                                            'taxopress_addedit_taxonomy_nonce_field');
                                        if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) {

                                            /**
                                             * Filters the text value to use on the button when editing.
                                             *
                                             *
                                             * @param string $value Text to use for the button.
                                             */
                                            ?>
                                            <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                                   name="cpt_submit"
                                                   value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_edit',
                                                       esc_attr__('Save Taxonomy', 'simpletags'))); ?>"/>
                                            <?php

                                            /**
                                             * Filters the text value to use on the button when deleting.
                                             *
                                             *
                                             * @param string $value Text to use for the button.
                                             */
                                            if (!$external_edit) {
                                                ?>
                                                <input type="submit" class="button-secondary taxopress-delete-top"
                                                       name="cpt_delete" id="cpt_submit_delete"
                                                       value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_delete',
                                                           __('Delete Taxonomy', 'simpletags'))); ?>"/>
                                            <?php }
                                        } else { ?>
                                            <?php

                                            /**
                                             * Filters the text value to use on the button when adding.
                                             *
                                             *
                                             * @param string $value Text to use for the button.
                                             */
                                            ?>
                                            <input type="submit" class="button-primary taxopress-taxonomy-submit"
                                                   name="cpt_submit"
                                                   value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_add',
                                                       esc_attr__('Add Taxonomy', 'simpletags'))); ?>"/>
                                        <?php } ?>

                                        <?php if (!empty($current)) { ?>
                                            <input type="hidden" name="tax_original" id="tax_original"
                                                   value="<?php echo esc_attr($current['name']); ?>"/>
                                            <?php
                                        }

                                        // Used to check and see if we should prevent duplicate slugs.
                                        ?>
                                        <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                               value="<?php echo esc_attr($tab); ?>"/>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="taxopress-section taxopress-labels postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php esc_html_e('Additional labels', 'simpletags'); ?></span>
                                </h2>
                                <div class="handle-actions hide-if-no-js">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span
                                            class="screen-reader-text"><?php esc_html_e('Toggle panel: Additional labels',
                                                'simpletags'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <div class="main">
                                    <table class="form-table taxopress-table">

                                        <?php
                                        if (isset($current['description'])) {
                                            $current['description'] = stripslashes_deep($current['description']);
                                        }
                                        echo $ui->get_textarea_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'description',
                                            'rows'      => '4',
                                            'cols'      => '40',
                                            'textvalue' => isset($current['description']) ? esc_textarea($current['description']) : '',
                                            'labeltext' => esc_html__('Description', 'simpletags'),
                                            'helptext'  => esc_attr__('Describe what your taxonomy is used for.',
                                                'simpletags'),
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'menu_name',
                                            'textvalue' => isset($current['labels']['menu_name']) ? esc_attr($current['labels']['menu_name']) : '',
                                            'aftertext' => esc_attr__('(e.g. Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('Menu Name', 'simpletags'),
                                            'helptext'  => esc_html__('Custom admin menu name for your taxonomy.',
                                                'simpletags'),
                                            'data'      => [
                                                'label'     => 'item', // Not localizing because it's isolated.
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'all_items',
                                            'textvalue' => isset($current['labels']['all_items']) ? esc_attr($current['labels']['all_items']) : '',
                                            'aftertext' => esc_attr__('(e.g. All Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('All Items', 'simpletags'),
                                            'helptext'  => esc_html__('Used as tab text when showing all terms for hierarchical taxonomy while editing post.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('All %s', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'edit_item',
                                            'textvalue' => isset($current['labels']['edit_item']) ? esc_attr($current['labels']['edit_item']) : '',
                                            'aftertext' => esc_attr__('(e.g. Edit Actor)', 'simpletags'),
                                            'labeltext' => esc_html__('Edit Item', 'simpletags'),
                                            'helptext'  => esc_html__('Used at the top of the term editor screen for an existing taxonomy term.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Edit %s', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'view_item',
                                            'textvalue' => isset($current['labels']['view_item']) ? esc_attr($current['labels']['view_item']) : '',
                                            'aftertext' => esc_attr__('(e.g. View Actor)', 'simpletags'),
                                            'labeltext' => esc_html__('View Item', 'simpletags'),
                                            'helptext'  => esc_html__('Used in the admin bar when viewing editor screen for an existing taxonomy term.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('View %s', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'update_item',
                                            'textvalue' => isset($current['labels']['update_item']) ? esc_attr($current['labels']['update_item']) : '',
                                            'aftertext' => esc_attr__('(e.g. Update Actor Name)', 'simpletags'),
                                            'labeltext' => esc_html__('Update Item Name', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Update %s name', 'simpletags'),
                                                    'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'add_new_item',
                                            'textvalue' => isset($current['labels']['add_new_item']) ? esc_attr($current['labels']['add_new_item']) : '',
                                            'aftertext' => esc_attr__('(e.g. Add New Actor)', 'simpletags'),
                                            'labeltext' => esc_html__('Add New Item', 'simpletags'),
                                            'helptext'  => esc_html__('Used at the top of the term editor screen and button text for a new taxonomy term.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Add new %s', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'new_item_name',
                                            'textvalue' => isset($current['labels']['new_item_name']) ? esc_attr($current['labels']['new_item_name']) : '',
                                            'aftertext' => esc_attr__('(e.g. New Actor Name)', 'simpletags'),
                                            'labeltext' => esc_html__('New Item Name', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('New %s name', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'parent_item',
                                            'textvalue' => isset($current['labels']['parent_item']) ? esc_attr($current['labels']['parent_item']) : '',
                                            'aftertext' => esc_attr__('(e.g. Parent Actor)', 'simpletags'),
                                            'labeltext' => esc_html__('Parent Item', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Parent %s', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'parent_item_colon',
                                            'textvalue' => isset($current['labels']['parent_item_colon']) ? esc_attr($current['labels']['parent_item_colon']) : '',
                                            'aftertext' => esc_attr__('(e.g. Parent Actor:)', 'simpletags'),
                                            'labeltext' => esc_html__('Parent Item Colon', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Parent %s:', 'simpletags'), 'item'),
                                                'plurality' => 'singular',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'search_items',
                                            'textvalue' => isset($current['labels']['search_items']) ? esc_attr($current['labels']['search_items']) : '',
                                            'aftertext' => esc_attr__('(e.g. Search Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('Search Items', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Search %s', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'popular_items',
                                            'textvalue' => isset($current['labels']['popular_items']) ? esc_attr($current['labels']['popular_items']) : null,
                                            'aftertext' => esc_attr__('(e.g. Popular Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('Popular Items', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Popular %s', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'separate_items_with_commas',
                                            'textvalue' => isset($current['labels']['separate_items_with_commas']) ? esc_attr($current['labels']['separate_items_with_commas']) : null,
                                            'aftertext' => esc_attr__('(e.g. Separate Actors with commas)',
                                                'simpletags'),
                                            'labeltext' => esc_html__('Separate Items with Commas', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Separate %s with commas',
                                                    'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'add_or_remove_items',
                                            'textvalue' => isset($current['labels']['add_or_remove_items']) ? esc_attr($current['labels']['add_or_remove_items']) : null,
                                            'aftertext' => esc_attr__('(e.g. Add or remove Actors)', 'simpletags'),
                                            'labeltext' => esc_html__('Add or Remove Items', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Add or remove %s', 'simpletags'),
                                                    'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'choose_from_most_used',
                                            'textvalue' => isset($current['labels']['choose_from_most_used']) ? esc_attr($current['labels']['choose_from_most_used']) : null,
                                            'aftertext' => esc_attr__('(e.g. Choose from the most used Actors)',
                                                'simpletags'),
                                            'labeltext' => esc_html__('Choose From Most Used', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Choose from the most used %s',
                                                    'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'not_found',
                                            'textvalue' => isset($current['labels']['not_found']) ? esc_attr($current['labels']['not_found']) : null,
                                            'aftertext' => esc_attr__('(e.g. No Actors found)', 'simpletags'),
                                            'labeltext' => esc_html__('Not found', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy label. Used in the admin menu for displaying taxonomies.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('No %s found', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'no_terms',
                                            'textvalue' => isset($current['labels']['no_terms']) ? esc_attr($current['labels']['no_terms']) : null,
                                            'aftertext' => esc_html__('(e.g. No actors)', 'simpletags'),
                                            'labeltext' => esc_html__('No terms', 'simpletags'),
                                            'helptext'  => esc_attr__('Used when indicating that there are no terms in the given taxonomy associated with an object.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('No %s', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'items_list_navigation',
                                            'textvalue' => isset($current['labels']['items_list_navigation']) ? esc_attr($current['labels']['items_list_navigation']) : null,
                                            'aftertext' => esc_html__('(e.g. Actors list navigation)', 'simpletags'),
                                            'labeltext' => esc_html__('Items List Navigation', 'simpletags'),
                                            'helptext'  => esc_attr__('Screen reader text for the pagination heading on the term listing screen.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('%s list navigation', 'simpletags'),
                                                    'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'items_list',
                                            'textvalue' => isset($current['labels']['items_list']) ? esc_attr($current['labels']['items_list']) : null,
                                            'aftertext' => esc_html__('(e.g. Actors list)', 'simpletags'),
                                            'labeltext' => esc_html__('Items List', 'simpletags'),
                                            'helptext'  => esc_attr__('Screen reader text for the items list heading on the term listing screen.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('%s list', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'not_found',
                                            'textvalue' => isset($current['labels']['not_found']) ? esc_attr($current['labels']['not_found']) : null,
                                            'aftertext' => esc_html__('(e.g. No actors found)', 'simpletags'),
                                            'labeltext' => esc_html__('Not Found', 'simpletags'),
                                            'helptext'  => esc_attr__('The text displayed via clicking ‘Choose from the most used items’ in the taxonomy meta box when no items are available.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('No %s found', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_tax_labels',
                                            'name'      => 'back_to_items',
                                            'textvalue' => isset($current['labels']['back_to_items']) ? esc_attr($current['labels']['back_to_items']) : null,
                                            'aftertext' => esc_html__('(e.g. &larr; Back to actors', 'simpletags'),
                                            'labeltext' => esc_html__('Back to Items', 'simpletags'),
                                            'helptext'  => esc_attr__('The text displayed after a term has been updated for a link back to main index.',
                                                'simpletags'),
                                            'data'      => [
                                                /* translators: Used for autofill */
                                                'label'     => sprintf(esc_attr__('Back to %s', 'simpletags'), 'item'),
                                                'plurality' => 'plural',
                                            ],
                                        ]);
                                        ?>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="taxopress-section taxopress-settings postbox">
                            <div class="postbox-header">
                                <h2 class="hndle ui-sortable-handle">
                                    <span><?php esc_html_e('Settings', 'simpletags'); ?></span>
                                </h2>
                                <div class="handle-actions hide-if-no-js">
                                    <button type="button" class="handlediv" aria-expanded="true">
                                        <span class="screen-reader-text"><?php esc_html_e('Toggle panel: Settings',
                                                'simpletags'); ?></span>
                                        <span class="toggle-indicator" aria-hidden="true"></span>
                                    </button>
                                </div>
                            </div>
                            <div class="inside">
                                <div class="main">
                                    <table class="form-table taxopress-table">
                                        <?php

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['public']) : '';
                                        $select['selected'] = !empty($selected) ? $current['public'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'public',
                                            'labeltext'  => esc_html__('Public', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: true) Whether a taxonomy is intended for use publicly either via the admin interface or by front-end users.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['publicly_queryable']) : '';
                                        $select['selected'] = !empty($selected) ? $current['publicly_queryable'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'publicly_queryable',
                                            'labeltext'  => esc_html__('Public Queryable', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: value of "public" setting) Whether or not the taxonomy should be publicly queryable.',
                                                'simpletags'),
                                            'selections' => $select,
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
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['hierarchical']) : '';
                                        $select['selected'] = !empty($selected) ? $current['hierarchical'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'hierarchical',
                                            'labeltext'  => esc_html__('Hierarchical', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: false) Whether the taxonomy can have parent-child relationships.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['show_ui']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_ui'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_ui',
                                            'labeltext'  => esc_html__('Show UI', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: true) Whether to generate a default UI for managing this custom taxonomy.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['show_in_menu']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_in_menu'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_in_menu',
                                            'labeltext'  => esc_html__('Show in menu', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: value of show_ui) Whether to show the taxonomy in the admin menu.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = (isset($current) && !empty($current['show_in_nav_menus'])) ? taxopress_disp_boolean($current['show_in_nav_menus']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_in_nav_menus'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_in_nav_menus',
                                            'labeltext'  => esc_html__('Show in nav menus', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: value of public) Whether to make the taxonomy available for selection in navigation menus.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['query_var']) : '';
                                        $select['selected'] = !empty($selected) ? $current['query_var'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'query_var',
                                            'labeltext'  => esc_html__('Query Var', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: true) Sets the query_var key for this taxonomy.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'query_var_slug',
                                            'textvalue' => isset($current['query_var_slug']) ? esc_attr($current['query_var_slug']) : '',
                                            'aftertext' => esc_attr__('(default: taxonomy slug). Query var needs to be true to use.',
                                                'simpletags'),
                                            'labeltext' => esc_html__('Custom Query Var String', 'simpletags'),
                                            'helptext'  => esc_html__('Sets a custom query_var slug for this taxonomy.',
                                                'simpletags'),
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite']) : '';
                                        $select['selected'] = !empty($selected) ? $current['rewrite'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'rewrite',
                                            'labeltext'  => esc_html__('Rewrite', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: true) Whether or not WordPress should use rewrites for this taxonomy.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'rewrite_slug',
                                            'textvalue' => isset($current['rewrite_slug']) ? esc_attr($current['rewrite_slug']) : '',
                                            'aftertext' => esc_attr__('(default: taxonomy name)', 'simpletags'),
                                            'labeltext' => esc_html__('Custom Rewrite Slug', 'simpletags'),
                                            'helptext'  => esc_html__('Custom taxonomy rewrite slug.', 'simpletags'),
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite_withfront']) : '';
                                        $select['selected'] = !empty($selected) ? $current['rewrite_withfront'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'rewrite_withfront',
                                            'labeltext'  => esc_html__('Rewrite With Front', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: true) Should the permastruct be prepended with the front base.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr'    => '0',
                                                    'text'    => esc_attr__('False', 'simpletags'),
                                                    'default' => 'false',
                                                ],
                                                [
                                                    'attr' => '1',
                                                    'text' => esc_attr__('True', 'simpletags'),
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['rewrite_hierarchical']) : '';
                                        $select['selected'] = !empty($selected) ? $current['rewrite_hierarchical'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'rewrite_hierarchical',
                                            'labeltext'  => esc_html__('Rewrite Hierarchical', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: false) Should the permastruct allow hierarchical urls.',
                                                'simpletags'),
                                            'selections' => $select,
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
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['show_admin_column']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_admin_column'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_admin_column',
                                            'labeltext'  => esc_html__('Show Admin Column', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: false) Whether to allow automatic creation of taxonomy columns on associated post-types.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr' => '0',
                                                    'text' => esc_attr__('False', 'simpletags'),
                                                ],
                                                [
                                                    'attr'    => '1',
                                                    'text'    => esc_attr__('True', 'simpletags'),
                                                    'default' => 'true',
                                                ],
                                            ],
                                        ];
                                        $selected           = isset($current) ? taxopress_disp_boolean($current['show_in_rest']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_in_rest'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_in_rest',
                                            'labeltext'  => esc_html__('Show in REST API', 'simpletags'),
                                            'aftertext'  => esc_html__('(Custom Post Type UI default: true) Whether to show this taxonomy data in the WP REST API.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'rest_base',
                                            'labeltext' => esc_html__('REST API base slug', 'simpletags'),
                                            'helptext'  => esc_attr__('Slug to use in REST API URLs.', 'simpletags'),
                                            'textvalue' => isset($current['rest_base']) ? esc_attr($current['rest_base']) : '',
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'rest_controller_class',
                                            'labeltext' => esc_html__('REST API controller class', 'simpletags'),
                                            'aftertext' => esc_attr__('(default: WP_REST_Terms_Controller) Custom controller to use instead of WP_REST_Terms_Controller.',
                                                'simpletags'),
                                            'textvalue' => isset($current['rest_controller_class']) ? esc_attr($current['rest_controller_class']) : '',
                                        ]);

                                        $select             = [
                                            'options' => [
                                                [
                                                    'attr'    => '0',
                                                    'text'    => esc_attr__('False', 'simpletags'),
                                                    'default' => 'false',
                                                ],
                                                [
                                                    'attr' => '1',
                                                    'text' => esc_attr__('True', 'simpletags'),
                                                ],
                                            ],
                                        ];
                                        $selected           = (isset($current) && !empty($current['show_in_quick_edit'])) ? taxopress_disp_boolean($current['show_in_quick_edit']) : '';
                                        $select['selected'] = !empty($selected) ? $current['show_in_quick_edit'] : '';
                                        echo $ui->get_select_input([
                                            'namearray'  => 'cpt_custom_tax',
                                            'name'       => 'show_in_quick_edit',
                                            'labeltext'  => esc_html__('Show in quick/bulk edit panel.', 'simpletags'),
                                            'aftertext'  => esc_html__('(default: false) Whether to show the taxonomy in the quick/bulk edit panel.',
                                                'simpletags'),
                                            'selections' => $select,
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'meta_box_cb',
                                            'textvalue' => isset($current['meta_box_cb']) ? esc_attr($current['meta_box_cb']) : '',
                                            'labeltext' => esc_html__('Metabox callback', 'simpletags'),
                                            'helptext'  => esc_html__('Sets a callback function name for the meta box display. Hierarchical default: post_categories_meta_box, non-hierarchical default: post_tags_meta_box. To remove the metabox completely, use "false".',
                                                'simpletags'),
                                        ]);

                                        echo $ui->get_text_input([
                                            'namearray' => 'cpt_custom_tax',
                                            'name'      => 'default_term',
                                            'textvalue' => isset($current['default_term']) ? esc_attr($current['default_term']) : '',
                                            'labeltext' => esc_html__('Default Term', 'simpletags'),
                                            'helptext'  => esc_html__('Set a default term for the taxonomy. Able to set a name, slug, and description. Only a name is required if setting a default, others are optional. Set values in the following order, separated by comma. Example: name, slug, description',
                                                'simpletags'),
                                        ]);
                                        ?>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <?php
                        /**
                         * Fires after the default fieldsets on the taxonomy screen.
                         *
                         * @param taxopress_admin_ui $ui Admin UI instance.
                         */
                        do_action('taxopress_taxonomy_after_fieldsets', $ui);
                        ?>

                        <p class="submit">
                            <?php
                            wp_nonce_field('taxopress_addedit_taxonomy_nonce_action',
                                'taxopress_addedit_taxonomy_nonce_field');
                            if (!empty($_GET) && !empty($_GET['action']) && 'edit' === $_GET['action']) { ?>
                                <?php

                                /**
                                 * Filters the text value to use on the button when editing.
                                 *
                                 *
                                 * @param string $value Text to use for the button.
                                 */
                                ?>
                                <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                       value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_edit',
                                           esc_attr__('Save Taxonomy', 'simpletags'))); ?>"/>
                                <?php

                                /**
                                 * Filters the text value to use on the button when deleting.
                                 *
                                 *
                                 * @param string $value Text to use for the button.
                                 */
                                if (!$external_edit) {
                                    ?>
                                    <input type="submit" class="button-secondary taxopress-delete-bottom"
                                           name="cpt_delete" id="cpt_submit_delete"
                                           value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_delete',
                                               __('Delete Taxonomy', 'simpletags'))); ?>"/>
                                <?php }
                            } else { ?>
                                <?php

                                /**
                                 * Filters the text value to use on the button when adding.
                                 *
                                 *
                                 * @param string $value Text to use for the button.
                                 */
                                ?>
                                <input type="submit" class="button-primary taxopress-taxonomy-submit" name="cpt_submit"
                                       value="<?php echo esc_attr(apply_filters('taxopress_taxonomy_submit_add',
                                           esc_attr__('Add Taxonomy', 'simpletags'))); ?>"/>
                            <?php } ?>

                            <?php if (!empty($current)) { ?>
                                <input type="hidden" name="tax_original" id="tax_original"
                                       value="<?php echo esc_attr($current['name']); ?>"/>
                                <?php
                            }

                            // Used to check and see if we should prevent duplicate slugs.
                            ?>
                            <input type="hidden" name="cpt_tax_status" id="cpt_tax_status"
                                   value="<?php echo esc_attr($tab); ?>"/>
                        </p>
                    </div>
                </div>
        </form>
    </div><!-- End .wrap -->

    <div class="clear"></div>
    <?php
}

/**
 * Construct a dropdown of our taxonomies so users can select which to edit.
 *
 *
 * @param array $taxonomies Array of taxonomies that are registered. Optional.
 */
function taxopress_taxonomies_dropdown($taxonomies = [])
{

    $ui = new taxopress_admin_ui();

    if (!empty($taxonomies)) {
        $select            = [];
        $select['options'] = [];

        foreach ($taxonomies as $tax) {
            $text                = !empty($tax['label']) ? esc_html($tax['label']) : esc_html($tax['name']);
            $select['options'][] = [
                'attr' => $tax['name'],
                'text' => $text,
            ];
        }

        $current            = taxopress_get_current_taxonomy();
        $select['selected'] = $current;

        /**
         * Filters the taxonomy dropdown options before rendering.
         *
         * @param array $select Array of options for the dropdown.
         * @param array $taxonomies Array of original passed in post types.
         */
        $select = apply_filters('taxopress_taxonomies_dropdown_options', $select, $taxonomies);

        echo $ui->get_select_input([
            'namearray'  => 'taxopress_selected_taxonomy',
            'name'       => 'taxonomy',
            'selections' => $select,
            'wrap'       => false,
        ]);
    }
}

/**
 * Get the selected taxonomy from the $_POST global.
 *
 *
 * @param bool $taxonomy_deleted Whether or not a taxonomy was recently deleted. Optional. Default false.
 * @return bool|string False on no result, sanitized taxonomy if set.
 * @internal
 *
 */
function taxopress_get_current_taxonomy($taxonomy_deleted = false)
{

    $tax = false;

    if (!empty($_POST)) {
        if (!empty($_POST['taxopress_select_taxonomy_nonce_field'])) {
            check_admin_referer('taxopress_select_taxonomy_nonce_action', 'taxopress_select_taxonomy_nonce_field');
        }
        if (isset($_POST['taxopress_selected_taxonomy']['taxonomy'])) {
            $tax = sanitize_text_field($_POST['taxopress_selected_taxonomy']['taxonomy']);
        } elseif ($taxonomy_deleted) {
            $taxonomies = taxopress_get_taxonomy_data();
            $tax        = key($taxonomies);
        } elseif (isset($_POST['cpt_custom_tax']['name'])) {
            // Return the submitted value.
            if (!in_array($_POST['cpt_custom_tax']['name'], taxopress_reserved_taxonomies(), true)) {
                $tax = sanitize_text_field($_POST['cpt_custom_tax']['name']);
            } else {
                // Return the original value since user tried to submit a reserved term.
                $tax = sanitize_text_field($_POST['tax_original']);
            }
        }
    } elseif (!empty($_GET) && isset($_GET['taxopress_taxonomy'])) {
        $tax = sanitize_text_field($_GET['taxopress_taxonomy']);
    } else {
        $taxonomies = taxopress_get_taxonomy_data();
        if (!empty($taxonomies)) {
            // Will return the first array key.
            $tax = key($taxonomies);
        }
    }

    /**
     * Filters the current taxonomy to edit.
     *
     * @param string $tax Taxonomy slug.
     */
    return apply_filters('taxopress_current_taxonomy', $tax);
}

/**
 * Delete our custom taxonomy from the array of taxonomies.
 *
 *
 * @param array $data The $_POST values. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_delete_taxonomy($data = [])
{

    if (is_string($data) && taxonomy_exists($data)) {
        $data = [
            'cpt_custom_tax' => [
                'name' => $data,
            ],
        ];
    }

    // Check if they selected one to delete.
    if (empty($data['cpt_custom_tax']['name'])) {
        return taxopress_admin_notices('error', '', false,
            esc_html__('Please provide a taxonomy to delete', 'simpletags'));
    }

    /**
     * Fires before a taxonomy is deleted from our saved options.
     *
     *
     * @param array $data Array of taxonomy data we are deleting.
     */
    do_action('taxopress_before_delete_taxonomy', $data);

    $taxonomies = taxopress_get_taxonomy_data();

    if (array_key_exists(strtolower($data['cpt_custom_tax']['name']), $taxonomies)) {

        unset($taxonomies[$data['cpt_custom_tax']['name']]);

        /**
         * Filters whether or not 3rd party options were saved successfully within taxonomy deletion.
         *
         * @param bool $value Whether or not someone else saved successfully. Default false.
         * @param array $taxonomies Array of our updated taxonomies data.
         * @param array $data Array of submitted taxonomy to update.
         */
        if (false === ($success = apply_filters('taxopress_taxonomy_delete_tax', false, $taxonomies, $data))) {
            $success = update_option('taxopress_taxonomies', $taxonomies);
        }
    }
    delete_option("default_term_{$data['cpt_custom_tax']['name']}");

    /**
     * Fires after a taxonomy is deleted from our saved options.
     *
     *
     * @param array $data Array of taxonomy data that was deleted.
     */
    do_action('taxopress_after_delete_taxonomy', $data);

    // Used to help flush rewrite rules on init.
    set_transient('taxopress_flush_rewrite_rules', 'true', 5 * 60);

    if (isset($success)) {
        return 'delete_success';
    }

    return 'delete_fail';
}

/**
 * Add to or update our TAXOPRESS option with new data.
 *
 *
 * @param array $data Array of taxonomy data to update. Optional.
 * @return bool|string False on failure, string on success.
 * @internal
 *
 */
function taxopress_update_taxonomy($data = [])
{

    /**
     * Fires before a taxonomy is updated to our saved options.
     *
     *
     * @param array $data Array of taxonomy data we are updating.
     */
    do_action('taxopress_before_update_taxonomy', $data);

    // They need to provide a name.
    if (empty($data['cpt_custom_tax']['name'])) {
        return taxopress_admin_notices('error', '', false, esc_html__('Please provide a taxonomy name', 'simpletags'));
    }

    if (!isset($data['taxonomy_external_edit'])) {
        // Maybe a little harsh, but we shouldn't be saving THAT frequently.
        delete_option("default_term_{$data['cpt_custom_tax']['name']}");
    }

    if (empty($data['cpt_post_types'])) {
        add_filter('taxopress_custom_error_message', 'taxopress_empty_cpt_on_taxonomy');

        return 'error';
    }

    if (!isset($data['taxonomy_external_edit'])) {
        if (!empty($data['tax_original']) && $data['tax_original'] !== $data['cpt_custom_tax']['name']) {
            if (!empty($data['update_taxonomy'])) {
                add_filter('taxopress_convert_taxonomy_terms', '__return_true');
            }
        }
    }

    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $data[$key] = sanitize_text_field($value);
        } else {
            array_map('sanitize_text_field', $data[$key]);
        }
    }

    if (false !== strpos($data['cpt_custom_tax']['name'], '\'') ||
        false !== strpos($data['cpt_custom_tax']['name'], '\"') ||
        false !== strpos($data['cpt_custom_tax']['rewrite_slug'], '\'') ||
        false !== strpos($data['cpt_custom_tax']['rewrite_slug'], '\"')) {

        add_filter('taxopress_custom_error_message', 'taxopress_slug_has_quotes');

        return 'error';
    }

    $taxonomies          = taxopress_get_taxonomy_data();
    $external_taxonomies = taxopress_get_extername_taxonomy_data();


    if (!isset($data['taxonomy_external_edit'])) {
        /**
         * Check if we already have a post type of that name.
         *
         * @param bool $value Assume we have no conflict by default.
         * @param string $value Post type slug being saved.
         * @param array $post_types Array of existing post types from TAXOPRESS.
         */
        $slug_exists = apply_filters('taxopress_taxonomy_slug_exists', false, $data['cpt_custom_tax']['name'],
            $taxonomies);
        if (true === $slug_exists) {
            add_filter('taxopress_custom_error_message', 'taxopress_slug_matches_taxonomy');

            return 'error';
        }
    }

    foreach ($data['cpt_tax_labels'] as $key => $label) {
        if (empty($label)) {
            unset($data['cpt_tax_labels'][$key]);
        }
        $label                        = str_replace('"', '', htmlspecialchars_decode($label));
        $label                        = htmlspecialchars($label, ENT_QUOTES);
        $label                        = trim($label);
        $data['cpt_tax_labels'][$key] = stripslashes_deep($label);
    }

    $label = ucwords(str_replace('_', ' ', $data['cpt_custom_tax']['name']));
    if (!empty($data['cpt_custom_tax']['label'])) {
        $label = str_replace('"', '', htmlspecialchars_decode($data['cpt_custom_tax']['label']));
        $label = htmlspecialchars(stripslashes($label), ENT_QUOTES);
    }

    $name = trim($data['cpt_custom_tax']['name']);

    $singular_label = ucwords(str_replace('_', ' ', $data['cpt_custom_tax']['name']));
    if (!empty($data['cpt_custom_tax']['singular_label'])) {
        $singular_label = str_replace('"', '', htmlspecialchars_decode($data['cpt_custom_tax']['singular_label']));
        $singular_label = htmlspecialchars(stripslashes($singular_label));
    }
    $description           = stripslashes_deep($data['cpt_custom_tax']['description']);
    $query_var_slug        = trim($data['cpt_custom_tax']['query_var_slug']);
    $rewrite_slug          = trim($data['cpt_custom_tax']['rewrite_slug']);
    $rest_base             = trim($data['cpt_custom_tax']['rest_base']);
    $rest_controller_class = trim($data['cpt_custom_tax']['rest_controller_class']);
    $show_quickpanel_bulk  = !empty($data['cpt_custom_tax']['show_in_quick_edit']) ? taxopress_disp_boolean($data['cpt_custom_tax']['show_in_quick_edit']) : '';
    $default_term          = trim($data['cpt_custom_tax']['default_term']);

    $meta_box_cb = trim($data['cpt_custom_tax']['meta_box_cb']);
    // We may or may not need to force a boolean false keyword.
    $maybe_false = strtolower(trim($data['cpt_custom_tax']['meta_box_cb']));
    if ('false' === $maybe_false) {
        $meta_box_cb = $maybe_false;
    }


    if (!isset($data['taxonomy_external_edit'])) {

        $taxonomies[$data['cpt_custom_tax']['name']] = [
            'name'                  => $name,
            'label'                 => $label,
            'singular_label'        => $singular_label,
            'description'           => $description,
            'public'                => taxopress_disp_boolean($data['cpt_custom_tax']['public']),
            'publicly_queryable'    => taxopress_disp_boolean($data['cpt_custom_tax']['publicly_queryable']),
            'hierarchical'          => taxopress_disp_boolean($data['cpt_custom_tax']['hierarchical']),
            'show_ui'               => taxopress_disp_boolean($data['cpt_custom_tax']['show_ui']),
            'show_in_menu'          => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_menu']),
            'show_in_nav_menus'     => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_nav_menus']),
            'query_var'             => taxopress_disp_boolean($data['cpt_custom_tax']['query_var']),
            'query_var_slug'        => $query_var_slug,
            'rewrite'               => taxopress_disp_boolean($data['cpt_custom_tax']['rewrite']),
            'rewrite_slug'          => $rewrite_slug,
            'rewrite_withfront'     => $data['cpt_custom_tax']['rewrite_withfront'],
            'rewrite_hierarchical'  => $data['cpt_custom_tax']['rewrite_hierarchical'],
            'show_admin_column'     => taxopress_disp_boolean($data['cpt_custom_tax']['show_admin_column']),
            'show_in_rest'          => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_rest']),
            'show_in_quick_edit'    => $show_quickpanel_bulk,
            'rest_base'             => $rest_base,
            'rest_controller_class' => $rest_controller_class,
            'labels'                => $data['cpt_tax_labels'],
            'meta_box_cb'           => $meta_box_cb,
            'default_term'          => $default_term,
        ];

        $taxonomies[$data['cpt_custom_tax']['name']]['object_types'] = $data['cpt_post_types'];


        /**
         * Filters final data to be saved right before saving taxoomy data.
         *
         * @param array $taxonomies Array of final taxonomy data to save.
         * @param string $name Taxonomy slug for taxonomy being saved.
         */
        $taxonomies = apply_filters('taxopress_pre_save_taxonomy', $taxonomies, $name);

        /**
         * Filters whether or not 3rd party options were saved successfully within taxonomy add/update.
         *
         * @param bool $value Whether or not someone else saved successfully. Default false.
         * @param array $taxonomies Array of our updated taxonomies data.
         * @param array $data Array of submitted taxonomy to update.
         */
        if (false === ($success = apply_filters('taxopress_taxonomy_update_save', false, $taxonomies, $data))) {
            $success = update_option('taxopress_taxonomies', $taxonomies);
        }
    } else {

        $external_taxonomies[$data['cpt_custom_tax']['name']] = [
            'name'                  => $name,
            'label'                 => $label,
            'singular_label'        => $singular_label,
            'description'           => $description,
            'public'                => taxopress_disp_boolean($data['cpt_custom_tax']['public']),
            'publicly_queryable'    => taxopress_disp_boolean($data['cpt_custom_tax']['publicly_queryable']),
            'hierarchical'          => taxopress_disp_boolean($data['cpt_custom_tax']['hierarchical']),
            'show_ui'               => taxopress_disp_boolean($data['cpt_custom_tax']['show_ui']),
            'show_in_menu'          => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_menu']),
            'show_in_nav_menus'     => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_nav_menus']),
            'query_var'             => taxopress_disp_boolean($data['cpt_custom_tax']['query_var']),
            'query_var_slug'        => $query_var_slug,
            'rewrite'               => taxopress_disp_boolean($data['cpt_custom_tax']['rewrite']),
            'rewrite_slug'          => $rewrite_slug,
            'rewrite_withfront'     => $data['cpt_custom_tax']['rewrite_withfront'],
            'rewrite_hierarchical'  => $data['cpt_custom_tax']['rewrite_hierarchical'],
            'show_admin_column'     => taxopress_disp_boolean($data['cpt_custom_tax']['show_admin_column']),
            'show_in_rest'          => taxopress_disp_boolean($data['cpt_custom_tax']['show_in_rest']),
            'show_in_quick_edit'    => $show_quickpanel_bulk,
            'rest_base'             => $rest_base,
            'rest_controller_class' => $rest_controller_class,
            'labels'                => $data['cpt_tax_labels'],
            'meta_box_cb'           => $meta_box_cb,
            'default_term'          => $default_term,
        ];

        $external_taxonomies[$data['cpt_custom_tax']['name']]['object_types'] = $data['cpt_post_types'];
        $success                                                              = update_option('taxopress_external_taxonomies',
            $external_taxonomies);
    }

    /**
     * Fires after a taxonomy is updated to our saved options.
     *
     *
     * @param array $data Array of taxonomy data that was updated.
     */
    do_action('taxopress_after_update_taxonomy', $data);

    // Used to help flush rewrite rules on init.
    set_transient('taxopress_flush_rewrite_rules', 'true', 5 * 60);

    if (isset($success) && 'new' === $data['cpt_tax_status']) {
        return 'add_success';
    }

    return 'update_success';
}

/**
 * Return an array of names that users should not or can not use for taxonomy names.
 *
 * @return array $value Array of names that are recommended against.
 */
function taxopress_reserved_taxonomies()
{

    $reserved = [
        'action',
        'attachment',
        'attachment_id',
        'author',
        'author_name',
        'calendar',
        'cat',
        'category',
        'category__and',
        'category__in',
        'category__not_in',
        'category_name',
        'comments_per_page',
        'comments_popup',
        'customize_messenger_channel',
        'customized',
        'cpage',
        'day',
        'debug',
        'error',
        'exact',
        'feed',
        'fields',
        'hour',
        'include',
        'link_category',
        'm',
        'minute',
        'monthnum',
        'more',
        'name',
        'nav_menu',
        'nonce',
        'nopaging',
        'offset',
        'order',
        'orderby',
        'p',
        'page',
        'page_id',
        'paged',
        'pagename',
        'pb',
        'perm',
        'post',
        'post__in',
        'post__not_in',
        'post_format',
        'post_mime_type',
        'post_status',
        'post_tag',
        'post_type',
        'posts',
        'posts_per_archive_page',
        'posts_per_page',
        'preview',
        'robots',
        's',
        'search',
        'second',
        'sentence',
        'showposts',
        'static',
        'subpost',
        'subpost_id',
        'tag',
        'tag__and',
        'tag__in',
        'tag__not_in',
        'tag_id',
        'tag_slug__and',
        'tag_slug__in',
        'taxonomy',
        'tb',
        'term',
        'theme',
        'type',
        'types',
        'w',
        'withcomments',
        'withoutcomments',
        'year',
        'output',
    ];

    /**
     * Filters the list of reserved post types to check against.
     * 3rd party plugin authors could use this to prevent duplicate post types.
     *
     *
     * @param array $value Array of post type slugs to forbid.
     */
    $custom_reserved = apply_filters('taxopress_reserved_taxonomies', []);

    if (is_string($custom_reserved) && !empty($custom_reserved)) {
        $reserved[] = $custom_reserved;
    } elseif (is_array($custom_reserved) && !empty($custom_reserved)) {
        foreach ($custom_reserved as $slug) {
            $reserved[] = $slug;
        }
    }

    return $reserved;
}

/**
 * Convert taxonomies.
 *
 * @param string $original_slug Original taxonomy slug. Optional. Default empty string.
 * @param string $new_slug New taxonomy slug. Optional. Default empty string.
 * @internal
 *
 */
function taxopress_convert_taxonomy_terms($original_slug = '', $new_slug = '')
{
    global $wpdb;

    $args = [
        'taxonomy'   => $original_slug,
        'hide_empty' => false,
        'fields'     => 'ids',
    ];

    $term_ids = get_terms($args);

    if (is_int($term_ids)) {
        $term_ids = (array)$term_ids;
    }

    if (is_array($term_ids) && !empty($term_ids)) {
        $term_ids = implode(',', $term_ids);

        $query = "UPDATE `{$wpdb->term_taxonomy}` SET `taxonomy` = %s WHERE `taxonomy` = %s AND `term_id` IN ( {$term_ids} )";

        $wpdb->query(
            $wpdb->prepare($query, $new_slug, $original_slug)
        );
    }
    taxopress_delete_taxonomy($original_slug);
}

/**
 * Checks if we are trying to register an already registered taxonomy slug.
 *
 * @param bool $slug_exists Whether or not the post type slug exists. Optional. Default false.
 * @param string $taxonomy_slug The post type slug being saved. Optional. Default empty string.
 * @param array $taxonomies Array of TAXOPRESS-registered post types. Optional.
 *
 * @return bool
 */
function taxopress_check_existing_taxonomy_slugs($slug_exists = false, $taxonomy_slug = '', $taxonomies = [])
{

    // If true, then we'll already have a conflict, let's not re-process.
    if (true === $slug_exists) {
        return $slug_exists;
    }

    // Check if TAXOPRESS has already registered this slug.
    if (array_key_exists(strtolower($taxonomy_slug), $taxonomies)) {
        return true;
    }

    // Check if we're registering a reserved post type slug.
    if (in_array($taxonomy_slug, taxopress_reserved_taxonomies())) {
        return true;
    }

    // Check if other plugins have registered this same slug.
    $public                = get_taxonomies(['_builtin' => false, 'public' => true]);
    $private               = get_taxonomies(['_builtin' => false, 'public' => false]);
    $registered_taxonomies = array_merge($public, $private);
    if (in_array($taxonomy_slug, $registered_taxonomies)) {
        return true;
    }

    // If we're this far, it's false.
    return $slug_exists;
}

add_filter('taxopress_taxonomy_slug_exists', 'taxopress_check_existing_taxonomy_slugs', 10, 3);

/**
 * Handle the save and deletion of taxonomy data.
 */
function taxopress_process_taxonomy()
{

    if (wp_doing_ajax()) {
        return;
    }

    if (!is_admin()) {
        return;
    }

    if (!empty($_GET) && isset($_GET['page']) && 'st_taxonomies' !== $_GET['page']) {
        return;
    }

    if (!empty($_POST) && (isset($_POST['cpt_submit']) || isset($_POST['cpt_delete']))) {
        $result = '';
        if (isset($_POST['cpt_submit'])) {
            check_admin_referer('taxopress_addedit_taxonomy_nonce_action', 'taxopress_addedit_taxonomy_nonce_field');
            $result = taxopress_update_taxonomy($_POST);
        } elseif (isset($_POST['cpt_delete'])) {
            check_admin_referer('taxopress_addedit_taxonomy_nonce_action', 'taxopress_addedit_taxonomy_nonce_field');
            $result = taxopress_delete_taxonomy($_POST);
            add_filter('taxopress_taxonomy_deleted', '__return_true');
        }

        if ($result && is_callable("taxopress_{$result}_admin_notice")) {
            add_action('admin_notices', "taxopress_{$result}_admin_notice");
        }

        if (isset($_POST['cpt_delete']) && empty(taxopress_get_taxonomy_slugs())) {
            wp_safe_redirect(
                add_query_arg(
                    ['page' => 'st_taxonomies'],
                    taxopress_admin_url('admin.php?page=st_taxonomies')
                )
            );
        }
    } elseif (isset($_POST['action']) || isset($_POST['action2'])) {

        if ((isset($_POST['action']) && $_POST['action'] == 'st-bulk-deactivate-term') || (isset($_POST['action2']) && $_POST['action2'] == 'st-bulk-deactivate-term')) {
            //deactivate term
            $term_objects = isset($_POST['taxonomy-bulk-checked']) ? $_POST['taxonomy-bulk-checked'] : [];
            if (count($term_objects) > 0) {
                foreach ($term_objects as $term_object) {
                    taxopress_deactivate_taxonomy($term_object);
                }
                add_action('admin_notices', "taxopress_deactivated_admin_notice");
            } else {
                add_action('admin_notices', "taxopress_none_admin_notice");
            }
        } elseif ((isset($_POST['action']) && $_POST['action'] == 'st-bulk-activate-term') || (isset($_POST['action2']) && $_POST['action2'] == 'st-bulk-activate-term')) {
            //activate term
            $term_objects = isset($_POST['taxonomy-bulk-checked']) ? $_POST['taxonomy-bulk-checked'] : [];
            if (count($term_objects) > 0) {
                $term_objects = $_POST['taxonomy-bulk-checked'];
                foreach ($term_objects as $term_object) {
                    taxopress_activate_taxonomy($term_object);
                }
                add_action('admin_notices', "taxopress_activated_admin_notice");
            } else {
                add_action('admin_notices', "taxopress_none_admin_notice");
            }
        } else {
            add_action('admin_notices', "taxopress_noaction_admin_notice");
        }
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-deactivate-taxonomy') {
        $nonce = esc_attr($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'taxonomy-action-request-nonce')) {
            taxopress_deactivate_taxonomy($_REQUEST['taxonomy']);
            add_action('admin_notices', "taxopress_deactivated_admin_notice");
        }
        add_filter('removable_query_args', 'taxopress_filter_removable_query_args');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-reactivate-taxonomy') {
        $nonce = esc_attr($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'taxonomy-action-request-nonce')) {
            taxopress_activate_taxonomy($_REQUEST['taxonomy']);
            add_action('admin_notices', "taxopress_activated_admin_notice");
        }
        add_filter('removable_query_args', 'taxopress_filter_removable_query_args');
    } elseif (isset($_REQUEST['action']) && $_REQUEST['action'] === 'taxopress-delete-taxonomy') {
        $nonce = esc_attr($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'taxonomy-action-request-nonce')) {
            taxopress_action_delete_taxonomy($_REQUEST['taxonomy']);
        }
        add_filter('removable_query_args', 'taxopress_filter_removable_query_args');
    } elseif (isset($_REQUEST['action2']) && $_REQUEST['action2'] === 'taxopress-reactivate-taxonomy') {
        $nonce = esc_attr($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'taxonomy-action-request-nonce')) {
            taxopress_activate_taxonomy($_REQUEST['taxonomy']);
            add_action('admin_notices', "taxopress_activated_admin_notice");
        }
        add_filter('removable_query_args', 'taxopress_filter_removable_query_args_2');
    } elseif (isset($_REQUEST['action2']) && $_REQUEST['action2'] === 'taxopress-deactivate-taxonomy') {
        $nonce = esc_attr($_REQUEST['_wpnonce']);
        if (wp_verify_nonce($nonce, 'taxonomy-action-request-nonce')) {
            taxopress_deactivate_taxonomy($_REQUEST['taxonomy']);
            add_action('admin_notices', "taxopress_deactivated_admin_notice");
        }
        add_filter('removable_query_args', 'taxopress_filter_removable_query_args_2');
    }
}

add_action('init', 'taxopress_process_taxonomy', 8);

/**
 * Handle the conversion of taxonomy terms.
 *
 * This function came to be because we needed to convert AFTER registration.
 */
function taxopress_do_convert_taxonomy_terms()
{

    /**
     * Whether or not to convert taxonomy terms.
     *
     * @param bool $value Whether or not to convert.
     */
    if (apply_filters('taxopress_convert_taxonomy_terms', false)) {
        check_admin_referer('taxopress_addedit_taxonomy_nonce_action', 'taxopress_addedit_taxonomy_nonce_field');

        taxopress_convert_taxonomy_terms(sanitize_text_field($_POST['tax_original']),
            sanitize_text_field($_POST['cpt_custom_tax']['name']));
    }
}

add_action('init', 'taxopress_do_convert_taxonomy_terms');

/**
 * Handles slug_exist checks for cases of editing an existing taxonomy.
 *
 * @param bool $slug_exists Current status for exist checks.
 * @param string $taxonomy_slug Taxonomy slug being processed.
 * @param array $taxonomies TAXOPRESS taxonomies.
 * @return bool
 */
function taxopress_updated_taxonomy_slug_exists($slug_exists, $taxonomy_slug = '', $taxonomies = [])
{
    if (
        (!empty($_POST['cpt_tax_status']) && 'edit' === $_POST['cpt_tax_status']) &&
        !in_array($taxonomy_slug, taxopress_reserved_taxonomies(), true) &&
        (!empty($_POST['tax_original']) && $taxonomy_slug === $_POST['tax_original'])
    ) {
        $slug_exists = false;
    }

    return $slug_exists;
}

add_filter('taxopress_taxonomy_slug_exists', 'taxopress_updated_taxonomy_slug_exists', 11, 3);


/**
 * Return boolean status depending on passed in value.
 *
 * @param mixed $bool_text text to compare to typical boolean values.
 * @return bool Which bool value the passed in value was.
 */
function get_taxopress_disp_boolean($bool_text)
{
    $bool_text = (string)$bool_text;
    if (empty($bool_text) || '0' === $bool_text || 'false' === $bool_text) {
        return false;
    }

    return true;
}

/**
 * Return string versions of boolean values.
 *
 * @param string $bool_text String boolean value.
 * @return string standardized boolean text.
 */
function taxopress_disp_boolean($bool_text)
{
    $bool_text = (string)$bool_text;
    if (empty($bool_text) || '0' === $bool_text || 'false' === $bool_text) {
        return 'false';
    }

    return 'true';
}

/**
 * Conditionally flushes rewrite rules if we have reason to.
 */
function taxopress_flush_rewrite_rules()
{

    if (wp_doing_ajax()) {
        return;
    }

    /*
	 * Wise men say that you should not do flush_rewrite_rules on init or admin_init. Due to the nature of our plugin
	 * and how new post types or taxonomies can suddenly be introduced, we need to...potentially. For this,
	 * we rely on a short lived transient. Only 5 minutes life span. If it exists, we do a soft flush before
	 * deleting the transient to prevent subsequent flushes. The only times the transient gets created, is if
	 * post types or taxonomies are created, updated, deleted, or imported. Any other time and this condition
	 * should not be met.
	 */
    if ('true' === ($flush_it = get_transient('taxopress_flush_rewrite_rules'))) {
        flush_rewrite_rules(false);
        // So we only run this once.
        delete_transient('taxopress_flush_rewrite_rules');
    }
}

add_action('admin_init', 'taxopress_flush_rewrite_rules');

/**
 * Return the current action being done within TAXOPRESS context.
 *
 * @return string Current action being done by TAXOPRESS
 */
function taxopress_get_current_action()
{
    $current_action = '';
    if (!empty($_GET) && isset($_GET['action'])) {
        $current_action .= esc_textarea($_GET['action']);
    }

    return $current_action;
}

/**
 * Return an array of all taxonomy slugs from Custom Post Type UI.
 *
 * @return array TAXOPRESS taxonomy slugs.
 */
function taxopress_get_taxonomy_slugs()
{
    $taxonomies = get_option('taxopress_taxonomies');
    if (!empty($taxonomies)) {
        return array_keys($taxonomies);
    }

    return [];
}

/**
 * Return the appropriate admin URL depending on our context.
 *
 * @param string $path URL path.
 * @return string
 */
function taxopress_admin_url($path)
{
    if (is_multisite() && is_network_admin()) {
        return network_admin_url($path);
    }

    return admin_url($path);
}

/**
 * Construct action tag for `<form>` tag.
 *
 * @param object|string $ui TAXOPRESS Admin UI instance. Optional. Default empty string.
 * @return string
 */
function taxopress_get_post_form_action($ui = '')
{
    /**
     * Filters the string to be used in an `action=""` attribute.
     */
    return apply_filters('taxopress_post_form_action', '', $ui);
}

/**
 * Display action tag for `<form>` tag.
 *
 * @param object $ui TAXOPRESS Admin UI instance.
 */
function taxopress_post_form_action($ui)
{
    echo esc_attr(taxopress_get_post_form_action($ui));
}

/**
 * Fetch our TAXOPRESS taxonomies option.
 *
 * @return mixed
 */
function taxopress_get_taxonomy_data()
{
    return apply_filters('taxopress_get_taxonomy_data', get_option('taxopress_taxonomies', []), get_current_blog_id());
}

/**
 * Fetch our TAXOPRESS taxonomies option.
 *
 * @return mixed
 */
function taxopress_get_extername_taxonomy_data()
{
    return apply_filters('taxopress_get_extername_taxonomy_data', get_option('taxopress_external_taxonomies', []),
        get_current_blog_id());
}


/**
 * Checks if a taxonomy is already registered.
 *
 * @param string $slug Taxonomy slug to check. Optional. Default empty string.
 * @param array|string $data Taxonomy data being utilized. Optional.
 *
 * @return mixed
 */
function taxopress_get_taxonomy_exists($slug = '', $data = [])
{

    /**
     * Filters the boolean value for if a taxonomy exists for 3rd parties.
     *
     * @param string $slug Taxonomy slug to check.
     * @param array|string $data Taxonomy data being utilized.
     */
    return apply_filters('taxopress_get_taxonomy_exists', taxonomy_exists($slug), $data);
}

/**
 * Secondary admin notices function for use with admin_notices hook.
 *
 * Constructs admin notice HTML.
 *
 * @param string $message Message to use in admin notice. Optional. Default empty string.
 * @param bool $success Whether or not a success. Optional. Default true.
 * @return mixed
 */
function taxopress_admin_notices_helper($message = '', $success = true)
{

    $class   = [];
    $class[] = $success ? 'updated' : 'error';
    $class[] = 'notice is-dismissible';

    $messagewrapstart = '<div id="message" class="' . implode(' ', $class) . '"><p>';

    $messagewrapend = '</p></div>';

    $action = '';

    /**
     * Filters the custom admin notice for TAXOPRESS.
     *
     *
     * @param string $value Complete HTML output for notice.
     * @param string $action Action whose message is being generated.
     * @param string $message The message to be displayed.
     * @param string $messagewrapstart Beginning wrap HTML.
     * @param string $messagewrapend Ending wrap HTML.
     */
    return apply_filters('taxopress_admin_notice', $messagewrapstart . $message . $messagewrapend, $action, $message,
        $messagewrapstart, $messagewrapend);
}

/**
 * Grab post type or taxonomy slug from $_POST global, if available.
 *
 * @return string
 * @internal
 *
 */
function taxopress_get_object_from_post_global()
{
    if (isset($_POST['cpt_custom_post_type']['name'])) {
        return sanitize_text_field($_POST['cpt_custom_post_type']['name']);
    }

    if (isset($_POST['cpt_custom_tax']['name'])) {
        return sanitize_text_field($_POST['cpt_custom_tax']['name']);
    }

    return esc_html__('Object', 'simpletags');
}

/**
 * Successful add callback.
 */
function taxopress_add_success_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has been successfully added', 'simpletags'),
            taxopress_get_object_from_post_global()
        )
    );
}

/**
 * Fail to add callback.
 */
function taxopress_add_fail_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has failed to be added', 'simpletags'),
            taxopress_get_object_from_post_global()
        ),
        false
    );
}

/**
 * Successful update callback.
 */
function taxopress_update_success_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has been successfully updated', 'simpletags'),
            taxopress_get_object_from_post_global()
        )
    );
}

/**
 * Fail to update callback.
 */
function taxopress_update_fail_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has failed to be updated', 'simpletags'),
            taxopress_get_object_from_post_global()
        ),
        false
    );
}

/**
 * Successful delete callback.
 */
function taxopress_delete_success_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has been successfully deleted', 'simpletags'),
            taxopress_get_object_from_post_global()
        )
    );
}

/**
 * Fail to delete callback.
 */
function taxopress_delete_fail_admin_notice()
{
    echo taxopress_admin_notices_helper(
        sprintf(
            esc_html__('%s has failed to be deleted', 'simpletags'),
            taxopress_get_object_from_post_global()
        ),
        false
    );
}


function taxopress_nonce_fail_admin_notice()
{
    echo taxopress_admin_notices_helper(
        esc_html__('Nonce failed verification', 'simpletags'),
        false
    );
}

/**
 * Returns error message for if trying to register existing taxonomy.
 *
 * @return string
 */
function taxopress_slug_matches_taxonomy()
{
    return sprintf(
        esc_html__('Please choose a different taxonomy name. %s is already registered.', 'simpletags'),
        taxopress_get_object_from_post_global()
    );
}


/**
 * Returns error message for if not providing a post type to associate taxonomy to.
 *
 * @return string
 */
function taxopress_empty_cpt_on_taxonomy()
{
    return esc_html__('Please provide a post type to attach to.', 'simpletags');
}

/**
 * Returns error message for if trying to register post type with matching page slug.
 *
 * @return string
 */
function taxopress_slug_matches_page()
{
    $slug         = taxopress_get_object_from_post_global();
    $matched_slug = get_page_by_path(
        taxopress_get_object_from_post_global()
    );
    if ($matched_slug instanceof WP_Post) {
        $slug = sprintf(
            '<a href="%s">%s</a>',
            get_edit_post_link($matched_slug->ID),
            taxopress_get_object_from_post_global()
        );
    }

    return sprintf(
        esc_html__('Please choose a different post type name. %s matches an existing page slug, which can cause conflicts.',
            'simpletags'),
        $slug
    );
}

/**
 * Returns error message for if trying to use quotes in slugs or rewrite slugs.
 *
 * @return string
 */
function taxopress_slug_has_quotes()
{
    return sprintf(
        esc_html__('Please do not use quotes in post type/taxonomy names or rewrite slugs', 'simpletags'),
        taxopress_get_object_from_post_global()
    );
}

/**
 * Error admin notice.
 */
function taxopress_error_admin_notice()
{
    echo taxopress_admin_notices_helper(
        apply_filters('taxopress_custom_error_message', ''),
        false
    );
}

/**
 * Returns saved values for single taxonomy from TAXOPRESS settings.
 *
 * @param string $taxonomy Taxonomy to retrieve TAXOPRESS object for.
 * @return string
 */
function taxopress_get_taxopress_taxonomy_object($taxonomy = '')
{
    $taxonomies = get_option('taxopress_taxonomies');

    if (array_key_exists($taxonomy, $taxonomies)) {
        return $taxonomies[$taxonomy];
    }

    return '';
}


/**
 * Register our users' custom taxonomies.
 *
 * @internal
 */
function taxopress_create_custom_taxonomies()
{
    $taxes = get_option('taxopress_taxonomies');

    if (empty($taxes)) {
        return;
    }

    /**
     * Fires before the start of the taxonomy registrations.
     *
     * @param array $taxes Array of taxonomies to register.
     */
    do_action('taxopress_pre_register_taxonomies', $taxes);

    if (is_array($taxes)) {
        foreach ($taxes as $tax) {
            /**
             * Filters whether or not to skip registration of the current iterated taxonomy.
             *
             * Dynamic part of the filter name is the chosen taxonomy slug.
             *
             * @param bool $value Whether or not to skip the taxonomy.
             * @param array $tax Current taxonomy being registered.
             */
            if ((bool)apply_filters("taxopress_disable_{$tax['name']}_tax", false, $tax)) {
                continue;
            }

            /**
             * Filters whether or not to skip registration of the current iterated taxonomy.
             *
             * @param bool $value Whether or not to skip the taxonomy.
             * @param array $tax Current taxonomy being registered.
             */
            if ((bool)apply_filters('taxopress_disable_tax', false, $tax)) {
                continue;
            }

            taxopress_register_single_taxonomy($tax);
        }
    }

    /**
     * Fires after the completion of the taxonomy registrations.
     *
     * @param array $taxes Array of taxonomies registered.
     */
    do_action('taxopress_post_register_taxonomies', $taxes);
}

add_action('init', 'taxopress_create_custom_taxonomies', 9);  // Leave on standard init for legacy purposes.

/**
 * Helper function to register the actual taxonomy.
 *
 * @param array $taxonomy Taxonomy array to register. Optional.
 * @return null Result of register_taxonomy.
 * @internal
 *
 */
function taxopress_register_single_taxonomy($taxonomy = [])
{
    $labels = [
        'name'          => $taxonomy['label'],
        'singular_name' => $taxonomy['singular_label'],
    ];

    $description = '';
    if (!empty($taxonomy['description'])) {
        $description = $taxonomy['description'];
    }

    $preserved        = taxopress_get_preserved_keys('taxonomies');
    $preserved_labels = taxopress_get_preserved_labels();
    foreach ($taxonomy['labels'] as $key => $label) {
        if (!empty($label)) {
            $labels[$key] = $label;
        } elseif (empty($label) && in_array($key, $preserved, true)) {
            $singular_or_plural = (in_array($key,
                array_keys($preserved_labels['taxonomies']['plural']))) ? 'plural' : 'singular';
            $label_plurality    = ('plural' === $singular_or_plural) ? $taxonomy['label'] : $taxonomy['singular_label'];
            $labels[$key]       = sprintf($preserved_labels['taxonomies'][$singular_or_plural][$key], $label_plurality);
        }
    }

    $rewrite = get_taxopress_disp_boolean($taxonomy['rewrite']);
    if (false !== get_taxopress_disp_boolean($taxonomy['rewrite'])) {
        $rewrite               = [];
        $rewrite['slug']       = !empty($taxonomy['rewrite_slug']) ? $taxonomy['rewrite_slug'] : $taxonomy['name'];
        $rewrite['with_front'] = true;
        if (isset($taxonomy['rewrite_withfront'])) {
            $rewrite['with_front'] = ('false' === taxopress_disp_boolean($taxonomy['rewrite_withfront'])) ? false : true;
        }
        $rewrite['hierarchical'] = false;
        if (isset($taxonomy['rewrite_hierarchical'])) {
            $rewrite['hierarchical'] = ('true' === taxopress_disp_boolean($taxonomy['rewrite_hierarchical'])) ? true : false;
        }
    }

    if (in_array($taxonomy['query_var'], ['true', 'false', '0', '1'], true)) {
        $taxonomy['query_var'] = get_taxopress_disp_boolean($taxonomy['query_var']);
    }
    if (true === $taxonomy['query_var'] && !empty($taxonomy['query_var_slug'])) {
        $taxonomy['query_var'] = $taxonomy['query_var_slug'];
    }

    $public             = (!empty($taxonomy['public']) && false === get_taxopress_disp_boolean($taxonomy['public'])) ? false : true;
    $publicly_queryable = (!empty($taxonomy['publicly_queryable']) && false === get_taxopress_disp_boolean($taxonomy['publicly_queryable'])) ? false : true;
    if (empty($taxonomy['publicly_queryable'])) {
        $publicly_queryable = $public;
    }

    $show_admin_column = (!empty($taxonomy['show_admin_column']) && false !== get_taxopress_disp_boolean($taxonomy['show_admin_column'])) ? true : false;

    $show_in_menu = (!empty($taxonomy['show_in_menu']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_menu'])) ? true : false;

    if (empty($taxonomy['show_in_menu'])) {
        $show_in_menu = get_taxopress_disp_boolean($taxonomy['show_ui']);
    }

    $show_in_nav_menus = (!empty($taxonomy['show_in_nav_menus']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_nav_menus'])) ? true : false;
    if (empty($taxonomy['show_in_nav_menus'])) {
        $show_in_nav_menus = $public;
    }

    $show_in_rest = (!empty($taxonomy['show_in_rest']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_rest'])) ? true : false;

    $show_in_quick_edit = (!empty($taxonomy['show_in_quick_edit']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_quick_edit'])) ? true : false;

    $rest_base = null;
    if (!empty($taxonomy['rest_base'])) {
        $rest_base = $taxonomy['rest_base'];
    }

    $rest_controller_class = null;
    if (!empty($post_type['rest_controller_class'])) {
        $rest_controller_class = $post_type['rest_controller_class'];
    }

    $meta_box_cb = null;
    if (!empty($taxonomy['meta_box_cb'])) {
        $meta_box_cb = (false !== get_taxopress_disp_boolean($taxonomy['meta_box_cb'])) ? $taxonomy['meta_box_cb'] : false;
    }
    $default_term = null;
    if (!empty($taxonomy['default_term'])) {
        $term_parts = explode(',', $taxonomy['default_term']);
        if (!empty($term_parts[0])) {
            $default_term['name'] = trim($term_parts[0]);
        }
        if (!empty($term_parts[1])) {
            $default_term['slug'] = trim($term_parts[1]);
        }
        if (!empty($term_parts[2])) {
            $default_term['description'] = trim($term_parts[2]);
        }
    }

    $args = [
        'labels'                => $labels,
        'label'                 => $taxonomy['label'],
        'description'           => $description,
        'public'                => $public,
        'publicly_queryable'    => $publicly_queryable,
        'hierarchical'          => get_taxopress_disp_boolean($taxonomy['hierarchical']),
        'show_ui'               => get_taxopress_disp_boolean($taxonomy['show_ui']),
        'show_in_menu'          => $show_in_menu,
        'show_in_nav_menus'     => $show_in_nav_menus,
        'query_var'             => $taxonomy['query_var'],
        'rewrite'               => $rewrite,
        'show_admin_column'     => $show_admin_column,
        'show_in_rest'          => $show_in_rest,
        'rest_base'             => $rest_base,
        'rest_controller_class' => $rest_controller_class,
        'show_in_quick_edit'    => $show_in_quick_edit,
        'meta_box_cb'           => $meta_box_cb,
        'default_term'          => $default_term,
    ];

    $object_type = !empty($taxonomy['object_types']) ? $taxonomy['object_types'] : '';

    /**
     * Filters the arguments used for a taxonomy right before registering.
     *
     * @param array $args Array of arguments to use for registering taxonomy.
     * @param string $value Taxonomy slug to be registered.
     * @param array $taxonomy Original passed in values for taxonomy.
     * @param array $object_type Array of chosen post types for the taxonomy.
     */
    $args = apply_filters('taxopress_pre_register_taxonomy', $args, $taxonomy['name'], $taxonomy, $object_type);

    return register_taxonomy($taxonomy['name'], $object_type, $args);
}


/**
 * Return a notice based on conditions.
 *
 * @param string $action The type of action that occurred. Optional. Default empty string.
 * @param string $object_type Whether it's from a post type or taxonomy. Optional. Default empty string.
 * @param bool $success Whether the action succeeded or not. Optional. Default true.
 * @param string $custom Custom message if necessary. Optional. Default empty string.
 * @return bool|string false on no message, else HTML div with our notice message.
 */
function taxopress_admin_notices($action = '', $object_type = '', $success = true, $custom = '')
{
    $class       = [];
    $class[]     = $success ? 'updated' : 'error';
    $class[]     = 'notice is-dismissible';
    $object_type = esc_attr($object_type);

    $messagewrapstart = '<div id="message" class="' . implode(' ', $class) . '"><p>';
    $message          = '';

    $messagewrapend = '</p></div>';

    if ('add' === $action) {
        if ($success) {
            $message .= sprintf(__('%s has been successfully added', 'simpletags'), $object_type);
        } else {
            $message .= sprintf(__('%s has failed to be added', 'simpletags'), $object_type);
        }
    } elseif ('update' === $action) {
        if ($success) {
            $message .= sprintf(__('%s has been successfully updated', 'simpletags'), $object_type);
        } else {
            $message .= sprintf(__('%s has failed to be updated', 'simpletags'), $object_type);
        }
    } elseif ('delete' === $action) {
        if ($success) {
            $message .= sprintf(__('%s has been successfully deleted', 'simpletags'), $object_type);
        } else {
            $message .= sprintf(__('%s has failed to be deleted', 'simpletags'), $object_type);
        }
    } elseif ('import' === $action) {
        if ($success) {
            $message .= sprintf(__('%s has been successfully imported', 'simpletags'), $object_type);
        } else {
            $message .= sprintf(__('%s has failed to be imported', 'simpletags'), $object_type);
        }
    } elseif ('error' === $action) {
        if (!empty($custom)) {
            $message = $custom;
        }
    }

    if ($message) {

        /**
         * Filters the custom admin notice for TAXOPRESS.
         *
         * @param string $value Complete HTML output for notice.
         * @param string $action Action whose message is being generated.
         * @param string $message The message to be displayed.
         * @param string $messagewrapstart Beginning wrap HTML.
         * @param string $messagewrapend Ending wrap HTML.
         */
        return apply_filters('taxopress_admin_notice', $messagewrapstart . $message . $messagewrapend, $action,
            $message, $messagewrapstart, $messagewrapend);
    }

    return false;
}

/**
 * Return array of keys needing preserved.
 *
 * @param string $type Type to return. Either 'post_types' or 'taxonomies'. Optional. Default empty string.
 * @return array Array of keys needing preservered for the requested type.
 */
function taxopress_get_preserved_keys($type = '')
{
    $preserved_labels = [
        'post_types' => [
            'add_new_item',
            'edit_item',
            'new_item',
            'view_item',
            'view_items',
            'all_items',
            'search_items',
            'not_found',
            'not_found_in_trash',
        ],
        'taxonomies' => [
            'search_items',
            'popular_items',
            'all_items',
            'parent_item',
            'parent_item_colon',
            'edit_item',
            'update_item',
            'add_new_item',
            'new_item_name',
            'separate_items_with_commas',
            'add_or_remove_items',
            'choose_from_most_used',
        ],
    ];

    return !empty($type) ? $preserved_labels[$type] : [];
}

/**
 * Return label for the requested type and label key.
 *
 * @param string $type Type to return. Either 'post_types' or 'taxonomies'. Optional. Default empty string.
 * @param string $key Requested label key. Optional. Default empty string.
 * @param string $plural Plural verbiage for the requested label and type. Optional. Default empty string.
 * @param string $singular Singular verbiage for the requested label and type. Optional. Default empty string.
 * @return string Internationalized default label.
 * @deprecated
 *
 */
function taxopress_get_preserved_label($type = '', $key = '', $plural = '', $singular = '')
{
    $preserved_labels = [
        'post_types' => [
            'add_new_item'       => sprintf(__('Add new %s', 'simpletags'), $singular),
            'edit_item'          => sprintf(__('Edit %s', 'simpletags'), $singular),
            'new_item'           => sprintf(__('New %s', 'simpletags'), $singular),
            'view_item'          => sprintf(__('View %s', 'simpletags'), $singular),
            'view_items'         => sprintf(__('View %s', 'simpletags'), $plural),
            'all_items'          => sprintf(__('All %s', 'simpletags'), $plural),
            'search_items'       => sprintf(__('Search %s', 'simpletags'), $plural),
            'not_found'          => sprintf(__('No %s found.', 'simpletags'), $plural),
            'not_found_in_trash' => sprintf(__('No %s found in trash.', 'simpletags'), $plural),
        ],
        'taxonomies' => [
            'search_items'               => sprintf(__('Search %s', 'simpletags'), $plural),
            'popular_items'              => sprintf(__('Popular %s', 'simpletags'), $plural),
            'all_items'                  => sprintf(__('All %s', 'simpletags'), $plural),
            'parent_item'                => sprintf(__('Parent %s', 'simpletags'), $singular),
            'parent_item_colon'          => sprintf(__('Parent %s:', 'simpletags'), $singular),
            'edit_item'                  => sprintf(__('Edit %s', 'simpletags'), $singular),
            'update_item'                => sprintf(__('Update %s', 'simpletags'), $singular),
            'add_new_item'               => sprintf(__('Add new %s', 'simpletags'), $singular),
            'new_item_name'              => sprintf(__('New %s name', 'simpletags'), $singular),
            'separate_items_with_commas' => sprintf(__('Separate %s with commas', 'simpletags'), $plural),
            'add_or_remove_items'        => sprintf(__('Add or remove %s', 'simpletags'), $plural),
            'choose_from_most_used'      => sprintf(__('Choose from the most used %s', 'simpletags'), $plural),
        ],
    ];

    return $preserved_labels[$type][$key];
}

/**
 * Returns an array of translated labels, ready for use with sprintf().
 *
 * Replacement for taxopress_get_preserved_label for the sake of performance.
 *
 * @return array
 */
function taxopress_get_preserved_labels()
{
    return [
        'post_types' => [
            'singular' => [
                'add_new_item' => __('Add new %s', 'simpletags'),
                'edit_item'    => __('Edit %s', 'simpletags'),
                'new_item'     => __('New %s', 'simpletags'),
                'view_item'    => __('View %s', 'simpletags'),
            ],
            'plural'   => [
                'view_items'         => __('View %s', 'simpletags'),
                'all_items'          => __('All %s', 'simpletags'),
                'search_items'       => __('Search %s', 'simpletags'),
                'not_found'          => __('No %s found.', 'simpletags'),
                'not_found_in_trash' => __('No %s found in trash.', 'simpletags'),
            ],
        ],
        'taxonomies' => [
            'singular' => [
                'parent_item'       => __('Parent %s', 'simpletags'),
                'parent_item_colon' => __('Parent %s:', 'simpletags'),
                'edit_item'         => __('Edit %s', 'simpletags'),
                'update_item'       => __('Update %s', 'simpletags'),
                'add_new_item'      => __('Add new %s', 'simpletags'),
                'new_item_name'     => __('New %s name', 'simpletags'),
            ],
            'plural'   => [
                'search_items'               => __('Search %s', 'simpletags'),
                'popular_items'              => __('Popular %s', 'simpletags'),
                'all_items'                  => __('All %s', 'simpletags'),
                'separate_items_with_commas' => __('Separate %s with commas', 'simpletags'),
                'add_or_remove_items'        => __('Add or remove %s', 'simpletags'),
                'choose_from_most_used'      => __('Choose from the most used %s', 'simpletags'),
            ],
        ],
    ];
}


function get_all_taxopress_taxonomies()
{

    $category              = get_taxonomies(
        ['name' => 'category'],
        'objects');
    $post_tag              = get_taxonomies(
        ['name' => 'post_tag'],
        'objects');
    $public                = get_taxonomies([
        '_builtin' => false,
        'public'   => true,
    ],
        'objects');
    $private               = get_taxonomies([
        '_builtin' => false,
        'public'   => false,
    ],
        'objects');
    $registered_taxonomies = array_merge($category, $post_tag, $public, $private);

    return $registered_taxonomies;
}

/**
 * Return an array of all deactivated taxonomy.
 *
 * @return array TAXOPRESS taxonomy.
 */
function taxopress_get_deactivated_taxonomy()
{
    $taxonomies = get_option('taxopress_deactivated_taxonomies');
    if (!empty($taxonomies)) {
        return (array)$taxonomies;
    }

    return [];
}

/**
 * None callback.
 */
function taxopress_noaction_admin_notice()
{
    echo taxopress_admin_notices_helper(esc_html__('Kindly select an action in bulk action dropdown', 'simpletags'),
        false);
}

/**
 * None callback.
 */
function taxopress_none_admin_notice()
{
    echo taxopress_admin_notices_helper(esc_html__('Kindly select atleast one taxonomy to proceed', 'simpletags'),
        false);
}

/**
 * Deactivated callback.
 */
function taxopress_deactivated_admin_notice()
{
    echo taxopress_admin_notices_helper(esc_html__('Taxonomy has been successfully deactivated', 'simpletags'));
}

/**
 * Activated callback.
 */
function taxopress_activated_admin_notice()
{
    echo taxopress_admin_notices_helper(esc_html__('Taxonomy has been successfully activated', 'simpletags'));
}

/**
 * Delete callback.
 */
function taxopress_taxdeleted_admin_notice()
{
    echo taxopress_admin_notices_helper(esc_html__('Taxonomy has been successfully deleted', 'simpletags'));
}

/**
 * Deactivated taxonomy.
 *
 * @return array TAXOPRESS taxonomy.
 */
function taxopress_deactivate_taxonomy($term_object)
{
    $all_taxonomies   = (array)get_option('taxopress_deactivated_taxonomies');
    $all_taxonomies[] = $term_object;
    $all_taxonomies   = array_unique(array_filter($all_taxonomies));
    $success          = update_option('taxopress_deactivated_taxonomies', $all_taxonomies);
}

/**
 * Activate taxonomy.
 *
 * @return array TAXOPRESS taxonomy.
 */
function taxopress_activate_taxonomy($term_object)
{
    $all_taxonomies = (array)get_option('taxopress_deactivated_taxonomies');
    if (($key = array_search($term_object, $all_taxonomies)) !== false) {
        unset($all_taxonomies[$key]);
        $success = update_option('taxopress_deactivated_taxonomies', $all_taxonomies);
    }
}


/**
 * Fetch our TAXOPRESS disabled taxonomies option.
 *
 * @return mixed
 */
function taxopress_get_deactivated_taxonomy_data()
{
    return apply_filters('taxopress_get_deactivated_taxonomy_data', get_option('taxopress_deactivated_taxonomies', []),
        get_current_blog_id());
}


/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_filter_removable_query_args(array $args)
{
    return array_merge($args, [
        'action',
        'taxonomy',
        '_wpnonce',
    ]);
}

/**
 * Filters the list of query arguments which get removed from admin area URLs in WordPress.
 *
 * @link https://core.trac.wordpress.org/ticket/23367
 *
 * @param string[] $args Array of removable query arguments.
 * @return string[] Updated array of removable query arguments.
 */
function taxopress_filter_removable_query_args_2(array $args)
{
    return array_merge($args, [
        'action2',
        'taxonomy',
        '_wpnonce',
    ]);
}


/**
 * Delete our custom taxonomy from the array of taxonomies.
 * @return bool|string False on failure, string on success.
 */
function taxopress_action_delete_taxonomy($term_object)
{

    $data = [
        'cpt_custom_tax' => [
            'name' => $term_object,
        ],
    ];
    // Check if they selected one to delete.
    if (empty($data['cpt_custom_tax']['name'])) {
        return taxopress_admin_notices('error', '', false,
            esc_html__('Please provide a taxonomy to delete', 'simpletags'));
    }

    /**
     * Fires before a taxonomy is deleted from our saved options.
     *
     *
     * @param array $data Array of taxonomy data we are deleting.
     */
    do_action('taxopress_before_delete_taxonomy', $data);

    $taxonomies = taxopress_get_taxonomy_data();

    if (array_key_exists(strtolower($data['cpt_custom_tax']['name']), $taxonomies)) {

        unset($taxonomies[$data['cpt_custom_tax']['name']]);

        /**
         * Filters whether or not 3rd party options were saved successfully within taxonomy deletion.
         *
         * @param bool $value Whether or not someone else saved successfully. Default false.
         * @param array $taxonomies Array of our updated taxonomies data.
         * @param array $data Array of submitted taxonomy to update.
         */
        if (false === ($success = apply_filters('taxopress_taxonomy_delete_tax', false, $taxonomies, $data))) {
            $success = update_option('taxopress_taxonomies', $taxonomies);
        }
    }
    delete_option("default_term_{$data['cpt_custom_tax']['name']}");

    /**
     * Fires after a taxonomy is deleted from our saved options.
     *
     *
     * @param array $data Array of taxonomy data that was deleted.
     */
    do_action('taxopress_after_delete_taxonomy', $data);

    // Used to help flush rewrite rules on init.
    set_transient('taxopress_flush_rewrite_rules', 'true', 5 * 60);

    if (isset($success)) {
        add_action('admin_notices', "taxopress_taxdeleted_admin_notice");

        return 'delete_success';
    }

    add_action('admin_notices', "taxopress_delete_fail_admin_notice");

    return 'delete_fail';
}

function unregister_tags()
{

    $all_taxonomies = (array)get_option('taxopress_deactivated_taxonomies');
    $all_taxonomies = array_unique(array_filter($all_taxonomies));

    if (count($all_taxonomies) > 0) {
        foreach ($all_taxonomies as $taxonomy) {

            if (!empty($_GET) && isset($_GET['page']) && 'st_taxonomies' == $_GET['page']) {
                $remove_current_taxonomy = $taxonomy;
                add_action('admin_menu', 'taxopress_remove_taxonomy_from_menus');
            } else {
                taxopress_unregister_taxonomy($taxonomy);
            }
        }
    }
}

add_action('init', 'unregister_tags');

// Remove menu
function taxopress_remove_taxonomy_from_menus()
{
    global $remove_current_taxonomy;
    remove_menu_page('edit-tags.php?taxonomy=post_tag');
}


function taxopress_unregister_taxonomy($taxonomy)
{
    if (!taxonomy_exists($taxonomy)) {
        return new WP_Error('invalid_taxonomy', __('Invalid taxonomy.'));
    }

    $taxonomy_object = get_taxonomy($taxonomy);

    // Do not allow unregistering internal taxonomies.
    /*if ( $taxonomy_object->_builtin ) {
        return new WP_Error( 'invalid_taxonomy', __( 'Unregistering a built-in taxonomy is not allowed.' ) );
    }*/

    global $wp_taxonomies;

    $taxonomy_object->remove_rewrite_rules();
    $taxonomy_object->remove_hooks();

    // Remove custom taxonomy default term option.
    if (!empty($taxonomy_object->default_term)) {
        delete_option('default_term_' . $taxonomy_object->name);
    }

    // Remove the taxonomy.
    unset($wp_taxonomies[$taxonomy]);

    /**
     * Fires after a taxonomy is unregistered.
     *
     * @param string $taxonomy Taxonomy name.
     */
    do_action('unregistered_taxonomy', $taxonomy);

    return true;
}


function taxopress_convert_external_taxonomy($taxonomy_object, $request_tax)
{

    if (array_key_exists($request_tax, taxopress_get_extername_taxonomy_data())) {
        return taxopress_get_extername_taxonomy_data()[$request_tax];
    }

    $taxonomy_data = (array)$taxonomy_object;

    foreach ($taxonomy_data as $key => $value) {
        //change label to array
        if ($key === 'labels') {
            $taxonomy_data[$key] = (array)$value;
        }
        //change cap to array
        if ($key === 'cap') {
            $taxonomy_data[$key] = (array)$value;
        }
        //change default terms to strings
        if ($key === 'default_term') {
            if (is_array($value) && count($value) > 0) {
                $taxonomy_data[$key] = join(',', array_filter($value));
            }
        }
        //set query var value if not empty
        if ($key === 'query_var') {
            if (empty(trim($value))) {
                $taxonomy_data['query_var']      = 'false';
                $taxonomy_data['query_var_slug'] = '';
            } else {
                $taxonomy_data['query_var']      = 'true';
                $taxonomy_data['query_var_slug'] = $value;
            }
        }
        //set rewrite
        if ($key === 'rewrite') {
            if(!empty($value) && is_array($value)){
            if (count($value) > 0) {
                foreach ($value as $holdkey => $holdvalue) {
                    if ($holdkey === 'with_front') {
                        $taxonomy_data['rewrite_withfront'] = is_bool($holdvalue) ? taxopress_disp_boolean($holdvalue) : $holdvalue;
                    } else {
                        $taxonomy_data[$key . '_' . $holdkey] = is_bool($holdvalue) ? taxopress_disp_boolean($holdvalue) : $holdvalue;
                    }
                }
            }
            $taxonomy_data[$key] = (count($value) > 0) ? 'true' : 'false';
        }else{
            $taxonomy_data[$key] = 'false';
            $taxonomy_data['rewrite_hierarchical'] = '';
            $taxonomy_data['rewrite_withfront'] = '';
        }
        }
        //dispose bool value
        if (is_bool($value)) {
            $taxonomy_data[$key] = taxopress_disp_boolean($value);
        }
    }
    //add singular label
    $taxonomy_data['singular_label'] = $taxonomy_data['labels']['singular_name'];
    //add object terms
    $taxonomy_data['object_types'] = $taxonomy_data['object_type'];

    return $taxonomy_data;
}

/**
 * Register our users' custom taxonomies.
 *
 * @internal
 */
function taxopress_recreate_custom_taxonomies()
{
    $taxes = taxopress_get_extername_taxonomy_data();

    if (empty($taxes)) {
        return;
    }
    /**
     * Fires before the start of the taxonomy registrations.
     *
     * @param array $taxes Array of taxonomies to register.
     */
    do_action('taxopress_pre_register_taxonomies', $taxes);

    if (is_array($taxes)) {
        foreach ($taxes as $tax) {
            taxopress_re_register_single_taxonomy($tax);
        }
    }

    /**
     * Fires after the completion of the taxonomy registrations.
     *
     * @param array $taxes Array of taxonomies registered.
     */
    do_action('taxopress_post_register_taxonomies', $taxes);
}

add_action('init', 'taxopress_recreate_custom_taxonomies', 99);  // Leave on standard init for legacy purposes.

/**
 * Helper function to register the actual taxonomy.
 *
 * @param array $taxonomy Taxonomy array to register. Optional.
 * @return null Result of register_taxonomy.
 * @internal
 *
 */
function taxopress_re_register_single_taxonomy($taxonomy)
{

    $labels = [
        'name'          => $taxonomy['label'],
        'singular_name' => $taxonomy['singular_label'],
    ];

    $description = '';
    if (!empty($taxonomy['description'])) {
        $description = $taxonomy['description'];
    }

    $preserved        = taxopress_get_preserved_keys('taxonomies');
    $preserved_labels = taxopress_get_preserved_labels();
    foreach ($taxonomy['labels'] as $key => $label) {
        if (!empty($label)) {
            $labels[$key] = $label;
        } elseif (empty($label) && in_array($key, $preserved, true)) {
            $singular_or_plural = (in_array($key,
                array_keys($preserved_labels['taxonomies']['plural']))) ? 'plural' : 'singular';
            $label_plurality    = ('plural' === $singular_or_plural) ? $taxonomy['label'] : $taxonomy['singular_label'];
            $labels[$key]       = sprintf($preserved_labels['taxonomies'][$singular_or_plural][$key], $label_plurality);
        }
    }

    $rewrite = get_taxopress_disp_boolean($taxonomy['rewrite']);
    if (false !== get_taxopress_disp_boolean($taxonomy['rewrite'])) {
        $rewrite               = [];
        $rewrite['slug']       = !empty($taxonomy['rewrite_slug']) ? $taxonomy['rewrite_slug'] : $taxonomy['name'];
        $rewrite['with_front'] = true;
        if (isset($taxonomy['rewrite_withfront'])) {
            $rewrite['with_front'] = ('false' === taxopress_disp_boolean($taxonomy['rewrite_withfront'])) ? false : true;
        }
        $rewrite['hierarchical'] = false;
        if (isset($taxonomy['rewrite_hierarchical'])) {
            $rewrite['hierarchical'] = ('true' === taxopress_disp_boolean($taxonomy['rewrite_hierarchical'])) ? true : false;
        }
    }

    if (in_array($taxonomy['query_var'], ['true', 'false', '0', '1'], true)) {
        $taxonomy['query_var'] = get_taxopress_disp_boolean($taxonomy['query_var']);
    }
    if (true === $taxonomy['query_var'] && !empty($taxonomy['query_var_slug'])) {
        $taxonomy['query_var'] = $taxonomy['query_var_slug'];
    }

    $public             = (!empty($taxonomy['public']) && false === get_taxopress_disp_boolean($taxonomy['public'])) ? false : true;
    $publicly_queryable = (!empty($taxonomy['publicly_queryable']) && false === get_taxopress_disp_boolean($taxonomy['publicly_queryable'])) ? false : true;
    if (empty($taxonomy['publicly_queryable'])) {
        $publicly_queryable = $public;
    }

    $show_admin_column = (!empty($taxonomy['show_admin_column']) && false !== get_taxopress_disp_boolean($taxonomy['show_admin_column'])) ? true : false;

    $show_in_menu = (!empty($taxonomy['show_in_menu']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_menu'])) ? true : false;

    if (empty($taxonomy['show_in_menu'])) {
        $show_in_menu = get_taxopress_disp_boolean($taxonomy['show_ui']);
    }

    $show_in_nav_menus = (!empty($taxonomy['show_in_nav_menus']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_nav_menus'])) ? true : false;
    if (empty($taxonomy['show_in_nav_menus'])) {
        $show_in_nav_menus = $public;
    }

    $show_in_rest = (!empty($taxonomy['show_in_rest']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_rest'])) ? true : false;

    $show_in_quick_edit = (!empty($taxonomy['show_in_quick_edit']) && false !== get_taxopress_disp_boolean($taxonomy['show_in_quick_edit'])) ? true : false;

    $rest_base = null;
    if (!empty($taxonomy['rest_base'])) {
        $rest_base = $taxonomy['rest_base'];
    }

    $rest_controller_class = null;
    if (!empty($post_type['rest_controller_class'])) {
        $rest_controller_class = $post_type['rest_controller_class'];
    }

    $meta_box_cb = null;
    if (!empty($taxonomy['meta_box_cb'])) {
        $meta_box_cb = (false !== get_taxopress_disp_boolean($taxonomy['meta_box_cb'])) ? $taxonomy['meta_box_cb'] : false;
    }
    $default_term = null;
    if (!empty($taxonomy['default_term'])) {
        $term_parts = explode(',', $taxonomy['default_term']);
        if (!empty($term_parts[0])) {
            $default_term['name'] = trim($term_parts[0]);
        }
        if (!empty($term_parts[1])) {
            $default_term['slug'] = trim($term_parts[1]);
        }
        if (!empty($term_parts[2])) {
            $default_term['description'] = trim($term_parts[2]);
        }
    }

    $args = [
        'labels'                => $labels,
        'label'                 => $taxonomy['label'],
        'description'           => $description,
        'public'                => $public,
        'publicly_queryable'    => $publicly_queryable,
        'hierarchical'          => get_taxopress_disp_boolean($taxonomy['hierarchical']),
        'show_ui'               => get_taxopress_disp_boolean($taxonomy['show_ui']),
        'show_in_menu'          => $show_in_menu,
        'show_in_nav_menus'     => $show_in_nav_menus,
        'query_var'             => $taxonomy['query_var'],
        'rewrite'               => $rewrite,
        'show_admin_column'     => $show_admin_column,
        'show_in_rest'          => $show_in_rest,
        'rest_base'             => $rest_base,
        'rest_controller_class' => $rest_controller_class,
        'show_in_quick_edit'    => $show_in_quick_edit,
        'meta_box_cb'           => $meta_box_cb,
        'default_term'          => $default_term,
    ];

    $object_type = !empty($taxonomy['object_types']) ? $taxonomy['object_types'] : '';

    /**
     * Filters the arguments used for a taxonomy right before registering.
     *
     * @param array $args Array of arguments to use for registering taxonomy.
     * @param string $value Taxonomy slug to be registered.
     * @param array $taxonomy Original passed in values for taxonomy.
     * @param array $object_type Array of chosen post types for the taxonomy.
     */
    $args = apply_filters('taxopress_pre_register_taxonomy', $args, $taxonomy['name'], $taxonomy, $object_type);

    return register_taxonomy($taxonomy['name'], $object_type, $args);
}
