<?php

/**
 * TODO : Autoload classes
 */
require_once plugin_dir_path(__FILE__) . 'classes/TaxoPressAiAjax.php';
require_once plugin_dir_path(__FILE__) . 'classes/TaxoPressAiApi.php';
require_once plugin_dir_path(__FILE__) . 'classes/TaxoPressAiFields.php';
require_once plugin_dir_path(__FILE__) . 'classes/TaxoPressAiUtilities.php';

if (!class_exists('TaxoPress_AI_Module')) {
    /**
     * class TaxoPress_AI_Module
     */
    class TaxoPress_AI_Module
    {
        const MENU_SLUG = 'st_options';

        const PAGE_MENU_SLUG = 'st_taxopress_ai';

        const TAXOPRESS_AI_OPTION_KEY = 'st_taxopress_ai_settings';

        // class instance
        static $instance;

        /**
         * Construct the TaxoPress_AI_Module class
         */
        public function __construct()
        {
            // Admin menu
            add_action('admin_menu', [$this, 'admin_menu']);
            // Script and Styles
            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts'], 11);
            // Migrate Auto Terms and Suggest Terms Dandelion and Open Calais API
            add_action('admin_init', [$this, 'migrate_legacy_settings']);
            // Save settings data
            add_action('admin_init', [$this, 'save_settings']);
            // Handle ai integration preview post search
            add_action('wp_ajax_taxopress_ai_post_search', ['TaxoPressAiAjax', 'handle_ai_post_search']);
            // Handle AI preview ajax request
            add_action('wp_ajax_taxopress_ai_preview_feature', ['TaxoPressAiAjax', 'handle_taxopress_ai_preview_feature']);
            // Handle AI post terms update.
            add_action('wp_ajax_taxopress_ai_add_post_term', ['TaxoPressAiAjax', 'handle_taxopress_ai_post_term']);
            // Handle AI new term.
            add_action('wp_ajax_taxopress_ai_add_new_term', ['TaxoPressAiAjax', 'handle_taxopress_ai_new_term']);
            // Ajax action, JS Helper and admin action
            add_action( 'wp_ajax_simpletags', [$this, 'ajax_check']);
            // Taxopress AI metabox default result on page load
            add_action( 'load_taxopress_ai_term_results', [$this, 'load_result']);
            // Register metabox for suggest tags, for post, and optionnaly cpt.
            add_action( 'admin_head', [$this, 'admin_head'], 1);

            add_filter('taxopress_validate_term_before_insert', ['TaxoPressAiAjax', 'taxopress_validate_term_before_insert'], 10, 2);
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
         * Migrate Auto Terms and Suggest Terms Dandelion and Open Calais API
         *
         * @return void
         */
        public function migrate_legacy_settings() {

            if (!get_option('migrate_taxopress_ai_legacy_api') && current_user_can('simple_tags')) {
                $taxopress_ai_settings = get_option(self::TAXOPRESS_AI_OPTION_KEY);
                if (empty($taxopress_ai_settings)) {
                    $suggestterm_datas = function_exists('taxopress_get_suggestterm_data') ? taxopress_get_suggestterm_data() : [];
                    $autoterm_datas = function_exists('taxopress_get_autoterm_data') ? taxopress_get_autoterm_data() : [];
                    $taxopress_ai_settings = [];

                    $dandelion_api_token = false;
                    $open_calais_api     = false;

                    if (!empty($suggestterm_datas) && is_array($suggestterm_datas)) {
                        foreach ($suggestterm_datas as $suggestterm_data) {
                            if (!$dandelion_api_token && !empty($suggestterm_data['terms_datatxt_access_token'])) {
                                $dandelion_api_token = $suggestterm_data['terms_datatxt_access_token'];
                            }
                            if (!$open_calais_api && !empty($suggestterm_data['terms_opencalais_key'])) {
                                $open_calais_api = $suggestterm_data['terms_opencalais_key'];
                            }

                        }
                    }

                    if (!empty($autoterm_datas) && is_array($autoterm_datas)) {
                        foreach ($autoterm_datas as $autoterm_data) {
                            if (!$dandelion_api_token && !empty($autoterm_data['terms_datatxt_access_token'])) {
                                $dandelion_api_token = $autoterm_data['terms_datatxt_access_token'];
                            }
                            if (!$open_calais_api && !empty($autoterm_data['terms_opencalais_key'])) {
                                $open_calais_api = $autoterm_data['terms_opencalais_key'];
                            }

                        }
                    }

                    if ($dandelion_api_token) {
                        $taxopress_ai_settings['dandelion_api_token'] = $dandelion_api_token;
                    }

                    if ($open_calais_api) {
                        $taxopress_ai_settings['open_calais_api_key'] = $open_calais_api;
                    }

                    if (!empty($taxopress_ai_settings)) {
                        update_option(self::TAXOPRESS_AI_OPTION_KEY, $taxopress_ai_settings);
                    }
                }
                update_option('migrate_taxopress_ai_legacy_api', true);
            }
        }

        public function save_settings()
        {

            if (wp_doing_ajax()) {
                return;
            }

            if (!is_admin()) {
                return;
            }

            if (!current_user_can('simple_tags')) {
                return;
            }

            if (empty($_GET)) {
                return;
            }

            if (!isset($_GET['page'])) {
                return;
            }

            if ($_GET['page'] !== self::PAGE_MENU_SLUG) {
                return;
            }

            if (!empty($_POST['taxopress_ai_integration']) && !empty($_POST['_wpnonce']) && wp_verify_nonce(sanitize_key($_POST['_wpnonce']), 'taxopress_ai_settings_nonce_action')) {
                $sanitized_data = map_deep($_POST['taxopress_ai_integration'], 'sanitize_text_field');

                foreach (['open_ai', 'ibm_watson', 'dandelion', 'open_calais'] as $field) {
                    if (!isset($sanitized_data[$field . '_cache_result'])) {
                        $sanitized_data[$field . '_cache_result'] = 0;
                    }
                }
                foreach (['open_ai', 'ibm_watson', 'dandelion', 'open_calais', 'suggest_local_terms', 'existing_terms', 'post_terms'] as $field) {
                    if (!isset($sanitized_data[$field . '_show_post_count'])) {
                        $sanitized_data[$field . '_show_post_count'] = 0;
                    }
                }

                update_option(self::TAXOPRESS_AI_OPTION_KEY, $sanitized_data);
                add_action('admin_notices', function () {
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo taxopress_admin_notices_helper(esc_html__('Settings updated successfully.', 'simple-tags'));
                });
            }
        }

        /**
         * Init somes JS and CSS need for this feature
         *
         * @return void
         * @author Olatechpro
         */
        public function admin_enqueue_scripts()
        {
            global $pagenow;

            if (isset($_GET['page']) && $_GET['page'] == self::PAGE_MENU_SLUG ||
                    (
                        in_array($pagenow, ['post-new.php', 'post.php', 'page.php', 'page-new.php', 'edit.php']) && 
                        !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_' . get_post_type() . '_metabox'))
                    )
            ) {

                if ($pagenow && !empty($_GET['post'])) {
                    $main_post_screen = false;
                } else {
                    $main_post_screen = true;
                }
                
                $fast_update_screen = isset($_GET['page']) && $_GET['page'] == self::PAGE_MENU_SLUG;

                if ($fast_update_screen) {
                    $tp_current_screen = self::PAGE_MENU_SLUG;
                } else {
                    $tp_current_screen = 'post.php';
                }
                
                $manage_link = add_query_arg(
                    [
                        'page' => 'st_taxopress_ai'
                    ],
                    admin_url('admin.php')
                );

                $removed_taxonomies = taxopress_user_role_removed_taxonomy();
                $removed_taxonomies_tax = $removed_taxonomies['taxonomies'];
                $removed_taxonomies_css = $removed_taxonomies['custom_css'];



                wp_enqueue_style('taxopress-admin-select2');
                wp_enqueue_script('taxopress-admin-select2');

                wp_enqueue_script( 'taxopress-ai-editor-js', plugins_url('', __FILE__) . '/assets/js/taxopress-ai-editor.js', array(
                    'jquery', 'taxopress-admin-select2'
                ), STAGS_VERSION );

                wp_enqueue_style('taxopress-ai-editor-css', plugins_url('', __FILE__) . '/assets/css/taxopress-ai-editor.css', [], STAGS_VERSION, 'all');

                if (!empty($removed_taxonomies_css) && !$main_post_screen && !$fast_update_screen) {
                    wp_add_inline_style('taxopress-ai-editor-css', '' . implode(',', $removed_taxonomies_css) . ' {display:none !important;}');
                }

                wp_localize_script(
                    'taxopress-ai-editor-js',
                    'taxoPressAIRequestAction',
                    [
                        'requiredSuffix' => esc_html__('Please choose a post to use with TaxoPress AI.', 'simple-tags'),
                        'nonce' => wp_create_nonce('taxopress-ai-ajax-nonce'),
                        'apiEditLink' => '',//'<span class="edit-suggest-term-metabox"> <a target="blank" href="' . $manage_link . '"> '. esc_html__('Manage API Configuration', 'simple-tags') .' </a></span>',
                        'fieldTabs' => TaxoPressAiFields::get_fields_tabs(),
                        'removed_tax' => $removed_taxonomies_tax,
                        'current_screen' => $tp_current_screen,
                    ]
                );
                
            }
        }

        /**
         * Add WP admin menu for taxopress ai
         *
         * @return void
         * @author ojopau;
         */
        public function admin_menu()
        {
            $hook = add_submenu_page(
                self::MENU_SLUG,
                esc_html__('Fast Update', 'simple-tags'),
                esc_html__('Fast Update', 'simple-tags'),
                'simple_tags',
                self::PAGE_MENU_SLUG,
                [
                    $this,
                    'page_manage_taxopress_ai',
                ]
            );
        }


        /**
         * @return void
         */
        public function page_manage_taxopress_ai()
        {

            settings_errors(__CLASS__);
            $post = 0;

            ?>
            

            <div class="wrap st_wrap st_taxopress_ai-wrap st-manage-taxonomies-page <?php echo esc_attr(self::PAGE_MENU_SLUG . '-wrap'); ?>">
                <h1>
                    <?php echo esc_html__('Fast Update', 'simple-tags'); ?>
                </h1>
                <div class="taxopress-description">
                    <?php esc_html_e('This screen allows you to quickly edit the terms on multiple posts. This feature uses the same metabox you see when editing posts.', 'simple-tags'); ?> <a target="_blank" href="<?php echo admin_url('admin.php?page=st_options&active_tab=taxopress-ai') ?>"><?php echo esc_html__('Configure the metabox settings', 'simple-tags'); ?></a>.
                </div>
                <div class="wp-clearfix"></div>
                <form method="post" action="">
                    <input type="hidden" name="taxopress_ai_integration[active_tab]" class="taxopress-active-subtab" />
                    <?php wp_nonce_field('taxopress_ai_settings_nonce_action'); ?>
                        <div id="poststuff">
                            <div id="post-body" class="taxopress-section metabox-holder columns-2">
                                <div>
                                    <div id="post-body-content" class="right-body-content" style="position: relative;">
                                        <?php $this->editor_metabox($post, $context = 'fast_update'); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                </form>
            </div>
        <?php
        }

        /**
         * Get a rendered field partial
         *
         * @param array $args Arguments to render in the partial.
         */
        private static function get_rendered_field_partial($args)
        {
            $defaults = [
                'description' => '',
                'type' => 'text',
                'tab' => 'general',
                'value' => '',
                'key' => '',
                'other_attr' => '',
                'label' => '',
                'required' => false,
                'multiple' => false,
                'classes' => '',
                'options' => [],
            ];
            $args = array_merge($defaults, $args);

            $key = $args['key'];
            ?>
                    <tr valign="top" class="<?php echo esc_attr('form-field settings-' . $key . '-wrap'); ?>">
                        <th scope="row">
                            <?php if (!empty($args['label'])): ?>
                                <label for="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($args['label']); ?>
                                </label>
                                <?php if ($args['required']) { ?>
                                    <span class="required">*</span>
                                <?php } ?>
                            <?php endif; ?>
                        </th>
                        <td>
                            <?php
                            if ($args['type'] === 'checkbox'):
                                ?>
                                <input name="taxopress_ai_integration[<?php echo esc_attr($key); ?>]" id="<?php echo esc_attr($key); ?>"
                                    type="<?php echo esc_attr($args['type']); ?>" value="1" <?php checked(1, (int) $args['value']); ?>
                                    <?php echo ($args['required'] ? 'required="true"' : ''); ?> />
                                <?php if (!empty($args['description'])): ?>
                                    <span class="description">
                                        <?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </span>
                                <?php endif; ?>
                            <?php 
                            elseif ($args['type'] === 'select') : ?>
                                <select 
                                    name="taxopress_ai_integration[<?php echo esc_attr($key); ?>]<?php echo $args['multiple'] ? '[]' : '';?>"
                                    id="<?php echo esc_attr($key); ?>"
                                    class="<?php echo $args['classes']; ?>"
                                    style="width: 95%;"
                                    data-placeholder="<?php printf(esc_html__('Select %s', 'simple-tags'), esc_html(strtolower($args['label']))); ?>"
                                    <?php echo $args['other_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    <?php echo ($args['multiple'] ? 'multiple' : '');?>
                                    <?php echo ($args['required'] ? 'required="true"' : '');?>>
                                    <?php
                                    foreach ($args['options'] as $select_key => $select_label) {
                                        if ($args['multiple']) {
                                            $selected_option = (isset($args['value']) && is_array($args['value']) && in_array($select_key, $args['value'])) ? true : false;
                                        } else {
                                            $selected_option = (isset($args['value']) && $select_key == $args['value']) ? true : false;
                                        }
                                        ?>
                                        <option value="<?php esc_attr_e($select_key); ?>"
                                                <?php selected(true, $selected_option); ?>>
                                                <?php echo esc_html($select_label); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <?php if (isset($args['description'])) : ?>
                                    <p class="description">
                                        <?php echo esc_html($args['description']); ?>
                                    </p>
                                <?php endif; ?>
                            <?php elseif ($args['type'] === 'textarea') : ?>
                                <?php
                                $required_attr = ($args['required'] ? 'required="true"' : '');
                                ?>
                                <textarea style="min-height: 150px;" name="taxopress_ai_integration[<?php echo esc_attr($key); ?>]" id="<?php echo esc_attr($key); ?>" <?php echo $required_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>                 <?php echo $args['other_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><?php echo sanitize_textarea_field(stripslashes_deep($args['value'])); ?></textarea>
                                <?php if (isset($args['description'])): ?>
                                    <p class="description">
                                        <?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </p>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php
                                $required_attr = ($args['required'] ? 'required="true"' : '');
                                ?>
                                <input name="taxopress_ai_integration[<?php echo esc_attr($key); ?>]" id="<?php echo esc_attr($key); ?>"
                                    type="<?php echo esc_attr($args['type']); ?>" value="<?php echo esc_attr($args['value']); ?>" <?php echo $required_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>                 <?php echo $args['other_attr']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> />
                                <?php if (isset($args['description'])): ?>
                                    <p class="description">
                                        <?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                    </p>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
        }

        /**
         * Ajax action, JS Helper and admin action
         *
         */
        public function ajax_check() {
            if ( isset( $_GET['stags_action'] ) && $_GET['stags_action'] == 'tags_from_local_db' ) {
                self::ajax_suggest_local();
            } else {
                status_header( 200 );
                header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );
                echo '<p>' . esc_html__( 'Invalid request.', 'simple-tags' ) . '</p>';
                exit();
            }
        }

        /**
         * Suggest tags from local database
         *
         */
        public static function ajax_suggest_local() {
            status_header( 200 );
            header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );
    
    
            $taxonomy =  'post_tag';
    
            if (!empty($_GET['taxonomy'])) {
                $taxonomy = sanitize_key($_GET['taxonomy']);
            } elseif(isset($_GET['suggestterms'])) {
                $suggestterms = taxopress_get_suggestterm_data();
                $selected_suggestterm = (int)$_GET['suggestterms'];
    
                if (array_key_exists($selected_suggestterm, $suggestterms)) {
                    $taxonomy       = $suggestterms[$selected_suggestterm]['taxonomy'];
                }
            }
    
            if ( ( (int) wp_count_terms( $taxonomy, array( 'hide_empty' => false ) ) ) == 0 ) { // No tags to suggest
                echo '<p>' . esc_html__( 'No terms in your WordPress database.', 'simple-tags' ) . '</p>';
                exit();
            }
    
            // Get data
            $post_id = ( isset( $_POST['post_id'] ) ) ? intval( $_POST['post_id'] ) : 0;
            $content = stripslashes( sanitize_textarea_field($_POST['content'])) . ' ' . stripslashes( sanitize_text_field($_POST['title']));
            $content = trim( $content );
    
            if ( empty( $content ) ) {
                echo '<p>' . esc_html__( 'There\'s no content to scan.', 'simple-tags' ) . '</p>';
                exit();
            }
    
            // Get all terms
            $terms = SimpleTags_Admin::getTermsForAjax( $taxonomy, '' );
            if ( empty( $terms ) || $terms == false ) {
                echo '<p>' . esc_html__( 'No results from your WordPress database.', 'simple-tags' ) . '</p>';
                exit();
            }
    
            // Get terms for current post
            $post_terms = array();
            if ( $post_id > 0 ) {
                $post_terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
            }
    
            $flag = false;
            $click_terms = [];
            foreach ( (array) $terms as $term ) {
                $class_current = in_array($term->term_id, $post_terms) ? 'used_term' : '';
                $term_id = $term->term_id;
                $term = stripslashes( $term->name );
                
                $add_terms = [];
                $add_terms[$term] = $term_id;
                $primary_term = $term;
    
                // add term synonyms
                $term_synonyms = taxopress_get_term_synonyms($term_id);
                if (!empty($term_synonyms)) {
                    foreach ($term_synonyms as $term_synonym) {
                        $add_terms[$term_synonym] = $term_id;
                    }
                }
    
                // add linked term
                $add_terms = taxopress_add_linked_term_options($add_terms, $term_id, $taxonomy);
                
                foreach ($add_terms as $add_name => $add_term_id) {
                    if (is_string($add_name) && ! empty($add_name) && stristr($content, $add_name) && !in_array($primary_term, $click_terms)) {
                        $click_terms[] = $primary_term;
                        $flag = true;
                        echo '<span data-term_id="'.esc_attr($term_id).'" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr($class_current) . '" tabindex="0" role="button" aria-pressed="false">' . esc_html($primary_term) . '</span>' . "\n";
                    }
                }
            }
    
            if ( $flag == false ) {
                echo '<p>' . esc_html__( 'There are no terms that are relevant to your content.', 'simple-tags' ) . '</p>';
            } else {
                echo '<div class="clear"></div>';
            }
    
            exit();
        }

        public function load_result($args) {

            if (empty($args)) {
                return;
            }

            $action       = $args['action'];
            $taxonomy     = $args['taxonomy'];
            $ai_group     = $args['ai_group'];
            $post_title   = $args['post_title'];
            $post_content = $args['post_content'];
            $post_id 	  = $args['post_id'];
            $post         = get_post($post_id);
    
            if (in_array($ai_group, ['existing_terms', '__suggest_local_terms', 'post_terms'])) {
                $content = $post_content . ' ' . $post_title;
                $settings_data = TaxoPressAiUtilities::taxopress_get_ai_settings_data($post->post_type);
                $result_args = [
                    'settings_data' => $settings_data,
                    'screen_source' => 'st_taxopress_ai',
                    'content' => $content,
                    'post_id' => $post_id,
                    'preview_taxonomy' => $taxonomy,
                ];
    
                if ($ai_group == 'suggest_local_terms') {
                    $result_args['suggest_terms'] = true;
                    $result_args['show_counts'] = isset($settings_data['suggest_local_terms_show_post_count']) ? $settings_data['suggest_local_terms_show_post_count'] : 0;
                    $term_results = TaxoPressAiAjax::get_existing_terms_results($result_args);
                } elseif ($ai_group == 'existing_terms') {
                    $result_args['show_counts'] = isset($settings_data['existing_terms_show_post_count']) ? $settings_data['existing_terms_show_post_count'] : 0;
                    $term_results = TaxoPressAiAjax::get_existing_terms_results($result_args);
                } elseif ($ai_group == 'post_terms') {
                    $result_args['show_counts'] = isset($settings_data['post_terms_show_post_count']) ? $settings_data['post_terms_show_post_count'] : 0;
                    $post_terms_results = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'names']);
                    if (!empty($post_terms_results)) {
                        $taxonomy_list_page = admin_url('edit-tags.php');
                        $taxonomy_list_page = add_query_arg(array(
                            'taxonomy' => $taxonomy
                        ), $taxonomy_list_page);
    
                        $legend_title  = '<a href="' . esc_url($taxonomy_list_page) . '" target="blank">' . esc_html__('Tags', 'simple-tags') . '</a>';
                        $formatted_result = TaxoPressAiUtilities::format_taxonomy_term_results($post_terms_results, $taxonomy, $post_id, $legend_title, $result_args['show_counts'], [], $result_args);
    
                        $term_results['results'] = $formatted_result;
                        $term_results['status'] = 'success';
                        $term_results['message'] = '';
                    } else {
                        $term_results['status'] = 'error';
                        $term_results['message'] =  '';
                    }
                }
    
                if (!empty($term_results['results'])) {
                    echo $term_results['results'];
                }
    
            }
        }

        /**
         * Register metabox for suggest tags, for post, and optionnaly cpt.
         *
         * @return void
         * @author WebFactory Ltd
         */
        public function admin_head() {
            global $pagenow;
    
            if (in_array($pagenow, ['post-new.php', 'post.php', 'page.php', 'page-new.php', 'edit.php']) && can_manage_taxopress_metabox() && !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_' . get_post_type() . '_metabox'))) {
                add_meta_box(
                    'taxopress-ai-suggestedtags',
                    esc_html__('TaxoPress', 'simple-tags'),
                    [$this, 'editor_metabox'],
                    get_post_type(),
                    'normal',
                    'core'
                );
            }
        }

        /**
         * Print HTML for suggest tags box
         *
         **/
        public function editor_metabox($post, $context = 'post.php') {
            $fast_update_screen = $context == 'fast_update';

            $access_metabox = $fast_update_screen ? current_user_can('simple_tags') : can_manage_taxopress_metabox();
            if (!$access_metabox) {
                return;
            }

            $default_post_type = '';

            if ($fast_update_screen) {
                foreach (TaxoPressAiUtilities::get_post_types_options() as $post_type => $post_type_object) {
                    if (!in_array($post_type, ['attachment'])) {
                        if (empty($default_post_type)) {
                            $default_post_type = $post_type;
                            $posts = $posts = get_posts(['post_type' => $post_type, 'numberposts' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC']);
                            if (!empty($posts)) {
                                $post = $posts[0];
                            }
                        }
                    }
                }
            }

            $settings_data = TaxoPressAiUtilities::taxopress_get_ai_settings_data($post->post_type);
            $fields_tabs   = TaxoPressAiFields::get_fields_tabs();
  
            $existing_terms_maximum_terms = !empty($settings_data['existing_terms_maximum_terms']) ? $settings_data['existing_terms_maximum_terms'] : '';
            $existing_terms_orderby = !empty($settings_data['existing_terms_orderby']) ? $settings_data['existing_terms_orderby'] : '';
            $existing_terms_order = !empty($settings_data['existing_terms_order']) ? $settings_data['existing_terms_order'] : '';

            $wrapper_class = $fast_update_screen ? 'fast_update_screen' : 'editor-screen'
            ?>
            <div class="taxopress-post-suggestterm <?php echo esc_attr($wrapper_class); ?>">
                <div class="taxopress-suggest-terms-contents">
                <?php
                    $all_content_tabs = [
                        'existing_terms' => [
                            'label'   => esc_html__('Show All Existing Terms', 'simple-tags'),
                            'enabled' => $fast_update_screen || !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_'. $post->post_type .'_existing_terms_tab')),
                        ],
                        'post_terms' => [
                            'label'   => esc_html__('Manage Post Terms', 'simple-tags'),
                            'enabled' => $fast_update_screen || !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_'. $post->post_type .'_post_terms_tab')),
                        ],
                        'suggest_local_terms' => [
                            'label'   => esc_html__('Auto Terms', 'simple-tags'),
                            'enabled' => $fast_update_screen || !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_'. $post->post_type .'_suggest_local_terms_tab')),
                        ],
                        'suggest_local_terms' => [
                            'label'   => esc_html__('Auto Terms', 'simple-tags'),
                            'enabled' => $fast_update_screen || !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_'. $post->post_type .'_suggest_local_terms_tab')),
                        ],
                        'create_term' => [
                            'label'   => esc_html__('Create Terms', 'simple-tags'),
                            'enabled' =>$fast_update_screen ||  !empty(SimpleTags_Plugin::get_option_value('enable_taxopress_ai_'. $post->post_type .'_create_terms_tab')),
                        ],
                    ];

                    $all_content_tabs = can_manage_taxopress_metabox_tabs($all_content_tabs);

                    foreach ($all_content_tabs as $all_content_tab_name => $all_content_tab_options) {
                        if ($all_content_tab_options['enabled']) {
                            $content_tabs[$all_content_tab_name] = $all_content_tab_options['label'];
                        }
                    }


                    $support_private_taxonomy = $fast_update_screen ? false : SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post->post_type . '_support_private_taxonomy');

                    $post_type_taxonomies = [];
                    $permitted_post_type_taxonomies = [];
                    if ($fast_update_screen) {
                        foreach (TaxoPressAiUtilities::get_taxonomies(true) as $taxonomy_name => $taxonomy_data) {
                            if (!in_array($taxonomy_name, ['post_format'])) {
                                $post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                if (in_array($taxonomy_name, ['category', 'post_tag']) && current_user_can('manage_categories')) {
                                    $permitted_post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                } elseif (!empty($taxonomy_data->cap->edit_terms) && current_user_can($taxonomy_data->cap->edit_terms)) {
                                    $permitted_post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                }
                            }
                        }
                    } else {
                        foreach(get_object_taxonomies($post->post_type, 'objects') as $taxonomy_name => $taxonomy_data) {
                            if (can_manage_taxopress_metabox_taxonomy($taxonomy_name)) {
                                $post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                if (in_array($taxonomy_name, ['category', 'post_tag']) && current_user_can('manage_categories')) {
                                    $permitted_post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                } elseif (!empty($taxonomy_data->cap->edit_terms) && current_user_can($taxonomy_data->cap->edit_terms)) {
                                    $permitted_post_type_taxonomies[$taxonomy_name] = $taxonomy_data;
                                }
                            }
                        }
                    }
                    
                    $post_type_taxonomy_names = array_keys($post_type_taxonomies);
                    $post_type_default_taxonomy = SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post->post_type . '_metabox_default_taxonomy');
                    if (empty($post_type_default_taxonomy)) {
                        $post_type_default_taxonomy = 'post_tag';
                    }

                    if (empty($post_type_taxonomy_names)) { 
                        echo '<div style="padding: 15px;">';
                        printf(esc_html__('This user does not have access to manage any of this post attached taxonomies. Enable Metabox Access Taxonomies for this role in %1sTaxoPress Settings%2s.', 'simple-tags'), '<a target="_blank" href="'. admin_url('admin.php?page=st_options#metabox') .'">', '</a>');
                        echo '</div>';
                    } elseif (empty($content_tabs)) { 
                        echo '<div style="padding: 15px;">';
                        esc_html_e('No TaxoPress Metabox features are enabled for this post type.', 'simple-tags');
                        echo '</div>';
                    } else {
                        $default_taxonomy = (in_array($post_type_default_taxonomy, $post_type_taxonomy_names) ? $post_type_default_taxonomy : $post_type_taxonomy_names[0]);
                    ?>
                    <div class="taxopress-suggest-terms-content">
                        <ul class="taxopress-tab ai-integration-tab">
                            <?php
                            $tab_index = 0;
                            foreach ($content_tabs as $key => $label) {
                                $selected_class = ($tab_index === 0) ? ' active' : '';
                            ?>
                                <li class="<?php echo esc_attr($key); ?>_tab <?php esc_attr_e($selected_class); ?>"
                                    data-content="<?php echo esc_attr($key); ?>"
                                    aria-current="<?php echo $tab_index === 0 ? 'true' : 'false'; ?>">
                                    <a href="#<?php echo esc_attr($key); ?>" class="<?php echo esc_attr($key); ?>_icon">
                                        <span>
                                            <?php esc_html_e($label); ?>
                                        </span>
                                    </a>
                                </li>
                            <?php
                                $tab_index++;
                            }
                            ?>
                        </ul>
                        <div class="st-taxonomy-content taxopress-tab-content multiple">
                            <?php
                            $auto_term_options = TaxoPressAiUtilities::get_auto_term_options();
                            $auto_term_class = count($auto_term_options) > 1 ? 'multiple-option' : 'single-option';
                            $content_index = 0;
                            foreach ($content_tabs as $key => $label) {
                                $result_request_args = [
                                    'action' 	=> 'pageload', 
                                    'taxonomy'  => $default_taxonomy,
                                    'ai_group'  => $key,
                                    'post_title'	=> $post->post_title,
                                    'post_content'  => $post->post_content,
                                    'post_id'		=> $post->ID,
                                ];
                                $button_label = !empty($fields_tabs[$key]['button_label']) ? $fields_tabs[$key]['button_label'] : esc_html__('View Terms', 'simple-tags');
                                ?>
                                <table class="taxopress-tab-content-item form-table taxopress-table taxopress-ai-tab-content <?php echo esc_attr($key); ?>"
                                    data-ai-source="<?php echo esc_attr($key); ?>"
                                    data-post_id="<?php echo esc_attr($post->ID); ?>"
                                    style="<?php echo ($content_index === 0) ? '' : 'display:none;'; ?>">
                                    <tbody>
                                        <tr>
                                            <td>
                                                <div class="taxopress-ai-fetch-wrap">
                                                    <?php if ($fast_update_screen) : ?>

                                                            <select class="preview-post-types-select taxopress-ai-select2"
                                                            style="max-width: 100px;">
                                                                <?php foreach (TaxoPressAiUtilities::get_post_types_options() as $post_type => $post_type_object): 
                                                                    if (!in_array($post_type, ['attachment'])) {
                                                                        if (empty($default_post_type)) {
                                                                            $default_post_type = $post_type;
                                                                            $posts = $posts = get_posts(['post_type' => $post_type, 'numberposts' => 1, 'post_status' => 'publish', 'orderby' => 'date', 'order' => 'DESC']);
                                                                        }
                                                                    ?>
                                                                        <option value='<?php echo esc_attr($post_type); ?>'
                                                                            data-singular_label="<?php echo esc_html($post_type_object->labels->singular_name); ?>">
                                                                            <?php echo esc_html($post_type_object->labels->name); ?>
                                                                        </option>
                                                                    <?php 
                                                                    }
                                                                endforeach; ?>
                                                            </select>

                                                            <select class="preview-post-select taxopress-ai-post-search"
                                                            style="max-width: 250px; width: 250px;"
                                                                data-placeholder="<?php echo esc_attr__('Select...', 'simple-tags'); ?>"
                                                                data-allow-clear="true"
                                                                data-nonce="<?php echo esc_attr(wp_create_nonce('taxopress-ai-post-search')); ?>">
                                                                <?php if (is_object($post)) : ?>
                                                                    <option value='<?php echo esc_attr($post->ID); ?>'>
                                                                            <?php echo esc_html($post->post_title); ?>
                                                                        </option>
                                                                <?php endif; ?>
                                                            </select>
                                                        <?php endif; ?>
                                                    <input 
                                                        class="taxopress-taxonomy-search existing-term-item" 
                                                        type="search" 
                                                        value="" 
                                                        placeholder="<?php echo esc_html__('Search Terms...', 'simple-tags'); ?>"
                                                        style=""
                                                        onkeydown="return event.key != 'Enter';" />


                                                        <select class="taxopress-autoterms-options <?php echo esc_attr($auto_term_class); ?>"
                                                        style="display: none;">
                                                                <?php foreach ($auto_term_options as $option_name => $option_label): ?>
                                                                    <option value='<?php echo esc_attr($option_name); ?>'>
                                                                            <?php echo esc_html($option_label); ?>
                                                                        </option> 
                                                                    <?php
                                                                endforeach; ?>
                                                        </select>
                                                        
                                                    <input 
                                                        class="taxopress-taxonomy-term-input create-term-item" 
                                                        type="text" 
                                                        value="" 
                                                        placeholder="<?php echo esc_html__('Create Term', 'simple-tags'); ?>"
                                                        style="display: none; "
                                                        onkeydown="return event.key != 'Enter';" />
                                                    <select class="taxopress-ai-fetch-create-taxonomy create-term-item" style="display: none;">
                                                            <?php foreach ($permitted_post_type_taxonomies as $tax_key => $tax_object):
                                                            
                                                            if (!in_array($tax_key, ['post_format']) && (!empty($tax_object->show_ui) || !empty($support_private_taxonomy))) {
                                                                $rest_api_base = !empty($tax_object->rest_base) ? $tax_object->rest_base : $tax_key;
                                                                $hierarchical = !empty($tax_object->hierarchical) ? (int) $tax_object->hierarchical : 0;
                                                                ?>
                                                                    <option value='<?php echo esc_attr($tax_key); ?>'
                                                                    data-rest_base='<?php echo esc_attr($rest_api_base); ?>'
                                                                    data-hierarchical='<?php echo esc_attr($hierarchical); ?>'
                                                                    <?php selected($tax_key, $default_taxonomy); ?>>
                                                                        <?php echo esc_html($tax_object->labels->name. ' ('.$tax_object->name.')'); ?>
                                                                    </option>
                                                                <?php }
                                                            endforeach; ?>
                                                    </select>
                                                    <button class="button button-secondary taxopress-ai-create-button create-term-item" style="display: none;">
                                                        <div class="spinner"></div>
                                                        <span class="btn-text"><?php echo esc_html__('Create Term', 'simple-tags'); ?></span>
                                                    </button>

                                                    <select class="taxopress-ai-fetch-taxonomy-select">
                                                            <?php foreach ($post_type_taxonomies as $tax_key => $tax_object):
                                                            
                                                            if (!in_array($tax_key, ['post_format']) && (!empty($tax_object->show_ui) || !empty($support_private_taxonomy))) {
                                                                $rest_api_base = !empty($tax_object->rest_base) ? $tax_object->rest_base : $tax_key;
                                                                $hierarchical = !empty($tax_object->hierarchical) ? (int) $tax_object->hierarchical : 0;
                                                                ?>
                                                                    <option value='<?php echo esc_attr($tax_key); ?>'
                                                                    data-rest_base='<?php echo esc_attr($rest_api_base); ?>'
                                                                    data-hierarchical='<?php echo esc_attr($hierarchical); ?>'
                                                                    <?php selected($tax_key, $default_taxonomy); ?>>
                                                                        <?php echo esc_html($tax_object->labels->name. ' ('.$tax_object->name.')'); ?>
                                                                    </option>
                                                                <?php }
                                                            endforeach; ?>
                                                    </select>
                                                    <select id="existing_terms_orderby"
                                                        class="existing-term-item" style="">
                                                        <?php foreach (TaxoPressAiUtilities::get_existing_terms_orderby() as $key => $label): ?>
                                                            <option value='<?php echo esc_attr($key); ?>'
                                                            <?php selected($key, $existing_terms_orderby); ?>>
                                                                <?php echo esc_html($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <select id="existing_terms_order"
                                                        class="existing-term-item" style="">
                                                        <?php foreach (TaxoPressAiUtilities::get_existing_terms_order() as $key => $label): ?>
                                                            <option value='<?php echo esc_attr($key); ?>'
                                                            <?php selected($key, $existing_terms_order); ?>>
                                                                <?php echo esc_html($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                    <input 
                                                        id="existing_terms_maximum_terms"
                                                        step="1" min="0"
                                                        class="existing-term-item" 
                                                        type="number" 
                                                        value="<?php echo esc_attr($existing_terms_maximum_terms); ?>" 
                                                        placeholder="<?php echo esc_attr__('Count', 'simple-tags'); ?>"
                                                        style="min-width: unset;width: 80px;"
                                                        onkeydown="return event.key != 'Enter';" />
                                                    <button class="button button-secondary taxopress-ai-fetch-button">
                                                        <div class="spinner"></div>
                                                        <span class="btn-text"><?php echo esc_html($button_label); ?></span>
                                                    </button>
                                                </div>
                                                <div class="taxopress-ai-fetch-result <?php echo esc_attr($key); ?>">
                                                <?php do_action('load_taxopress_ai_term_results', $result_request_args); ?>
                                                </div>
                                                <?php if (empty($permitted_post_type_taxonomies)) {
                                                    
                                                    echo '<div class="auto-terms-error-red create-term-item" style="display: none;" style="padding: 15px;"><p>';
                                                    echo esc_html__('You do not have the required capabilities to manage any of this post attached taxonomies.', 'simple-tags');
                                                    echo '</p></div>';
                                                }
                                                ?>
                                                <div class="taxopress-ai-fetch-result-msg <?php echo esc_attr($key); ?>"></div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <?php
                                $content_index++;
                            }
                            ?>
                        </div>
                        <div class="clear"></div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php
        }

    }// end TaxoPress_AI_Module
}// end if exists check