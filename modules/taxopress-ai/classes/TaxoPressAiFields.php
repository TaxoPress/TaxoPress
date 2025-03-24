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

            $fields_tabs['existing_terms'] = [
                'label' => esc_html__('Show All Existing Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Existing Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to browse all the terms in a taxonomy.', 'simple-tags'),
            ];

            $fields_tabs['post_terms'] = [
                'label' => esc_html__('Manage Post Terms', 'simple-tags'),
                'button_label'   => esc_html__('View Current Post Terms', 'simple-tags'),
                'description'  => esc_html__('This feature allows you to manage all the terms that are currently attached to a post.', 'simple-tags'),
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

    }
}
