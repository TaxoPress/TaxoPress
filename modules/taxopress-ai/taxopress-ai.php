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

            add_filter('set-screen-option', [$this, 'set_screen'], 10, 3);
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
                    //$suggestterm_datas = taxopress_get_suggestterm_data();
                    $autoterm_datas = taxopress_get_autoterm_data();
                    $taxopress_ai_settings = [];

                    $dandelion_api_token = false;
                    $open_calais_api     = false;

                    /*if (!empty($suggestterm_datas) && is_array($suggestterm_datas)) {
                        foreach ($suggestterm_datas as $suggestterm_data) {
                            if (!$dandelion_api_token && !empty($suggestterm_data['terms_datatxt_access_token'])) {
                                $dandelion_api_token = $suggestterm_data['terms_datatxt_access_token'];
                            }
                            if (!$open_calais_api && !empty($suggestterm_data['terms_opencalais_key'])) {
                                $open_calais_api = $suggestterm_data['terms_opencalais_key'];
                            }

                        }
                    }*/

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
        public static function admin_enqueue_scripts()
        {

            if (isset($_GET['page']) && $_GET['page'] == self::PAGE_MENU_SLUG) {
                wp_enqueue_style(
                    'taxopress-ai-css',
                    plugins_url('', __FILE__) . '/assets/css/taxopress-ai.css',
                    [],
                    STAGS_VERSION,
                    'all'
                );

                wp_enqueue_style('taxopress-admin-select2');
                wp_enqueue_script('taxopress-admin-select2');

                wp_enqueue_script(
                    'taxopress-ai-js',
                    plugins_url('', __FILE__) . '/assets/js/taxopress-ai.js',
                    ['jquery', 'taxopress-admin-select2', 'wp-util'],
                    STAGS_VERSION
                );

                wp_localize_script(
                    'taxopress-ai-js',
                    'taxoPressAIRequestAction',
                    [
                        'requiredSuffix' => esc_html__('is required', 'simple-tags'),
                        'nonce' => wp_create_nonce('taxopress-ai-ajax-nonce'),
                        'aiGroups' => TaxoPressAiUtilities::get_taxopress_ai_groups()
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
                esc_html__('TaxoPress AI', 'simple-tags'),
                esc_html__('TaxoPress AI', 'simple-tags'),
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


            $fields_tabs = TaxoPressAiFields::get_fields_tabs();
            $fields = TaxoPressAiFields::get_fields();
            $settings_data = TaxoPressAiUtilities::taxopress_get_ai_settings_data();

            $active_tab = !empty($settings_data['active_tab']) ? $settings_data['active_tab'] : 'open_ai';
            $active_tab_label = '';
            ?>

            <div class="wrap st_wrap st-manage-taxonomies-page <?php echo esc_attr(self::PAGE_MENU_SLUG . '-wrap'); ?>">
                <h1>
                    <?php echo esc_html__('Manage TaxoPress AI integration', 'simple-tags'); ?>
                </h1>
                <div class="wp-clearfix"></div>
                <form method="post" action="">
                    <input type="hidden" name="taxopress_ai_integration[active_tab]" class="taxopress-active-subtab"
                        value="<?php echo esc_attr($active_tab); ?>" />
                    <?php wp_nonce_field('taxopress_ai_settings_nonce_action'); ?>
                    <div id="poststuff">
                        <div id="post-body" class="taxopress-section metabox-holder columns-2">
                            <div class="tp-ai-flex-item">
                                <div id="post-body-content" class="right-body-content" style="position: relative;">
                                    <div class="postbox-header">
                                        <h2 class="hndle ui-sortable-handle">
                                            <?php esc_html_e('AI Integration Settings', 'simple-tags'); ?>
                                        </h2>
                                    </div>
                                    <div class="main">
                                        <ul class="taxopress-tab ai-integration-tab">
                                            <?php
                                            foreach ($fields_tabs as $key => $args) {
                                                $selected_class = ($key === $active_tab) ? ' active' : '';

                                                if ($key === $active_tab) {
                                                    $active_tab_label = $args['label'];
                                                }
                                                ?>
                                                <li class="<?php echo esc_attr($key); ?>_tab <?php esc_attr_e($selected_class); ?>"
                                                    data-content="<?php echo esc_attr($key); ?>"
                                                    aria-current="<?php echo $active_tab === $key ? 'true' : 'false'; ?>">
                                                    <a href="#<?php echo esc_attr($key); ?>" class="<?php echo esc_attr($key); ?>_icon">
                                                        <span>
                                                            <?php esc_html_e($args['label']); ?>
                                                        </span>
                                                    </a>
                                                </li>
                                                <?php
                                            }
                                            ?>
                                        </ul>
                                        <div class="st-taxonomy-content taxopress-tab-content">
                                            <?php
                                            foreach ($fields_tabs as $key => $args) {
                                                $selected_class = ($key === $active_tab) ? ' active' : '';
                                                $current_tab_fields = array_filter($fields, function ($value) use ($key) {
                                                    return $value['tab'] === $key;
                                                });
                                                ?>
                                                <table class="form-table taxopress-table <?php echo esc_attr($key); ?>"
                                                    style="<?php echo ($key === $active_tab) ? '' : 'display:none;'; ?>">
                                                    <?php if (!empty($args['description'])): ?>
                                                        <tr>
                                                            <th class="api-desc-th" colspan="2">
                                                                <p class="description">
                                                                    <?php echo $args['description']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                                </p>
                                                            </th>
                                                        </tr>
                                                    <?php endif; ?>
                                                    <?php
                                                    foreach ($current_tab_fields as $field_key => $field_args) {
                                                        $field_args['key'] = $field_key;
                                                        if (isset($settings_data[$field_key])) {
                                                            $field_value = $settings_data[$field_key];
                                                        } elseif (isset($field_args['default_value'])) {
                                                            $field_value = $field_args['default_value'];
                                                        } else {
                                                            $field_value = '';
                                                        }
                                                        $field_args['value'] = $field_value;
                                                        self::get_rendered_field_partial($field_args);
                                                    }
                                                    ?>
                                                    <?php do_action('taxopress_ai_after_'. $key .'_fields', $current_tab_fields); ?>
                                                </table>
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>
                                <div class="tp-ai-submit-div">
                                    <input type="submit" class="button-primary taxopress-taxonomy-submit taxopress-ai-submit"
                                        name="taxopress_ai_submit"
                                        value="<?php echo esc_attr__('Save Settings', 'simple-tags'); ?>" />
                                </div>
                            </div>

                            <div id="postbox-container-1" class="postbox-container tp-ai-flex-item">
                                <div id="side-sortables" class="meta-box-sortables ui-sortable" style="">

                                    <!-- start preview sidebar -->
                                    <div id="submitdiv"
                                        class="postbox taxopress-ai-preview-sidebar <?php echo esc_attr($active_tab); ?>"
                                        data-ai-source="<?php echo esc_attr($active_tab); ?>"
                                        data-preview="<?php echo esc_attr__('Preview', 'simple-tags'); ?>">
                                        <div class="postbox-header">
                                            <h2
                                                class="hndle ui-sortable-handle <?php echo esc_attr($active_tab); ?>_icon preview-title">
                                                <?php printf(esc_html__('%1s Preview', 'simple-tags'), esc_html($active_tab_label)); ?>
                                            </h2>
                                        </div>
                                        <div class="inside">
                                            <div id="minor-publishing">
                                                <div class="sidebar-body-wrap">
                                                    <select class="preview-taxonomy-select taxopress-ai-select2"
                                                    style="width: 100%;">
                                                        <?php foreach (TaxoPressAiUtilities::get_taxonomies() as $key => $label): ?>
                                                            <option value='<?php echo esc_attr($key); ?>'
                                                            <?php selected($key, 'post_tag'); ?>>
                                                                <?php echo esc_html($label); ?>
                                                            </option>
                                                        <?php endforeach; ?>

                                                    </select>

                                                    <select class="preview-post-types-select taxopress-ai-select2"
                                                    style="width: 100%;">
                                                        <?php foreach (TaxoPressAiUtilities::get_post_types_options() as $post_type => $post_type_object): ?>
                                                            <option value='<?php echo esc_attr($post_type); ?>'>
                                                                <?php echo esc_html($post_type_object->labels->name); ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>

                                                    <select class="preview-post-select taxopress-ai-post-search"
                                                    style="width: 100%;"
                                                        data-placeholder="<?php echo esc_attr__('Select...', 'simple-tags'); ?>"
                                                        data-allow-clear="true"
                                                        data-nonce="<?php echo esc_attr(wp_create_nonce('taxopress-ai-post-search')); ?>">

                                                    </select>
                                                </div>
                                                <div class="sidebar-submit-wrap">
                                                    <div class="submit-action">
                                                        <button class="button button-secondary taxopress-ai-preview-button">
                                                            <div class="spinner"></div>
                                                            <?php echo esc_html__('Preview', 'simple-tags'); ?>
                                                        </button>
                                                    </div>

                                                </div>
                                                <div class="sidebar-response-wrap"></div>
                                                <?php foreach (TaxoPressAiUtilities::get_taxopress_ai_groups() as $ai_group) : ?>
                                                    <div class="sidebar-response-preview <?php echo esc_attr($ai_group); ?>"></div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- end preview sidebar -->

                                </div>
                            </div>

                        </div>
                        <br class="clear">
                    </div>
                </form>
                <div>
                    <div class="clear"></div>
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
    }
}