<?php

class SimpleTags_Client_Autoterms
{
	/**
	 * Constructor
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public function __construct()
	{
		if (1 === (int) SimpleTags_Plugin::get_option_value('active_auto_terms')) {
			add_action('save_post', array(__CLASS__, 'save_post'), 12, 2);
			add_action('post_syndicated_item', array(__CLASS__, 'save_post'), 12, 2);
		}
	}

	/**
	 * Check post/page content for auto terms
	 *
	 * @param integer $post_id
	 * @param object $object
	 *
	 * @return boolean
	 */
	public static function save_post($post_id = null, $object = null)
	{
		// Get options
		$options = get_option(STAGS_OPTIONS_NAME_AUTO);

		// user preference for this post ?
		$meta_value = isset($_POST['exclude_autotags']) ? sanitize_text_field($_POST['exclude_autotags']) : false;
		if ($meta_value) {
			return false;
		}

		if (!is_object($object)) {
			return;
		}

		if (!isset($object->post_type)) {
			return;
		}

		// Loop option for find if autoterms is actived on any taxonomy and post type
		$current_post_type = $object->post_type;
		$current_post_status = $object->post_status;
		$autoterms = taxopress_get_autoterm_data();
		$flag = false;

		foreach ($autoterms as $autoterm_data) {
			$eligible_post_status = isset($autoterm_data['post_status']) && is_array($autoterm_data['post_status']) ? $autoterm_data['post_status'] : ['publish'];
			$eligible_post_types = isset($autoterm_data['post_types']) && is_array($autoterm_data['post_types']) ? $autoterm_data['post_types'] : [];
			$eligible_post_types = array_filter($eligible_post_types);

			if (count($eligible_post_types) === 0) {
				continue;
			}
			if (!in_array($current_post_type, $eligible_post_types)) {
				continue;
			}
			if (!in_array($current_post_status, $eligible_post_status)) {
				continue;
			}

			self::auto_terms_post($object, $autoterm_data['taxonomy'], $autoterm_data, false, 'save_posts', 'st_autoterms');
			$flag = true;
		}

		if ($flag == true) { // Clean cache ?
			clean_post_cache($post_id);
		}

		return true;
	}

	/**
	 * Automatically tag a post/page from the database terms for the taxonomy specified
	 *
	 * @param object $object
	 * @param string $taxonomy
	 * @param array $options
	 * @param boolean $counter
	 *
	 * @return boolean
	 * @author WebFactory Ltd
	 */
	public static function auto_terms_post($object, $taxonomy = 'post_tag', $options = array(), $counter = false, $action = 'save_posts', $component = 'st_autoterms')
	{
		global $wpdb, $added_post_term;

		$terms_to_add = array();

		$exclude_autotags = get_post_meta($object->ID, '_exclude_autotags', true);
		if ($exclude_autotags) {
			return false;
		}

		// Option exists ?
		if ($options == false || empty($options)) {
			//update log
			self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'failed', 'invalid_option');
			return false;
		}

		if ((int) $options['autoterm_target'] === 1 && get_the_terms($object->ID, $taxonomy) != false) {
			//update log
			self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'failed', 'term_only_option');
			return false; // Skip post with terms, if term only empty post option is checked
		}

		$autoterm_exclude = isset($options['autoterm_exclude']) ? taxopress_change_to_array($options['autoterm_exclude']) : [];


		$content_source = !empty($options['autoterm_from']) ? $options['autoterm_from'] : 'post_content_title';
		if ($content_source === 'post_title') {
			$content = $object->post_title;
		} elseif ($content_source === 'post_content') {
			$content = $object->post_content;
		} else {
			$content = $object->post_content . ' ' . $object->post_title;
		}

		if (isset($object->post_excerpt)) {
			$content .= ' ' . $object->post_excerpt;
		}

		$html_exclusion  = (!empty($options['html_exclusion']) && is_array($options['html_exclusion'])) ? $options['html_exclusion'] :[];
		$html_exclusion_customs  = !empty($options['html_exclusion_customs']) ? $options['html_exclusion_customs'] : [];
		if (!empty($html_exclusion_customs)) {
			$html_exclusion = array_filter(array_merge($html_exclusion, $html_exclusion_customs));
		}
		if (count($html_exclusion) > 0) {
			foreach ($html_exclusion as $html_tag) {
				$pattern = "/<{$html_tag}[^>]*>.*?<\/{$html_tag}>/is";
    			$content = preg_replace($pattern, '', $content);
			}
		}

		$content = trim(strip_tags($content));

		if (empty($content)) {
			//update log
			self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'failed', 'empty_post_content');
			return false;
		}

		$autoterm_use_open_ai = isset($options['autoterm_use_open_ai']) ? (int) $options['autoterm_use_open_ai'] : 0;
		$autoterm_use_ibm_watson = isset($options['autoterm_use_ibm_watson']) ? (int) $options['autoterm_use_ibm_watson'] : 0;
		$autoterm_use_dandelion = isset($options['autoterm_use_dandelion']) ? (int) $options['autoterm_use_dandelion'] : 0;
		$autoterm_use_opencalais = isset($options['autoterm_use_opencalais']) ? (int) $options['autoterm_use_opencalais'] : 0;
		$autoterm_regex_code = !empty($options['terms_regex_code']) ? stripslashes($options['terms_regex_code']) : '';

		$args = [
			'post_id' => $object->ID,
			'settings_data' => TaxoPressAiUtilities::taxopress_get_ai_settings_data(),
			'content' => $content,
			'clean_content' => TaxoPressAiUtilities::taxopress_clean_up_content($content),
			'content_source' => 'autoterm_' . $content_source

		];

		//Autoterm with OpenAI
		if ($autoterm_use_open_ai > 0 && taxopress_is_pro_version()) {
			$open_ai_results = TaxoPressAiApi::get_open_ai_results($args);
			if (!empty($open_ai_results['results'])) {
				$term_results = $open_ai_results['results'];
				$data = $term_results;
				if (!empty($data)) {
					foreach ((array) $data as $term) {
						$term = stripslashes($term);

						if (!is_string($term)) {
							continue;
						}

						$term = trim($term);
						if (empty($term)) {
							continue;
						}

						//check if term belong to the post already
						/*if (has_term($term, $taxonomy, $object)) {
							continue;
						}*/

						//exclude if name found in exclude terms
						if (in_array($term, $autoterm_exclude)) {
							continue;
						}

						//add primary term
						$add_terms = [];
						$add_terms[$term] = $term;
						// add term synonyms
						if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
							$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
							if (!empty($term_synonyms)) {
								foreach ($term_synonyms as $term_synonym) {
									$add_terms[$term_synonym] = $term;
								}
							}
						}

						// add linked term
						$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

						foreach ($add_terms as $find_term => $original_term) {
							$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';

							if (!empty($terms_regex_code)) {
								if (preg_match("{$terms_regex_code}", $content)) {
									$terms_to_add[] = $term;
								}
							} elseif (isset($options['autoterm_word']) && (int) $options['autoterm_word'] == 1) {
								// Whole word ?
								if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
									$terms_to_add[] = $term;
								}

								//make exception for hashtag special character
								if (substr($find_term, 0, strlen('#')) === '#') {
									$trim_term = ltrim($find_term, '#');
									if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
										$terms_to_add[] = $term;
									}
								}

								if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] == 1 && stristr($content, '#' . $find_term)) {
									$terms_to_add[] = $term;
								}
							} elseif (stristr($content, $find_term)) {
								$terms_to_add[] = $term;
							}
						}
					}
				}
			}
		} elseif ($autoterm_use_ibm_watson > 0 && taxopress_is_pro_version()) {
			//Autoterm with IBM Watson
			$ibm_watson_results = TaxoPressAiApi::get_ibm_watson_results($args);
			if (!empty($ibm_watson_results['results'])) {
				$term_results = $ibm_watson_results['results'];
				$data = $term_results;
				if (!empty($data)) {
					foreach ((array) $data as $term) {
						$term = stripslashes($term);

						if (!is_string($term)) {
							continue;
						}

						$term = trim($term);
						if (empty($term)) {
							continue;
						}

						//check if term belong to the post already
						/*if (has_term($term, $taxonomy, $object)) {
							continue;
						}*/

						//exclude if name found in exclude terms
						if (in_array($term, $autoterm_exclude)) {
							continue;
						}

						//add primary term
						$add_terms = [];
						$add_terms[$term] = $term;
						// add term synonyms
						if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
							$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
							if (!empty($term_synonyms)) {
								foreach ($term_synonyms as $term_synonym) {
									$add_terms[$term_synonym] = $term;
								}
							}
						}

						// add linked term
						$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

						foreach ($add_terms as $find_term => $original_term) {
							$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';

							if (!empty($terms_regex_code)) {
								if (preg_match("{$terms_regex_code}", $content)) {
									$terms_to_add[] = $term;
								}
							} elseif (isset($options['autoterm_word']) && (int) $options['autoterm_word'] == 1) {
								// Whole word ?
								if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
									$terms_to_add[] = $term;
								}

								//make exception for hashtag special character
								if (substr($find_term, 0, strlen('#')) === '#') {
									$trim_term = ltrim($find_term, '#');
									if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
										$terms_to_add[] = $term;
									}
								}

								if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] == 1 && stristr($content, '#' . $find_term)) {
									$terms_to_add[] = $term;
								}
							} elseif (stristr($content, $find_term)) {
								$terms_to_add[] = $term;
							}
						}
					}
				}
			}
		} elseif ($autoterm_use_dandelion > 0 && taxopress_is_pro_version()) {
			//Autoterm with Dandelion
			$dandelion_results = TaxoPressAiApi::get_dandelion_results($args);
			if (!empty($dandelion_results['results'])) {
				$term_results = $dandelion_results['results'];
				$data = $term_results;
				if (!empty($data)) {
					foreach ((array) $data as $term) {
						$term = stripslashes($term);

						if (!is_string($term)) {
							continue;
						}

						$term = trim($term);
						if (empty($term)) {
							continue;
						}

						//check if term belong to the post already
						/*if (has_term($term, $taxonomy, $object)) {
							continue;
						}*/

						//exclude if name found in exclude terms
						if (in_array($term, $autoterm_exclude)) {
							continue;
						}

						//add primary term
						$add_terms = [];
						$add_terms[$term] = $term;
						// add term synonyms
						if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
							$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
							if (!empty($term_synonyms)) {
								foreach ($term_synonyms as $term_synonym) {
									$add_terms[$term_synonym] = $term;
								}
							}
						}

						// add linked term
						$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

						foreach ($add_terms as $find_term => $original_term) {
							$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';
							
							if (!empty($terms_regex_code)) {
								if (preg_match("{$terms_regex_code}", $content)) {
									$terms_to_add[] = $term;
								}
							} elseif (isset($options['autoterm_word']) && (int) $options['autoterm_word'] == 1) {
								// Whole word ?
								if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
									$terms_to_add[] = $term;
								}

								//make exception for hashtag special character
								if (substr($find_term, 0, strlen('#')) === '#') {
									$trim_term = ltrim($find_term, '#');
									if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
										$terms_to_add[] = $term;
									}
								}

								if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] == 1 && stristr($content, '#' . $find_term)) {
									$terms_to_add[] = $term;
								}
							} elseif (stristr($content, $find_term)) {
								$terms_to_add[] = $term;
							}
						}
					}
				}
			}
		} elseif ($autoterm_use_opencalais > 0 && taxopress_is_pro_version()) {
			//Autoterm with OpenCalais
			$open_calais_results = TaxoPressAiApi::get_open_calais_results($args);
			if (!empty($open_calais_results['results'])) {
				$data = $open_calais_results['results'];
				if (!empty($data)) {
					// Remove empty terms
					$data = array_filter($data, '_delete_empty_element');
					$data = array_unique($data);


					foreach ((array) $data as $term) {
						$term = stripslashes($term);

						if (!is_string($term)) {
							continue;
						}

						$term = trim($term);
						if (empty($term)) {
							continue;
						}

						//check if term belong to the post already
						/*if (has_term($term, $taxonomy, $object)) {
							continue;
						}*/

						//exclude if name found in exclude terms
						if (in_array($term, $autoterm_exclude)) {
							continue;
						}

						//add primary term
						$add_terms = [];
						$add_terms[$term] = $term;

						// add term synonyms
						if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
							$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
							if (!empty($term_synonyms)) {
								foreach ($term_synonyms as $term_synonym) {
									$add_terms[$term_synonym] = $term;
								}
							}
						}

						// add linked term
						$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

						foreach ($add_terms as $find_term => $original_term) {
							$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';
							
							if (!empty($terms_regex_code)) {
								if (preg_match("{$terms_regex_code}", $content)) {
									$terms_to_add[] = $term;
								}
							} elseif (isset($options['autoterm_word']) && (int) $options['autoterm_word'] == 1) {
								// Whole word ?
								if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
									$terms_to_add[] = $term;
								}

								//make exception for hashtag special character
								if (substr($find_term, 0, strlen('#')) === '#') {
									$trim_term = ltrim($find_term, '#');
									if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
										$terms_to_add[] = $term;
									}
								}

								if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] == 1 && stristr($content, '#' . $find_term)) {
									$terms_to_add[] = $term;
								}
							} elseif (stristr($content, $find_term)) {
								$terms_to_add[] = $term;
							}
						}
					}
				}
			}
		} elseif (isset($options['specific_terms']) && isset($options['autoterm_useonly']) && (int) $options['autoterm_useonly'] === 1) {
			// Auto term with specific auto terms list
			$terms = maybe_unserialize($options['specific_terms']);
			$terms = taxopress_change_to_array($terms);
			foreach ($terms as $term) {
				if (!is_string($term)) {
					continue;
				}

				$term = trim($term);
				if (empty($term)) {
					continue;
				}

				//check if term belong to the post already
				/*if (has_term($term, $taxonomy, $object)) {
					continue;
				}*/

				//exclude if name found in exclude terms
				if (in_array($term, $autoterm_exclude)) {
					continue;
				}

				//add primary term
				$add_terms = [];
				$add_terms[$term] = $term;

				// add term synonyms
				if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
					$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
					if (!empty($term_synonyms)) {
						foreach ($term_synonyms as $term_synonym) {
							$add_terms[$term_synonym] = $term;
						}
					}
				}

				// add linked term
				$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

				foreach ($add_terms as $find_term => $original_term) {
					$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';

					if (!empty($terms_regex_code)) {
						if (preg_match("{$terms_regex_code}", $content)) {
							$terms_to_add[] = $term;
						}
					} elseif(isset($options['autoterm_word']) && (int) $options['autoterm_word'] === 1) {
						// Whole word ?
						//if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
						if (preg_match("#\b" . preg_quote($find_term) . "\b#i", $content)) {
							$terms_to_add[] = $term;
						}

						//make exception for hashtag special character
						if (substr($find_term, 0, strlen('#')) === '#') {
							$trim_term = ltrim($find_term, '#');
							if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
								$terms_to_add[] = $term;
							}
						}

						if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] === 1 && stristr($content, '#' . $find_term)) {
							$terms_to_add[] = $term;
						}
					} elseif (stristr($content, $find_term)) {
						$terms_to_add[] = $term;
					}
				}
			}
			unset($terms, $term);
		} elseif (isset($options['autoterm_useall']) && (int) $options['autoterm_useall'] === 1) {
			// Auto terms with all terms
			// Get all terms
			$terms = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT name
				FROM {$wpdb->terms} AS t
				INNER JOIN {$wpdb->term_taxonomy} AS tt ON t.term_id = tt.term_id
				WHERE tt.taxonomy = %s", $taxonomy));

			$terms = array_unique($terms);

			foreach ($terms as $term) {
				$term = stripslashes($term);

				if (!is_string($term)) {
					continue;
				}

				$term = trim($term);
				if (empty($term)) {
					continue;
				}

				//check if term belong to the post already
				/*if (has_term($term, $taxonomy, $object)) {
					continue;
				}*/

				//exclude if name found in exclude terms
				if (in_array($term, $autoterm_exclude)) {
					continue;
				}

				//add primary term
				$add_terms = [];
				$add_terms[$term] = $term;

				// add term synonyms
				if (is_array($options) && isset($options['synonyms_term']) && (int) $options['synonyms_term'] > 0) {
					$term_synonyms = taxopress_get_term_synonyms($term, $taxonomy);
					if (!empty($term_synonyms)) {
						foreach ($term_synonyms as $term_synonym) {
							$add_terms[$term_synonym] = $term;
						}
					}
				}

				// add linked term
				$add_terms = taxopress_add_linked_term_options($add_terms, $term, $taxonomy, false, true);

				foreach ($add_terms as $find_term => $original_term) {
					$terms_regex_code = !empty($autoterm_regex_code) ? str_replace('{term}', preg_quote($find_term), $autoterm_regex_code) : '';

					if (!empty($terms_regex_code)) {
						if (preg_match("{$terms_regex_code}", $content)) {
							$terms_to_add[] = $term;
						}
					} elseif (isset($options['autoterm_word']) && (int) $options['autoterm_word'] == 1) {
						// Whole word ?
						//if (preg_match("/\b" . preg_quote($find_term) . "\b/i", $content)) {
						if (preg_match("#\b" . preg_quote($find_term) . "\b#i", $content)) {
							
							$terms_to_add[] = $term;
						}

						//make exception for hashtag special character
						if (substr($find_term, 0, strlen('#')) === '#') {
							$trim_term = ltrim($find_term, '#');
							if (preg_match("/\B(\#+$trim_term\b)(?!;)/i", $content)) {
								$terms_to_add[] = $term;
							}
						}

						if (isset($options['autoterm_hash']) && (int) $options['autoterm_hash'] == 1 && stristr($content, '#' . $find_term)) {
							$terms_to_add[] = $term;
						}
					} elseif (stristr($content, $find_term)) {
						$terms_to_add[] = $term;
					}
				}
			}
		}

		// Append terms if terms to add
		if (!empty($terms_to_add)) {
			// Remove empty and duplicate elements
			$terms_to_add = array_filter($terms_to_add, '_delete_empty_element');
			$terms_to_add = array_unique($terms_to_add);

			//auto terms limit
			$terms_limit = isset($options['terms_limit']) ? (int) $options['terms_limit'] : 0;
			if ($terms_limit > 0 && count($terms_to_add) > $terms_limit) {
				$terms_to_add = array_slice($terms_to_add, 0, $terms_limit);
			}

			// remove term that already belongs to the post
			foreach ($terms_to_add as $index => $term_name) {
				if (has_term($term_name, $taxonomy, $object)) {
					unset($terms_to_add[$index]);
				}
			}

			$terms_to_add = array_filter(array_values($terms_to_add));

			if (empty($terms_to_add)) {
				//update log
				self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'failed', 'empty_terms');
				return false;
			}

			if ($counter == true) {
				// Increment counter
				$counter = ((int) get_option('tmp_auto_terms_st')) + count($terms_to_add);
				update_option('tmp_auto_terms_st', $counter);
			}

			if (!is_array($added_post_term)) {
				$added_post_term = [];
			}

			$added_post_term[$object->ID] = $terms_to_add;

			// Add terms to posts
			wp_set_object_terms($object->ID, $terms_to_add, $taxonomy, true);

			// Clean cache
			clean_post_cache($object->ID);

			//update log
			self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'success', 'terms_added');

			return true;
		} else {
			//update log
			self::update_taxopress_logs($object, $taxonomy, $options, $counter, $action, $component, $terms_to_add, 'failed', 'empty_terms');
		}

		return false;
	}

	/**
	 * Update taxopress logs
	 *
	 * Known possible values
	 * 
	 * COMPONENT: (st_autoterms)
	 * ACTION: (existing_content, save_posts, daily_cron_schedule, hourly_cron_schedule)
	 * STATUS: (failed, success)
	 * STATUS MESSAGE: (invalid_option, term_only_option, empty_post_content, terms_added, empty_terms)
	 * 
	 * @param object $object
	 * @param string $taxonomy
	 * @param array $options
	 * @param boolean $counter
	 * @param string $action
	 * @param string $component
	 * @param array $terms_to_add
	 * @param string $status
	 * @param string $status_message
	 *
	 * @return boolean
	 * @author olatechpro
	 */
	public static function update_taxopress_logs($object, $taxonomy = 'post_tag', $options = array(), $counter = false, $action = 'save_posts', $component = 'st_autoterms', $terms_to_add = [], $status = 'failed', $status_message = 'not_provided')
	{

		if (get_option('taxopress_autoterms_logs_disabled') || !post_type_exists('taxopress_logs')) {
			return;
		}

		$insert_post_args = array(
			'post_author' => get_current_user_id(),
			'post_title' => $object->post_title,
			'post_content' => $object->post_content,
			'post_status' => 'publish',
			'post_type' => 'taxopress_logs',
		);
		$post_id = wp_insert_post($insert_post_args);
		update_post_meta($post_id, '_taxopress_log_post_id', $object->ID);
		update_post_meta($post_id, '_taxopress_log_taxonomy', $taxonomy);
		update_post_meta($post_id, '_taxopress_log_post_type', get_post_type($object->ID));
		update_post_meta($post_id, '_taxopress_log_action', $action);
		update_post_meta($post_id, '_taxopress_log_component', $component);
		update_post_meta($post_id, '_taxopress_log_terms', implode(", ", $terms_to_add));
		update_post_meta($post_id, '_taxopress_log_status', $status);
		update_post_meta($post_id, '_taxopress_log_status_message', $status_message);
		update_post_meta($post_id, '_taxopress_log_options', $options);
		update_post_meta($post_id, '_taxopress_log_option_id', $options['ID']);

		//for performance reason, delete only 1 posts if more than limit instead of querying all posts
		$auto_terms_logs_limit = (int) get_option('taxopress_auto_terms_logs_limit', 1000);

		$current_logs_counts = wp_count_posts('taxopress_logs');
		$current_logs_count = isset($current_logs_counts->publish) ? $current_logs_counts->publish : 0;

		if (isset($current_logs_counts->publish) && (int) $current_logs_count > $auto_terms_logs_limit) {
			$posts = get_posts(
				array(
					'post_type' => 'taxopress_logs',
					'post_status' => 'publish',
					'posts_per_page' => 1,
					'orderby' => 'ID',
					'order' => 'ASC',
					'fields' => 'ids'
				)
			);
			if (count($posts) > 0) {
				foreach ($posts as $post) {
					wp_delete_post($post, true);
				}
			}
		}
	}
}
