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

            $fields_tabs['suggest_local_terms'] = [
                'label' => esc_html__('Suggest Existing Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Suggested Terms', 'simple-tags'),
                'description'  => esc_html__('This feature can scan your posts and suggest relevant terms.', 'simple-tags'),
            ];

            $fields_tabs['existing_terms'] = [
                'label' => esc_html__('Show All Existing Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Existing Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to browse all the terms in a taxonomy.', 'simple-tags'),
            ];


            $fields_tabs['open_ai'] = [
                'label' => esc_html__('OpenAI', 'simple-tags'),
                'button_label'   => esc_html__('View Terms', 'simple-tags'),
                'description'  => sprintf(esc_html__('OpenAI is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-openai/">', '</a>'),
            ];

            $fields_tabs['ibm_watson'] = [
                'label' => esc_html__('IBM Watson', 'simple-tags'),
                'button_label'   => esc_html__('View Terms', 'simple-tags'),
                'description'  => sprintf(esc_html__('IBM Watson is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-ibm/">', '</a>'),
            ];

            $fields_tabs['dandelion'] = [
                'label' => esc_html__('Dandelion', 'simple-tags'),
                'button_label'   => esc_html__('View Terms', 'simple-tags'),
                'description'  => sprintf(esc_html__('Dandelion is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-dandelion/">', '</a>'),
            ];

            $fields_tabs['open_calais'] = [
                'label' => esc_html__('LSEG / Refinitiv', 'simple-tags'),
                'button_label'   => esc_html__('View Terms', 'simple-tags'),
                'description'  => sprintf(esc_html__('LSEG / Refinitiv is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-opencalais/">', '</a>'),
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

            //add suggest local terms fields
            $fields['suggest_local_terms_orderby'] = [
                'label' => esc_html__('Method for choosing terms', 'simple-tags'),
                'type' => 'select',
                'default_value' => 'count',
                'classes' => 'taxopress-ai-select2',
                'options' => TaxoPressAiUtilities::get_existing_terms_orderby(),
                'tab' => 'suggest_local_terms',
            ];
            $fields['suggest_local_terms_order'] = [
                'label' => esc_html__('Ordering for choosing terms', 'simple-tags'),
                'type' => 'select',
                'default_value' => 'desc',
                'classes' => 'taxopress-ai-select2',
                'options' => TaxoPressAiUtilities::get_existing_terms_order(),
                'tab' => 'suggest_local_terms',
            ];
            $fields['suggest_local_terms_show_post_count'] = [
            'label' => esc_html__('Show Term Post Count', 'simple-tags'),
            'description' => esc_html__('This will show the number of posts attached to the terms.', 'simple-tags'),
            'type' => 'checkbox',
            'default_value' => 0,
            'tab' => 'suggest_local_terms',
            ];

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
