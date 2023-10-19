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
            ];

            $fields_tabs['suggest_local_terms'] = [
                'label' => esc_html__('Suggest Existing Terms', 'simple-tags'),
            ];
            
            $fields_tabs['existing_terms'] = [
                'label' => esc_html__('Show All Existing Terms', 'simple-tags'),
            ];


            $fields_tabs['open_ai'] = [
                'label' => esc_html__('Open AI', 'simple-tags'),
                'description'  => sprintf(esc_html__('Open AI is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-openai/">', '</a>'),
            ];

            $fields_tabs['ibm_watson'] = [
                'label' => esc_html__('IBM Watson', 'simple-tags'),
                'description'  => sprintf(esc_html__('IBM Watson is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-ibm/">', '</a>'),
            ];

            $fields_tabs['dandelion'] = [
                'label' => esc_html__('Dandelion', 'simple-tags'),
                'description'  => sprintf(esc_html__('Dandelion is an external service that can scan your content and suggest relevant terms. %1sClick here for details%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/register-dandelion/">', '</a>'),
            ];

            $fields_tabs['open_calais'] = [
                'label' => esc_html__('LSEG / Refinitiv', 'simple-tags'),
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

            //add dandelion fields
            $fields['dandelion_api_token'] = [
                'label' => esc_html__('Api Token', 'simple-tags'),
                'description'  => sprintf(esc_html__('You need an API key to use Dandelion integration. %1sClick here for documentation.%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/dandelion-api/">', '</a>'),
                'type' => 'text',
                'tab' => 'dandelion',
            ];

            $fields['dandelion_api_confidence_value'] = [
                'label' => esc_html__('Api Confidence Value', 'simple-tags'),
                'description' => esc_html__('Choose a value between 0 and 1. A high value such as 0.8 will provide a few, accurate suggestions. A low value such as 0.2 will produce more suggestions, but they may be less accurate.', 'simple-tags'),
                'other_attr' => 'step=".1" min="0" max="1"',
                'type' => 'number',
                'default_value' => '0.6',
                'tab' => 'dandelion',
            ];
            $fields['dandelion_show_post_count'] = [
            'label' => esc_html__('Show Term Post Count', 'simple-tags'),
            'description' => esc_html__('This will show number of posts attached to the terms if it exist for preview taxonomy.', 'simple-tags'),
            'type' => 'checkbox',
            'default_value' => 0,
            'tab' => 'dandelion',
            ];
            $fields['dandelion_cache_result'] = [
                'label' => esc_html__('Cache Results', 'simple-tags'),
                'description' => esc_html__('By cahing the results locally, new API request will not be made unless the post title or content changes thereby saving API usage.', 'simple-tags'),
                'type' => 'checkbox',
                'default_value' => 1,
                'tab' => 'dandelion',
            ];

            //add open calais fields
            $fields['open_calais_api_key'] = [
                'label' => esc_html__('Api Key', 'simple-tags'),
                'description'  => sprintf(esc_html__('You need an API key to use OpenCalais integration. %1sClick here for documentation.%2s.', 'simple-tags'), '<a target="blank" href="https://taxopress.com/docs/opencalais/">', '</a>'),
                'type' => 'text',
                'tab' => 'open_calais',
            ];
            $fields['open_calais_show_post_count'] = [
            'label' => esc_html__('Show Term Post Count', 'simple-tags'),
            'description' => esc_html__('This will show number of posts attached to the terms if it exist for preview taxonomy.', 'simple-tags'),
            'type' => 'checkbox',
            'default_value' => 0,
            'tab' => 'open_calais',
            ];
            $fields['open_calais_cache_result'] = [
                'label' => esc_html__('Cache Results', 'simple-tags'),
                'description' => esc_html__('By cahing the results locally, new API request will not be made unless the post title or content changes thereby saving API usage.', 'simple-tags'),
                'type' => 'checkbox',
                'default_value' => 1,
                'tab' => 'open_calais',
            ];

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
            'description' => esc_html__('This will show number of posts attached to the terms.', 'simple-tags'),
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
            'description' => esc_html__('This will show number of posts attached to the terms.', 'simple-tags'),
            'type' => 'checkbox',
            'default_value' => 0,
            'tab' => 'existing_terms',
            ];

            //add post terms fields
            $fields['post_terms_show_post_count'] = [
                'label' => esc_html__('Show Term Post Count', 'simple-tags'),
                'description' => esc_html__('This will show number of posts attached to the terms.', 'simple-tags'),
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