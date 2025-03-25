<?php
if (!class_exists('TaxoPressAiAjax')) {
    class TaxoPressAiAjax
    {
        /**
         * Handle an ajax request to search post
         */
        public static function handle_ai_post_search()
        {
            header('Content-Type: application/javascript');

            if (
                empty($_GET['nonce'])
                || !wp_verify_nonce(sanitize_key($_GET['nonce']), 'taxopress-ai-post-search')
            ) {
                wp_send_json_error(null, 403);
            }

            if (!current_user_can('simple_tags')) {
                wp_send_json_error(null, 403);
            }

            $search = !empty($_GET['q']) ? sanitize_text_field($_GET['q']) : '';
            $post_type = !empty($_GET['post_type']) ? sanitize_text_field($_GET['post_type']) : 'any';

            $post_args = [
                'post_type' => $post_type,
                'post_status' => 'publish',
                'posts_per_page' => apply_filters('taxopress_filter_posts_search_result_limit', 20),
            ];

            if (!empty($search)) {
                $post_args['s'] = $search;
            }

            $posts = get_posts($post_args);
            $results = [];

            foreach ($posts as $post) {
                $results[] = [
                    'id' => $post->ID,
                    'text' => $post->post_title,
                ];
            }

            $response = [
                'results' => $results,
            ];
            echo wp_json_encode($response);
            exit;
        }

        /**
         * Handle AI preview ajax request.
         */
        public static function handle_taxopress_ai_preview_feature()
        {

            $response['status'] = 'success';
            $response['content'] = esc_html__('Request completed.', 'simple-tags');

            //do not process request if nonce validation failed
            if (
                empty($_POST['nonce'])
                || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'taxopress-ai-ajax-nonce')
            ) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Security error. Kindly reload this page and try again',
                    'simple-tags'
                );
            } elseif (!can_manage_taxopress_metabox()) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Permission error. You do not have permission to manage taxopress',
                    'simple-tags'
                );
            } else {
                $preview_ai = !empty($_POST['preview_ai']) ? sanitize_text_field($_POST['preview_ai']) : '';
                $current_tags = !empty($_POST['current_tags']) ? array_map('sanitize_text_field', $_POST['current_tags']) : [];
                $preview_taxonomy = !empty($_POST['preview_taxonomy']) ? sanitize_text_field($_POST['preview_taxonomy']) : '';
                $search_text = !empty($_POST['search_text']) ? sanitize_text_field($_POST['search_text']) : '';
                $selected_autoterms = !empty($_POST['selected_autoterms']) ? sanitize_text_field($_POST['selected_autoterms']) : '';
                $screen_source = !empty($_POST['screen_source']) ? sanitize_text_field($_POST['screen_source']) : 'st_autoterms';
                $preview_post = !empty($_POST['preview_post']) ? (int) $_POST['preview_post'] : 0;
                $preview_feature = 'data';
                $post_data = get_post($preview_post);
                $settings_data = TaxoPressAiUtilities::taxopress_get_ai_settings_data($post_data->post_type);
                $post_content = isset($_POST['post_content']) ? taxopress_sanitize_text_field($_POST['post_content']) : $post_data->post_content;
                $post_title = isset($_POST['post_title']) ? taxopress_sanitize_text_field($_POST['post_title']) : $post_data->post_title;
                $term_results = [];

                if ($screen_source == 'st_taxopress_ai') {
                    $post_content = $post_data->post_content;
                    $post_title = $post_data->post_title;
                }

                if ($preview_ai == 'suggest_local_terms') {
                    $preview_ai = 'autoterms';
                }

                if (!can_manage_taxopress_metabox_taxonomy($preview_taxonomy)) {
                    $response['status'] = 'error';
                    $response['content'] = sprintf(esc_html__('You do not have permission to manage this taxonomy. Enable Metabox Access Taxonomies for this role in %1sTaxoPress Settings%2s.', 'simple-tags'), '<a target="_blank" href="'. admin_url('admin.php?page=st_options#metabox') .'">', '</a>');
                    wp_send_json($response);
                    exit;
                }
                

                if ($preview_ai == 'autoterms' && !empty($selected_autoterms)) {
                    $autoterm_data      = taxopress_get_autoterm_data();
                    $settings_data      = array_key_exists($selected_autoterms, $autoterm_data) ? $autoterm_data[$selected_autoterms] : [];
                    $autoterm_use_taxonomy      = !empty($settings_data['autoterm_use_taxonomy']);
                    $autoterm_use_open_ai       = !empty($settings_data['autoterm_use_open_ai']);
                    $autoterm_use_ibm_watson    = !empty($settings_data['autoterm_use_ibm_watson']);
                    $autoterm_use_dandelion     = !empty($settings_data['autoterm_use_dandelion']);
                    $autoterm_use_opencalais    = !empty($settings_data['autoterm_use_opencalais']);
                }

                /**
                 * Filter auto term content
                 *
                 * @param string $content Original content to be analyzed. It could include post title,
                 *  content and/excerpt based on autoterms settings
                 * @param integer $post_id This is the post id
                 * @param array $settings_data Autoterm settings
                 */
                if (!empty($post_content)) {
                    $post_content = apply_filters('taxopress_filter_autoterm_content', $post_content, $post_data->ID, $settings_data);
                } elseif (!empty($post_title)) {
                    $post_title = apply_filters('taxopress_filter_autoterm_content', $post_title, $post_data->ID, $settings_data);
                }

                // TODO: Save last selected settings

                $content = $post_content . ' ' . $post_title;
                $clean_content = TaxoPressAiUtilities::taxopress_clean_up_content($post_content, $post_title);
                $post_id = $post_data->ID;
                $preview_taxonomy_details = get_taxonomy($preview_taxonomy);
                $post_type_details = get_post_type_object($post_data->post_type);
                $args = [
                    'post_id' => $post_id,
                    'settings_data' => $settings_data,
                    'screen_source' => $screen_source,
                    'content' => $content,
                    'taxonomy' => $preview_taxonomy,
                    'clean_content' => $clean_content,
                    'preview_taxonomy' => $preview_taxonomy,
                    'preview_taxonomy_details' => $preview_taxonomy_details,
                    'post_type_details' => $post_type_details,
                    'preview_feature' => $preview_feature,
                    'current_tags' => $current_tags,
                    'selected_autoterms' => $selected_autoterms,
                    'content_source' => $preview_feature . '_post_content_title'

                ];

                if (!is_object($post_data) || (empty($post_content) && empty($post_title))) {
                    $response['status'] = 'error';
                    $response['content'] = esc_html__(
                        'Posts content and title is empty.',
                        'simple-tags'
                    );
                } elseif ($preview_ai == 'suggest_local_terms') {
                    $args['suggest_terms'] = true;
                    $args['show_counts'] = isset($settings_data['suggest_local_terms_show_post_count']) ? $settings_data['suggest_local_terms_show_post_count'] : 0;
                    $suggest_local_terms_results = self::get_existing_terms_results($args);
                    if (!empty($suggest_local_terms_results['results'])) {
                        $term_results = $suggest_local_terms_results['results'];
                    }
                    $response['status'] = $suggest_local_terms_results['status'];
                    $response['content'] = $suggest_local_terms_results['message'];
                } elseif ($preview_ai == 'existing_terms') {
                    $args['show_counts'] = isset($settings_data['existing_terms_show_post_count']) ? $settings_data['existing_terms_show_post_count'] : 0;
                    $args['search_text'] = $search_text;

                    if (isset($_POST['existing_terms_order'])) {
                        $args['existing_terms_order'] = sanitize_text_field($_POST['existing_terms_order']);
                    }
                    if (isset($_POST['existing_terms_orderby'])) {
                        $args['existing_terms_orderby'] = sanitize_text_field($_POST['existing_terms_orderby']);
                    }
                    if (isset($_POST['existing_terms_maximum_terms'])) {
                        $args['existing_terms_maximum_terms'] = (int)$_POST['existing_terms_maximum_terms'];
                    }
                    
                    $existing_terms_results = self::get_existing_terms_results($args);
                    if (!empty($existing_terms_results['results'])) {
                        $term_results = $existing_terms_results['results'];
                    }
                    $response['status'] = $existing_terms_results['status'];
                    $response['content'] = $existing_terms_results['message'];
                } elseif ($preview_ai == 'post_terms') {
                    $args['show_counts'] = isset($settings_data['post_terms_show_post_count']) ? $settings_data['post_terms_show_post_count'] : 0;
                    $post_terms_results = wp_get_post_terms($post_id, $preview_taxonomy, ['fields' => 'names']);

                    if (!empty($current_tags)) {
                        $current_post_tags = get_terms(
                            array(
                                'taxonomy' => $preview_taxonomy,
                                'include' => $current_tags,
                                'fields' => 'names',
                                'hide_empty' => false
                            )
                        );

                        $post_terms_results = array_unique(array_filter(array_merge($post_terms_results, $current_post_tags)));
                        $post_terms_results = array_values($post_terms_results);
                    }
                    if (!empty($post_terms_results)) {
                        $term_results = $post_terms_results;
                        $response['status'] = 'success';
                        $response['content'] = esc_html__('Term Results', 'simple-tags');
                    } else {
                        $response['status'] = 'error';
                        $response['content'] = esc_html__('No results found for this post with this taxonomy.', 'simple-tags');
                    }
                } elseif ($preview_ai == 'autoterms') {
                    if (empty($selected_autoterms) || empty($settings_data)) {
                        $response['status'] = 'error';
                        $response['content'] = esc_html__('Invalid Auto Term ID. Please save the settings before using preview.', 'simple-tags');
                    } elseif ( !$autoterm_use_taxonomy && !$autoterm_use_open_ai && !$autoterm_use_ibm_watson && !$autoterm_use_dandelion && !$autoterm_use_opencalais
                    ) {
                        $response['status'] = 'error';
                        $response['content'] = esc_html__('You must enable at least one AI integration source to use auto term preview.', 'simple-tags');
                    } elseif (empty($settings_data['autoterm_for_metaboxes'])) {
                        $response['status'] = 'error';
                        $response['content'] = esc_html__('This Auto Terms option is not enabled for metaboxes. Please enable it in the Auto Terms settings.', 'simple-tags');
                    } else {
                        $request_args  = $args;
                        $request_args['settings_data'] = $settings_data;
                        $request_args['return_tags'] = true;
                        $terms_found = false;

                        $metabox_display_option = SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post_data->post_type . '_metabox_display_option');
            
                        if (empty($metabox_display_option) || $screen_source == 'st_autoterms') {
                            $metabox_display_option = 'default';
                        }

                        $term_results = '<div class="preview-action-title taxopress-autoterm-element '. esc_attr($metabox_display_option) .'"><p class="description">';
                        $term_results .= sprintf(esc_html__('Click %1s to select or deselect them from this %2s.', 'simple-tags'), esc_html($preview_taxonomy_details->labels->name), esc_html($post_type_details->labels->singular_name));
                        $term_results .= '</p></div>';
                        // autoterm_use_taxonomy
                        if ($autoterm_use_taxonomy) {
                            $request_args['suggest_terms'] = true;
                            $request_args['show_counts'] = isset($settings_data['suggest_local_terms_show_post_count']) ? $settings_data['suggest_local_terms_show_post_count'] : 0;

                            $suggest_local_terms_results = TaxoPressAiAjax::get_existing_terms_results($request_args);
                
                            if (!empty($suggest_local_terms_results['results'])) {
                                $terms_found = true;
                                $request_args['results']        = $suggest_local_terms_results['results'];
                                $request_args['legend_title']   = esc_html__('Suggest Existing Terms', 'simple-tags');
                                $term_results               .= TaxoPressAiUtilities::get_term_fieldset_html($request_args);
                            } else {
                                $term_results .= '<fieldset class="previewed-tag-fieldset"><legend> ' . esc_html__('Suggest Existing Terms', 'simple-tags') . ' </legend><div class="previewed-tag-content"><div class="taxopress-response-css red"><p>'. $suggest_local_terms_results['message'] .'</p></div></div></fieldset>';
                            }
                        }
                
                        if (taxopress_is_pro_version()) {
                            // autoterm_use_open_ai
                            if ($autoterm_use_open_ai) {
                                ##https://platform.openai.com/docs/guides/gpt
                                ##https://platform.openai.com/docs/api-reference/chat/create
                                ##https://platform.openai.com/docs/models/model-endpoint-compatibility

                                $request_args['show_counts'] = isset($settings_data['open_ai_show_post_count']) ? $settings_data['open_ai_show_post_count'] : 0;
                                $open_ai_results = TaxoPressAiApi::get_open_ai_results($request_args);
                                if (!empty($open_ai_results['results'])) {
                                    $terms_found = true;
                                    $request_args['results']        = $open_ai_results['results'];
                                    $request_args['legend_title']   = esc_html__('OpenAI', 'simple-tags');
                                    $term_results               .= TaxoPressAiUtilities::get_term_fieldset_html($request_args);
                                } else {
                                    $term_results .= '<fieldset class="previewed-tag-fieldset"><legend> ' . esc_html__('OpenAI', 'simple-tags') . ' </legend><div class="previewed-tag-content"><div class="taxopress-response-css red"><p>'. $open_ai_results['message'] .'</p></div></div></fieldset>';
                                }
                            }
                    
                            // autoterm_use_ibm_watson
                            if ($autoterm_use_ibm_watson) {
                                ##https://cloud.ibm.com/apidocs/natural-language-understanding
                                $request_args['show_counts'] = isset($settings_data['ibm_watson_show_post_count']) ? $settings_data['ibm_watson_show_post_count'] : 0;
                                $ibm_watson_results = TaxoPressAiApi::get_ibm_watson_results($request_args);
                                if (!empty($ibm_watson_results['results'])) {
                                    $terms_found = true;
                                    $request_args['results']        = $ibm_watson_results['results'];
                                    $request_args['legend_title']   = esc_html__('IBM Watson', 'simple-tags');
                                    $term_results               .= TaxoPressAiUtilities::get_term_fieldset_html($request_args);
                                } else {
                                    $term_results .= '<fieldset class="previewed-tag-fieldset"><legend> ' . esc_html__('IBM Watson', 'simple-tags') . ' </legend><div class="previewed-tag-content"><div class="taxopress-response-css red"><p>'. $ibm_watson_results['message'] .'</p></div></div></fieldset>';
                                }            
                            }
                    
                            // autoterm_use_dandelion
                            if ($autoterm_use_dandelion) {
                                ##https://dandelion.eu/docs/api/datatxt/nex/v1/#response
                                $request_args['show_counts'] = isset($settings_data['dandelion_show_post_count']) ? $settings_data['dandelion_show_post_count'] : 0;
                                $dandelion_results = TaxoPressAiApi::get_dandelion_results($request_args);
                                if (!empty($dandelion_results['results'])) {
                                    $terms_found = true;
                                    $request_args['results']        = $dandelion_results['results'];
                                    $request_args['legend_title']   = esc_html__('Dandelion', 'simple-tags');
                                    $term_results               .= TaxoPressAiUtilities::get_term_fieldset_html($request_args);
                                } else {
                                    $term_results .= '<fieldset class="previewed-tag-fieldset"><legend> ' . esc_html__('Dandelion', 'simple-tags') . ' </legend><div class="previewed-tag-content"><div class="taxopress-response-css red"><p>'. $dandelion_results['message'] .'</p></div></div></fieldset>';
                                } 
                            }
                    
                            // autoterm_use_opencalais
                            if ($autoterm_use_opencalais) {
                                ## https://developers.lseg.com/en/api-catalog/open-perm-id/intelligent-tagging-restful-api/documentation
                                $request_args['show_counts'] = isset($settings_data['open_calais_show_post_count']) ? $settings_data['open_calais_show_post_count'] : 0;
                                $open_calais_results = TaxoPressAiApi::get_open_calais_results($request_args);
                                if (!empty($open_calais_results['results'])) {
                                    $terms_found = true;
                                    $request_args['results']        = $open_calais_results['results'];
                                    $request_args['legend_title']   = esc_html__('LSEG / Refinitiv', 'simple-tags');
                                    $term_results               .= TaxoPressAiUtilities::get_term_fieldset_html($request_args);
                                } else {
                                    $term_results .= '<fieldset class="previewed-tag-fieldset"><legend> ' . esc_html__('LSEG / Refinitiv', 'simple-tags') . ' </legend><div class="previewed-tag-content"><div class="taxopress-response-css red"><p>'. $open_calais_results['message'] .'</p></div></div></fieldset>';
                                } 
                            }
                        }

                        if ($terms_found) {
                            $term_results .= '<div class="preview-action-btn-wrap">';
                            $term_results .= '<button class="button button-primary taxopress-ai-addtag-button">
                            <div class="spinner"></div>
                            '. sprintf(esc_html__('Update %1s on this %2s', 'simple-tags'), esc_html($preview_taxonomy_details->labels->name), esc_html($post_type_details->labels->singular_name)) .' 
                            </button>';
                            $term_results .= '</div>';
                        }

                    }
                }

                if (!empty($term_results)) {
                    if (is_array($term_results)) {
                        $term_results = array_unique(array_filter($term_results));
                        $addded_term_results = [];
                        foreach ($term_results as $term_result) {
                            if (!in_array($preview_ai, ['post_terms', 'existing_terms'])) {
                                $term_details = get_term_by('name', $term_result, $preview_taxonomy);
                                if ($term_details) {
                                    $primary_term = $term_result;
                                    $term_id = $term_details->term_id;
                                    $add_terms = [];
                                    $add_terms[$primary_term] = $term_id;
                        
                                    // add term synonyms
                                    $term_synonyms = taxopress_get_term_synonyms($term_id);
                                    if (!empty($term_synonyms)) {
                                        foreach ($term_synonyms as $term_synonym) {
                                            $add_terms[$term_synonym] = $term_id;
                                        }
                                    }
                
                                    // add linked term
                                    $add_terms = taxopress_add_linked_term_options($add_terms, $term_id, $preview_taxonomy);
                                    
                                    // add all of the linked and synonmy terms to the list
                                    foreach ($add_terms as $add_name => $add_term_id) {
                                        if (is_string($add_name) && ! empty($add_name) && !in_array($add_name, $addded_term_results)) {
                                            $addded_term_results[] = $add_name;
                                        }
                                    }
                                } else {
                                    $addded_term_results[] = $term_result;
                                }
                               
                            } else {
                                $addded_term_results[] = $term_result;
                            }
                        }
                        $addded_term_results = array_unique($addded_term_results);
                        $legend_title = '<a href="' . get_edit_post_link($post_id) . '" target="blank">' . $post_data->post_title . ' (' . esc_html__('Edit', 'simple-tags') . ')</a>';
                        $response_content = TaxoPressAiUtilities::format_taxonomy_term_results($addded_term_results, $preview_taxonomy, $post_id, $legend_title, $args['show_counts'], $current_tags, $args);

                    } else {
                        $response_content = $term_results;
                    }
                    $response['content'] = $response_content;
                }
            }

            wp_send_json($response);
            exit;
        }

        /**
         * Get existing terms
         *
         * @param  array $args
         * @return array
         */
        public static function get_existing_terms_results($args)
        {
            $return['status'] = 'error';
            $return['message'] = esc_html__('Existing Terms not found for the selected post type and taxonomies', 'simple-tags');
            $return['results'] = '';

            $settings_data = $args['settings_data'];
            $content = $args['content'];
            $suggest_terms = !empty($args['suggest_terms']);
            $return_tags = !empty($args['return_tags']);
            $current_tags = !empty($args['current_tags']) ? (array) $args['current_tags'] : [];
            $search_text = !empty($args['search_text']) ? $args['search_text'] : '';
            $post_id = !empty($args['post_id']) ? (int) $args['post_id'] : 0;
            $post_type = get_post_type($post_id);
            $existing_terms_taxonomy = isset($args['preview_taxonomy']) ? $args['preview_taxonomy'] : ['post_tag'];

            if ($suggest_terms) {
                $existing_terms_maximum_terms = 0;
                $existing_terms_orderby = isset($settings_data['suggest_local_terms_orderby']) ? $settings_data['suggest_local_terms_orderby'] : 'count';
                $existing_terms_order = isset($settings_data['suggest_local_terms_order']) ? $settings_data['suggest_local_terms_order'] : 'desc';
                $existing_terms_show_post_count = isset($settings_data['suggest_local_terms_show_post_count']) ? $settings_data['suggest_local_terms_show_post_count'] : 0;
            } else {

                if (isset($args['existing_terms_order'])) {
                    $existing_terms_order = $args['existing_terms_order'];
                } else {
                    $existing_terms_order = isset($settings_data['existing_terms_order']) ? $settings_data['existing_terms_order'] : 'desc';
                }
                if (isset($args['existing_terms_orderby'])) {
                    $existing_terms_orderby = $args['existing_terms_orderby'];
                } else {
                    $existing_terms_orderby = isset($settings_data['existing_terms_orderby']) ? $settings_data['existing_terms_orderby'] : 'count';
                }
                if (isset($args['existing_terms_maximum_terms'])) {
                    $existing_terms_maximum_terms = (int)$args['existing_terms_maximum_terms'];
                } else {
                    $existing_terms_maximum_terms = isset($settings_data['existing_terms_maximum_terms']) ? $settings_data['existing_terms_maximum_terms'] : 45;
                }
                $existing_terms_show_post_count = isset($settings_data['existing_terms_show_post_count']) ? $settings_data['existing_terms_show_post_count'] : 0;
            }

            if (!empty($args['show_counts'])) {
                $existing_terms_show_post_count = 1;
            }

            if ($existing_terms_maximum_terms > 0) {
                $limit = 'LIMIT 0, ' . $existing_terms_maximum_terms;
            } else {
                $limit = '';
            }

            if (empty($existing_terms_taxonomy)) {
                $return['status'] = 'error';
                $return['message'] = esc_html__(
                    'Existing Terms taxonomy is required. Kindly select taxonomies and save the settings before running preview.',
                    'simple-tags'
                );
            } else {
                $post_type_taxonomies = get_object_taxonomies($post_type);

                $supported_tax = false;
                $response_content = '';
                foreach ([$existing_terms_taxonomy] as $existing_tax) {
                    if (in_array($existing_tax, $post_type_taxonomies)) {
                        $supported_tax = true;
                        $taxonomy_details = get_taxonomy($existing_tax);
                        
                        $terms = SimpleTags_Admin::getTermsForAjax($existing_tax, $search_text, $existing_terms_orderby, $existing_terms_order, $limit);
                        // make sure post terms are always included
                        if (!$suggest_terms) {
                            $post_terms = wp_get_post_terms($post_id, $existing_tax);
                            if (!empty($post_terms)) {
                                // Transform post_terms to match terms structure
                                $structured_post_terms = array_map(function($term) {
                                    return (object) [
                                        'name' => $term->name,
                                        'term_id' => $term->term_id,
                                        'taxonomy' => $term->taxonomy
                                    ];
                                }, $post_terms);
                                // add structured post terms
                                $terms = array_merge($structured_post_terms, $terms);
                            }
                        }
                        if (!empty($terms)) {

                            if ($suggest_terms) {
                                $term_results = [];
                                foreach ($terms as $term ) {
                                    $term_id = $term->term_id;
                                    $term = stripslashes( $term->name );
                                    $add_terms = [];
                                    $add_terms[$term] = $term_id;
                                    $primary_term = $term;
                                    $term_check_names = [];
                                    // add term synonyms
                                    $term_synonyms = taxopress_get_term_synonyms($term_id);
                                    if (!empty($term_synonyms)) {
                                        foreach ($term_synonyms as $term_synonym) {
                                            $term_check_names[$term_synonym] = $primary_term;
                                            $add_terms[$term_synonym] = $term_id;
                                        }
                                    }
                        
                                    // add linked term
                                    $add_terms = taxopress_add_linked_term_options($add_terms, $term_id, $existing_tax);
                                    foreach ($add_terms as $add_name => $add_term_id) {
                                        $new_term = (isset($term_check_names[$add_name])) ? $term_check_names[$add_name] : $add_name;

                                        if (is_string($new_term) && ! empty($new_term) && stristr($content, $add_name) && !in_array($new_term, $term_results)) {
                                            $term_results[] = $new_term;
                                        }
                                    }
                                }
                            } else {
                                $term_results = array_unique(array_column((array) $terms, 'name'));
                            }


                            $taxonomy_list_page = admin_url('edit-tags.php');
                            $taxonomy_list_page = add_query_arg(
                                array(
                                    'taxonomy' => $existing_tax,
                                    'post_type' => $post_type,
                                ),
                                $taxonomy_list_page
                            );

                            $legend_title = '<a href="' . esc_url($taxonomy_list_page) . '" target="blank">' . $taxonomy_details->labels->name . '</a>';
                            if ($return_tags) {
                                $response_content = $term_results;
                            } else {
                                $response_content = TaxoPressAiUtilities::format_taxonomy_term_results($term_results, $existing_tax, $post_id, $legend_title, $existing_terms_show_post_count, $current_tags, $args);
                            }
                        }

                    }
                }

                if (!empty($response_content)) {
                    $return['status'] = 'success';
                    $return['results'] = $response_content;
                    $return['message'] = esc_html__(
                        'Result from ajax request.',
                        'simple-tags'
                    );
                } elseif (!$supported_tax) {
                    $return['status'] = 'error';
                    $return['message'] = esc_html__(
                        'The selected taxonomy is not associated with the selected post type.',
                        'simple-tags'
                    );
                } else {
                    $return['status'] = 'error';
                    $return['message'] = esc_html__(
                        'No results found for this taxonomy.',
                        'simple-tags'
                    );
                }

            }

            return $return;
        }

        /**
         * Handle AI post terms update.
         */
        public static function handle_taxopress_ai_post_term()
        {

            $response['status'] = 'success';
            $response['content'] = esc_html__('Request completed.', 'simple-tags');

            //do not process request if nonce validation failed
            if (
                empty($_POST['nonce'])
                || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'taxopress-ai-ajax-nonce')
            ) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Security error. Kindly reload this page and try again',
                    'simple-tags'
                );
            } elseif (!can_manage_taxopress_metabox()) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Permission error. You do not have permission to manage taxopress',
                    'simple-tags'
                );
            } else {
                $taxonomy = !empty($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
                $post_type_label = !empty($_POST['post_type_label']) ? sanitize_text_field($_POST['post_type_label']) : esc_html__('Post', 'simple-tags');
                $post_id = !empty($_POST['post_id']) ? (int) $_POST['post_id'] : 0;
                $added_tags = !empty($_POST['added_tags']) ? map_deep($_POST['added_tags'], 'sanitize_text_field') : [];
                $removed_tags = !empty($_POST['removed_tags']) ? map_deep($_POST['removed_tags'], 'sanitize_text_field') : [];

                if (!can_manage_taxopress_metabox_taxonomy($taxonomy)) {
                    $response['status'] = 'error';
                    $response['content'] = sprintf(esc_html__('You do not have permission to manage this taxonomy. Enable Metabox Access Taxonomies for this role in %1sTaxoPress Settings%2s.', 'simple-tags'), '<a target="_blank" href="'. admin_url('admin.php?page=st_options#metabox') .'">', '</a>');
                    wp_send_json($response);
                    exit;
                }

                if (!empty($post_id)) {
                    $post_type = get_post_type($post_id);
                    $post_type_details  = get_post_type_object($post_type);
                    $post_type_label = $post_type_details->labels->singular_name;
                }

                if (empty($added_tags) && empty($removed_tags)) {
                    $response['status'] = 'error';
                    $response['content'] = sprintf(esc_html__('Click Term to select or deselect from this %1s', 'simple-tags'), esc_html($post_type_label));
                } elseif (empty($taxonomy) || empty($post_id)) {
                    $response['status'] = 'error';
                    $response['content'] = esc_html__('Both Taxonomy and Post are required.', 'simple-tags');
                } else {
                    $post_terms = wp_get_post_terms($post_id, $taxonomy, ['fields' => 'ids']);

                    $removed_terms_name = [];
                    $added_terms_name = [];
                    $removed_terms_id = [];
                    $added_terms_id = [];
                    // Remove de-selected terms
                    if (!empty($removed_tags)) {
                        foreach ($removed_tags as $removed_tag) {
                            $term_id = (int) $removed_tag['term_id'];
                            if (in_array($term_id, $post_terms)) {
                                $remove = wp_remove_object_terms($post_id, $term_id, $taxonomy);
                                if ($remove) {
                                    $removed_terms_name[] = $removed_tag['name'];
                                    $removed_terms_id[] = $term_id;
                                    clean_term_cache($term_id, $taxonomy);
                                }
                            }
                        }
                        clean_object_term_cache($post_id, $taxonomy);
                    }
                    // Add selected terms
                    if (!empty($added_tags)) {
                        foreach ($added_tags as $added_tag) {
                            $term_id = (int) $added_tag['term_id'];
                            if ($term_id === 0) {
                                $term_id = wp_insert_term($added_tag['name'], $taxonomy)['term_id'];
                            }
                            if (!in_array($term_id, $post_terms)) {
                                $add = wp_set_object_terms($post_id, $term_id, $taxonomy, true);
                                if ($add) {
                                    $added_terms_name[] = $added_tag['name'];
                                    $added_terms_id[] = $term_id;
                                    clean_term_cache($term_id, $taxonomy);
                                }
                            }
                        }
                        clean_object_term_cache($post_id, $taxonomy);
                    }

                    $additional_message = ''; //sprintf(esc_html__('%1s updated.', 'simple-tags'), esc_html($post_type_label));
                    $response['status'] = 'success';
                    $response['removed_terms_id'] = $removed_terms_id;
                    $response['added_terms_id'] = $added_terms_id;
                    if (empty($added_terms_name) && empty($removed_terms_name)) {
                        $response['status'] = 'error';
                        $additional_message = esc_html__('No new terms were selected or deselected.', 'simple-tags');
                    }

                    if (!empty($added_terms_name)) {
                        $additional_message .= ' ' . sprintf(esc_html__('%1s terms added to this %2s.', 'simple-tags'), '<strong>' . join(', ', $added_terms_name) . '</strong>', esc_html($post_type_label));
                    }

                    if (!empty($removed_terms_name)) {
                        $additional_message .= ' ' . sprintf(esc_html__('%1s terms removed from this %2s.', 'simple-tags'), '<strong>' . join(', ', $removed_terms_name) . '</strong>', esc_html($post_type_label));
                    }

                    $response['content'] = $additional_message;
                }
            }

            wp_send_json($response);
            exit;
        }

        /**
         * Handle AI new term
         */
        public static function handle_taxopress_ai_new_term()
        {

            $response['status'] = 'error';
            $response['content'] = esc_html__('An error occured.', 'simple-tags');

            //do not process request if nonce validation failed
            if (
                empty($_POST['nonce'])
                || !wp_verify_nonce(sanitize_key($_POST['nonce']), 'taxopress-ai-ajax-nonce')
            ) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Security error. Kindly reload this page and try again',
                    'simple-tags'
                );
            } elseif (!can_manage_taxopress_metabox()) {
                $response['status'] = 'error';
                $response['content'] = esc_html__(
                    'Permission error. You do not have permission to manage taxopress',
                    'simple-tags'
                );
            } else {
                $taxonomy = !empty($_POST['taxonomy']) ? sanitize_text_field($_POST['taxonomy']) : '';
                $term_name = !empty($_POST['term_name']) ? sanitize_text_field($_POST['term_name']) : '';
                $screen_source = !empty($_POST['screen_source']) ? sanitize_text_field($_POST['screen_source']) : '';
                $existing_terms = !empty($_POST['existing_terms']) ? map_deep($_POST['existing_terms'], 'sanitize_text_field') : [];
                $selected_terms = !empty($_POST['selected_terms']) ? map_deep($_POST['selected_terms'], 'intval') : [];
                $post_id = !empty($_POST['post_id']) ? intval($_POST['post_id']) : 0;

                if (!can_manage_taxopress_metabox_taxonomy($taxonomy)) {
                    $response['status'] = 'error';
                    $response['content'] = sprintf(esc_html__('You do not have permission to manage this taxonomy. Enable Metabox Access Taxonomies for this role in %1sTaxoPress Settings%2s.', 'simple-tags'), '<a target="_blank" href="'. admin_url('admin.php?page=st_options#metabox') .'">', '</a>');
                    wp_send_json($response);
                    exit;
                }

                $taxonomy_data = get_taxonomy( $taxonomy );
                $can_manage_term = false;
                if (in_array($taxonomy, ['category', 'post_tag']) && current_user_can('manage_categories')) {
                    $can_manage_term = true;
                } elseif (!empty($taxonomy_data->cap->edit_terms) && current_user_can($taxonomy_data->cap->edit_terms)) {
                    $can_manage_term = true;
                }

                if (!$can_manage_term) {
                    $response['status'] = 'error';
                    $response['content'] = esc_html__('You do not have capability to manage this taxonomy.', 'simple-tags');
                    wp_send_json($response);
                    exit;
                }

                $validated_term = apply_filters('taxopress_validate_term_before_insert', $term_name, $taxonomy);
                if (is_wp_error($validated_term)) {
                    $response['status'] = 'error';
                    $response['content'] = $validated_term->get_error_message();
                    wp_send_json($response);
                    exit;
                }


                $term_id   = 0;
                $term_data = false;
                $term_exits = 0;

                $result_term = term_exists($term_name, $taxonomy);
                if (empty($result_term)) {
                    $result_term = wp_insert_term(
                        $term_name,
                        $taxonomy
                    );

                    if (!is_wp_error($result_term)) {
                        $term_id = (int) $result_term['term_id'];
                    }
                } else {
                    $term_id = (int) $result_term['term_id'];
                    $term_exits = 1;
                }

                $term_html = '';
                if ($term_id > 0) {
                    $term = get_term($term_id);
                    $term_data = [
                        'term_id' => $term_id,
                        'name' => $term->name,
                        'term_exits' => $term_exits
                    ];
                    $existing_terms[] = $term->name;
                    $existing_terms = array_filter($existing_terms);
                    if (!empty($post_id)) {
                        $term_html = TaxoPressAiUtilities::format_taxonomy_term_results($existing_terms, $taxonomy, $post_id, '', false, $selected_terms, ['screen_source' => 'post.php']);

                        if ($screen_source == 'st_taxopress_ai') {
                            wp_set_object_terms($post_id, $term->slug, $taxonomy, true);
                            clean_term_cache($term_id, $taxonomy);
                        }
                    }
                }
                $current_term = $term_id;

                $response['status'] = 'success';
                $response['content'] = esc_html__('Request completed.', 'simple-tags');
                $response['term'] = $term_data;
                $response['term_html'] = $term_html;
                $response['current_term'] = $current_term;
            }
            wp_send_json($response);
            exit;
        }

        public static function taxopress_validate_term_before_insert($term, $taxonomy) {
            $post_id   = 0;
            $post_type = '';
        
            // Check both possible keys for post ID
            if (!empty($_POST['post_ID'])) {
                $post_id = intval($_POST['post_ID']);
            } elseif (!empty($_POST['post_id'])) {
                $post_id = intval($_POST['post_id']);
            }
        
            if ($post_id) {
                $post_type = get_post_type($post_id);
            }

            $post_type = $post_type ?: 'post';
        
            $option_name    = 'taxopress_ai_' . $post_type . '_exclusions';
            $excluded_chars = SimpleTags_Plugin::get_option_value($option_name);
        
            if (!empty($excluded_chars)) {
                $excluded_chars = array_filter(str_split(preg_replace('/\s+/', '', $excluded_chars)));
        
                if (!empty($excluded_chars)) {
                    $exact_chars = array_filter($excluded_chars, function ($char) use ($term) {
                        return strpos($term, $char) !== false;
                    });
        
                    if (!empty($exact_chars)) {
                        return new WP_Error(
                            'invalid_character',
                            sprintf(__('Terms cannot contain the following characters: %s', 'simple-tags'), implode(' ', $exact_chars))
                        );
                    }
                }
            }

            // Retrieve minimum and maximum term length settings
            $min_length = (int) SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post_type . '_minimum_term_length');
            $max_length = (int) SimpleTags_Plugin::get_option_value('taxopress_ai_' . $post_type . '_maximum_term_length');

            // Validate term length
            $term_length = mb_strlen($term);
            if ($min_length > 0 && $term_length < $min_length) {
                return new WP_Error(
                    'term_too_short',
                    sprintf(__('Terms must be at least %d characters long.', 'simple-tags'), $min_length)
                );
            }
            if ($max_length > 0 && $term_length > $max_length) {
                return new WP_Error(
                    'term_too_long',
                    sprintf(__('Terms cannot exceed %d characters.', 'simple-tags'), $max_length)
                );
            }
        
            return $term;
        }

    }
}