<?php
if (!class_exists('TaxoPressAiFields')) {
    class TaxoPressAiFields
    {

        /**
         * Get the fields tabs to be rendered on taxopress ai screen
         *
         * @return array
         */
        public static function get_fields_tabs()
        {
            $fields_tabs = [];

            $fields_tabs['post_terms'] = [
                'label' => esc_html__('Manage Post Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Current Post Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to manage all the terms that are currently attached to a post.', 'simple-tags'),
            ];

            $fields_tabs['existing_terms'] = [
                'label' => esc_html__('Show All Existing Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Existing Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to browse all the terms in a taxonomy.', 'simple-tags'),
            ];

            $fields_tabs['autoterms'] = [
                'label' => esc_html__('Auto Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to browse based on selected auto terms settings and sources.', 'simple-tags'),
            ];

            /**
             * Customize fields tabs presented on taxopress ai screen.
             *
             * @param array $fields_tabs Existing fields tabs to display.
             */
            $fields_tabs = apply_filters('taxopress_ai_tabs', $fields_tabs);

            return $fields_tabs;
        }

        /**
         * Get the fields to be rendered on taxopress ai screen
         *
         * @return array
         */
        public static function get_fields()
        {

            //add existing terms fields
            $fields['existing_terms_maximum_terms'] = [
                'label' => esc_html__('Maximum terms', 'simple-tags'),
                'description'  => esc_html__('Set (0) for no limit.', 'simple-tags'),
                'type' => 'number',
                'other_attr' => 'step="1" min="0"',
                'default_value' => 45,
                'tab' => 'existing_terms',
            ];
            $fields['existing_terms_orderby'] = [
                'label' => esc_html__('Method for choosing terms', 'simple-tags'),
                'type' => 'select',
                'default_value' => 'count',
                'classes' => 'taxopress-ai-select2',
                'options' => TaxoPressAiUtilities::get_existing_terms_orderby(),
                'tab' => 'existing_terms',
            ];
            $fields['existing_terms_order'] = [
                'label' => esc_html__('Ordering for choosing terms', 'simple-tags'),
                'type' => 'select',
                'default_value' => 'desc',
                'classes' => 'taxopress-ai-select2',
                'options' => TaxoPressAiUtilities::get_existing_terms_order(),
                'tab' => 'existing_terms',
            ];
            $fields['existing_terms_show_post_count'] = [
            'label' => esc_html__('Show Term Post Count', 'simple-tags'),
            'description' => esc_html__('This will show the number of posts attached to the terms.', 'simple-tags'),
            'type' => 'checkbox',
            'default_value' => 0,
            'tab' => 'existing_terms',
            ];

            //add post terms fields
            $fields['post_terms_show_post_count'] = [
                'label' => esc_html__('Show Term Post Count', 'simple-tags'),
                'description' => esc_html__('This will show the number of posts attached to the terms.', 'simple-tags'),
                'type' => 'checkbox',
                'default_value' => 0,
                'tab' => 'post_terms',
            ];

            //add auto terms fields
            $fields['metabox_auto_term'] = [
                'label' => esc_html__('Auto Terms setting', 'simple-tags'),
                'description' => esc_html__('Select an Auto Terms configuration to use when scanning content.', 'simple-tags'),
                'type' => 'select',
                'default_value' => 'desc',
                'classes' => 'taxopress-ai-select2',
                'options' => TaxoPressAiUtilities::get_auto_term_options(),
                'tab' => 'autoterms',
            ];

            /**
             * Customize fields presented on taxopress ai screen.
             *
             * @param array $fields Existing fields to display.
             */
            $fields = apply_filters('taxopress_ai_fields', $fields);

            return $fields;
        }

    }
}
