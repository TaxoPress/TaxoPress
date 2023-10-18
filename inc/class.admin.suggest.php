<?php
require_once STAGS_DIR . '/modules/taxopress-ai/classes/TaxoPressAiUtilities.php';
require_once STAGS_DIR . '/modules/taxopress-ai/classes/TaxoPressAiAjax.php';

class SimpleTags_Admin_Suggest {

	// Application entrypoint -> https://wordpress.org/plugins/simple-tags/wiki/

	/**
	 * SimpleTags_Admin_Suggest constructor.
	 */
	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags', array( __CLASS__, 'ajax_check' ) );
		// Ajax action, JS Helper and admin action
		add_action( 'load_taxopress_ai_term_results', array( __CLASS__, 'load_result' ) );
		
        // Box for post/page
	    add_action( 'admin_head', array( __CLASS__, 'admin_head' ), 1 );
    	// Javascript
    	add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ), 11 );
	}

	/**
	 * Init somes JS and CSS need for this feature
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_enqueue_scripts() {

        $taxopress_ai_post_types = SimpleTags_Plugin::get_option_value('taxopress_ai_post_types');

        if(!is_array($taxopress_ai_post_types) || !in_array(get_post_type(), $taxopress_ai_post_types)){
            return;
        }

		$manage_link = add_query_arg(
			[
				'page'                   => 'st_taxopress_ai'
			],
			admin_url('admin.php')
		);

		wp_enqueue_script( 'taxopress-ai-editor-js', STAGS_URL . '/modules/taxopress-ai/assets/js/taxopress-ai-editor.js', array(
			'jquery'
		), STAGS_VERSION );

		wp_enqueue_style('taxopress-ai-editor-css', STAGS_URL . '/modules/taxopress-ai/assets/css/taxopress-ai-editor.css', [], STAGS_VERSION, 'all');

		wp_localize_script(
			'taxopress-ai-editor-js',
			'taxoPressAIRequestAction',
			[
				'requiredSuffix' => esc_html__('is required', 'simple-tags'),
				'nonce' => wp_create_nonce('taxopress-ai-ajax-nonce'),
				'apiEditLink' => '<span class="edit-suggest-term-metabox"> <a target="blank" href="' . $manage_link . '"> '. esc_html__('Manage API Configuration', 'simple-tags') .' </a></span>'
			]
		);
	}

	/**
	 * Register metabox for suggest tags, for post, and optionnaly page.
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_head() {

        $taxopress_ai_post_types = SimpleTags_Plugin::get_option_value('taxopress_ai_post_types');

        if(!is_array($taxopress_ai_post_types) || !in_array(get_post_type(), $taxopress_ai_post_types)){
            return;
        }

		add_meta_box(
			'taxopress-ai-suggestedtags', 
			esc_html__('TaxoPress AI Integration', 'simple-tags'), 
			array(__CLASS__, 'metabox'), 
			get_post_type(), 
			'normal', 
			'core'
		);
	}

	/**
	 * Print HTML for suggest tags box
	 *
	 **/
	public static function metabox($post) {
		?>
        <div class="taxopress-post-suggestterm">
			<div class="taxopress-suggest-terms-contents">
			<?php
				$content_tabs = [];
				$content_tabs['post_terms'] = esc_html__('Manage Post Terms', 'simple-tags');
				$content_tabs['suggest_local_terms'] = esc_html__('Suggest Existing Terms', 'simple-tags');
				$content_tabs['existing_terms'] = esc_html__('Show All Existing Terms', 'simple-tags');
				$content_tabs['open_ai'] = esc_html__('Open AI', 'simple-tags');
				$content_tabs['ibm_watson'] = esc_html__('IBM Watson', 'simple-tags');
				$content_tabs['dandelion'] = esc_html__('Dandelion', 'simple-tags');
				$content_tabs['open_calais'] = esc_html__('LSEG / Refinitiv', 'simple-tags');
                $post_type_taxonomies = get_object_taxonomies($post->post_type, 'objects');
				$post_type_taxonomy_names = array_keys($post_type_taxonomies);
				$default_taxonomy = (in_array('post_tag', $post_type_taxonomy_names) ? 'post_tag' : $post_type_taxonomy_names[0]);

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
							?>
							<table class="taxopress-tab-content-item form-table taxopress-table taxopress-ai-tab-content <?php echo esc_attr($key); ?>"
								data-ai-source="<?php echo esc_attr($key); ?>"
								data-post_id="<?php echo esc_attr($post->ID); ?>"
								style="<?php echo ($content_index === 0) ? '' : 'display:none;'; ?>">
								<tbody>
									<tr>
										<td>
											<div class="taxopress-ai-fetch-wrap">
												<select class="taxopress-ai-fetch-taxonomy-select">
														<?php foreach ($post_type_taxonomies as $tax_key => $tax_object):
														$rest_api_base = !empty($tax_object->rest_base) ? $tax_object->rest_base : $tax_key;
														$hierarchical = !empty($tax_object->hierarchical) ? (int) $tax_object->hierarchical : 0;
														 ?>
															<option value='<?php echo esc_attr($tax_key); ?>'
															data-rest_base='<?php echo esc_attr($rest_api_base); ?>'
															data-hierarchical='<?php echo esc_attr($hierarchical); ?>'
															<?php selected($tax_key, $default_taxonomy); ?>>
																<?php echo esc_html($tax_object->labels->name. ' ('.$tax_object->name.')'); ?>
															</option>
														<?php endforeach; ?>
												</select>
												<button class="button button-secondary taxopress-ai-fetch-button">
													<div class="spinner"></div>
													<?php echo esc_html__('Fetch Term', 'simple-tags'); ?>
												</button>
											</div>
                                        	<div class="taxopress-ai-fetch-result <?php echo esc_attr($key); ?>">
											<?php do_action('load_taxopress_ai_term_results', $result_request_args); ?>
											</div>
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
			</div>
		</div>
		<?php
	}

	public static function load_result($args) {
		$action       = $args['action'];
		$taxonomy     = $args['taxonomy'];
		$ai_group     = $args['ai_group'];
		$post_title   = $args['post_title'];
		$post_content = $args['post_content'];
		$post_id 	  = $args['post_id'];

		if (in_array($ai_group, ['existing_terms', 'suggest_local_terms', 'post_terms'])) {
			$content = $post_content . ' ' . $post_title;
			$settings_data = TaxoPressAiUtilities::taxopress_get_ai_settings_data();
			$result_args = [
				'settings_data' => $settings_data,
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
					$formatted_result = TaxoPressAiUtilities::format_taxonomy_term_results($post_terms_results, $taxonomy, $post_id, $legend_title, $result_args['show_counts']);

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
			} else {
				echo $term_results['message'];
			}

		}
	}

	/**
	 * Ajax Dispatcher
	 *
	 */
	public static function ajax_check() {
		if ( isset( $_GET['stags_action'] ) ) {
			switch ( $_GET['stags_action'] ) {
				case 'tags_from_datatxt' :
					self::ajax_datatxt();
					break;
				case 'tags_from_opencalais' :
					self::ajax_opencalais();
					break;
				case 'tags_from_local_db' :
					self::ajax_suggest_local();
					break;
			}
		}else{
            self::invalid_ajax_request();
        }
	}

	/**
	 * Suggest tags from OpenCalais Service
	 *
	 */
	public static function invalid_ajax_request() {
        status_header( 200 );
		header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );
        echo '<p>' . esc_html__( 'Invalid request.', 'simple-tags' ) . '</p>';
		exit();
    }

	/**
	 * Suggest tags from OpenCalais Service
	 *
	 */
	public static function ajax_opencalais() {
		status_header( 200 );
		header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );


        $suggestterms = taxopress_get_suggestterm_data();
        $selected_suggestterm = (int)$_GET['suggestterms'];
        $click_terms = false;
        $taxonomy =  'post_tag';
        if (array_key_exists($selected_suggestterm, $suggestterms)) {
            $click_terms       = $suggestterms[$selected_suggestterm];
            $taxonomy          = $click_terms['taxonomy'];
        }

        if(!$click_terms){
			echo '<p>' . esc_html__( 'Suggest terms settings not found', 'simple-tags' ) . '</p>';
			exit();
        }

		// API Key ?
		if ( $click_terms['terms_opencalais_key'] == '' ) {
			echo '<p>' . esc_html__( 'OpenCalais need an API key to work. You can register on service website to obtain a key and set it on TaxoPress options.', 'simple-tags' ) . '</p>';
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

		$response = wp_remote_post( 'https://api-eit.refinitiv.com/permid/calais', array(
			'timeout' => 30,
			'headers' => array(
				'X-AG-Access-Token' => $click_terms['terms_opencalais_key'],
				'Content-Type'      => 'text/html',
				'outputFormat'      => 'application/json'
			),
			'body'    => $content
		) );

		if ( ! is_wp_error( $response ) && $response != null ) {
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data_raw = json_decode( wp_remote_retrieve_body( $response ), true );

				$data = array();
				if ( isset( $data_raw ) && is_array( $data_raw ) ) {
					foreach ( $data_raw as $_data_raw ) {
						if ( isset( $_data_raw['_typeGroup'] ) && $_data_raw['_typeGroup'] == 'socialTag' ) {
							$data[] = $_data_raw['name'];
						}
					}
				}
			}
		}

		if ( empty( $data ) || is_wp_error( $response ) ) {
			echo '<p>' . esc_html__( 'No results from OpenCalais service.', 'simple-tags' ) . '</p>';
			exit();
		}

		// Remove empty terms
		$data = array_filter( $data, '_delete_empty_element' );
		$data = array_unique( $data );

		// Get terms for current post
		$post_terms = array();
		if ( $post_id > 0 ) {
			$post_terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		}

		foreach ( (array) $data as $term ) {
			$class_current = in_array(strip_tags( $term ), $post_terms) ? 'used_term' : '';
			echo '<span data-term_id="0" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr( $class_current ) . '" tabindex="0" role="button" aria-pressed="false">' . esc_html( strip_tags( $term ) ) . '</span>' . "\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}

	/**
	 * Suggest tags from dataTXT
	 *
	 */
	public static function ajax_datatxt() {
		status_header( 200 );
		header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );

		$request_ws_args = array();

        $suggestterms = taxopress_get_suggestterm_data();
        $selected_suggestterm = (int)$_GET['suggestterms'];
        $click_terms = false;
        $taxonomy =  'post_tag';
        if (array_key_exists($selected_suggestterm, $suggestterms)) {
            $click_terms       = $suggestterms[$selected_suggestterm];
            $taxonomy          = $click_terms['taxonomy'];
        }

        if(!$click_terms){
			echo '<p>' . esc_html__( 'Suggest terms settings not found', 'simple-tags' ) . '</p>';
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

		$request_ws_args['text'] = $content;

		// Custom confidence ?
		$request_ws_args['min_confidence'] = 0.6;
		if ( $click_terms['terms_datatxt_min_confidence'] != "" ) {
			$request_ws_args['min_confidence'] = $click_terms['terms_datatxt_min_confidence'];
		}

		$request_ws_args['token'] = $click_terms['terms_datatxt_access_token'];

		// Build params
		$response = wp_remote_post( 'https://api.dandelion.eu/datatxt/nex/v1', array(
			'user-agent' => 'WordPress simple-tags',
			'body'       => $request_ws_args
		) );

		$data = false;
		if ( ! is_wp_error( $response ) && $response != null ) {
			if ( wp_remote_retrieve_response_code( $response ) == 200 ) {
				$data = wp_remote_retrieve_body( $response );
			} else {
				echo '<p>' . esc_html__( 'Invalid access token !', 'simple-tags' ) . '</p>';
				exit();
			}
		}

		$data = json_decode( $data );

		// echo $data;
		$data = is_object($data) ? $data->annotations : '';

		if ( empty( $data ) ) {
			echo '<p>' . esc_html__( 'No results from dataTXT API.', 'simple-tags' ) . '</p>';
			exit();
		}

		// Get terms for current post
		$post_terms = array();
		if ( $post_id > 0 ) {
			$post_terms = wp_get_post_terms( $post_id, $taxonomy, array( 'fields' => 'names' ) );
		}

		foreach ( (array) $data as $term ) {
			$class_current = in_array(strip_tags($term->title), $post_terms) ? 'used_term' : '';
			echo '<span data-term_id="0" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr( $class_current ) . '" tabindex="0" role="button" aria-pressed="false">' . esc_html( $term->title ) . '</span>' . "\n";
		}
		echo '<div class="clear"></div>';
		exit();
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
}
