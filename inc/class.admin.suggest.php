<?php

class SimpleTags_Admin_Suggest {

	// Application entrypoint -> https://wordpress.org/plugins/simple-tags/wiki/

	/**
	 * SimpleTags_Admin_Suggest constructor.
	 */
	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags', array( __CLASS__, 'ajax_check' ) );

        if ( 1 === (int) SimpleTags_Plugin::get_option_value( 'active_suggest_terms' )){
    		// Box for post/page
	    	add_action( 'admin_head', array( __CLASS__, 'admin_head' ), 1 );
    		// Javascript
	    	add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ), 11 );
        }
	}

	/**
	 * Init somes JS and CSS need for this feature
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow;

        $click_terms = taxopress_current_post_suggest_terms('term_suggestion', false, false);

        if(!is_array($click_terms)){
            return;
        }

		$manage_link = add_query_arg(
			[
				'page'                   => 'st_suggestterms',
				'add'                    => 'new_item',
				'action'                 => 'edit',
			],
			admin_url('admin.php')
		);

		wp_register_script( 'st-helper-suggested-tags', STAGS_URL . '/assets/js/helper-suggested-tags.js', array(
			'jquery',
			'st-helper-add-tags'
		), STAGS_VERSION );
		wp_localize_script( 'st-helper-suggested-tags', 'stHelperSuggestedTagsL10n', array(
			'click_terms' 		=> $click_terms,
			'manage_link' 		=> $manage_link,
			'stag_url' 		    => STAGS_URL,
			'manage_metabox'    => current_user_can('admin_simple_tags') ? 1 : 0,
			'edit_metabox_text' => esc_html__('Edit this metabox', 'simple-tags'),
			'local_term_text' 	=> esc_html__('Existing terms on your site', 'simple-tags'),
			'dandelion_text' 	=> esc_html__('dataTXT by Dandelion', 'simple-tags'),
			'opencalais_text' 	=> esc_html__('OpenCalais', 'simple-tags'),
			'refresh_text' 	    => esc_html__('Refresh', 'simple-tags'),
			'content_bloc'      => esc_html__('Select an option above to load suggested terms.', 'simple-tags'),
			'source_text'       => esc_html__('Select source to load suggested terms', 'simple-tags') 
		) );

        // Helper for post type
        wp_enqueue_script( 'st-helper-suggested-tags' );
	}

	/**
	 * Register metabox for suggest tags, for post, and optionnaly page.
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_head() {

        $click_terms = taxopress_current_post_suggest_terms('term_suggestion', false, false);

        if(!is_array($click_terms)){
            return;
        }
		$key_index = 0;
        foreach ($click_terms as $click_term) {
            add_meta_box(
				'suggestedtags-'.$key_index, 
				esc_html__('Suggested tags', 'simple-tags'), 
				array(__CLASS__, 'metabox'), 
				get_post_type(), 
				'advanced', 
				'core',
				['key_index' => $key_index]
			);
			$key_index++;
        }
	}

	/**
	 * Print HTML for suggest tags box
	 *
	 **/
	public static function metabox($post, $callback_args) {
		?>
        <span class="container_clicktags <?php echo esc_attr('container_clicktags_' . $callback_args['args']['key_index']); ?> multiple" data-key_index="<?php echo esc_attr($callback_args['args']['key_index']); ?>">
			<?php 
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo SimpleTags_Admin::getDefaultContentBox(); ?>
            <div class="clear"></div>
		</span>
		<?php
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
			echo '<span data-term_id="0" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr( $class_current ) . '">' . esc_html( strip_tags( $term ) ) . '</span>' . "\n";
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
			$class_current = in_array(strip_tags( $term ), $post_terms) ? 'used_term' : '';
			echo '<span data-term_id="0" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr( $class_current ) . '">' . esc_html( $term->title ) . '</span>' . "\n";
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

		if(isset($_GET['suggestterms'])){
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
		foreach ( (array) $terms as $term ) {
			$class_current = in_array($term->term_id, $post_terms) ? 'used_term' : '';
            $term_id = $term->term_id;
			$term = stripslashes( $term->name );
			if ( is_string( $term ) && ! empty( $term ) && stristr( $content, $term ) ) {
				$flag = true;
				echo '<span data-term_id="'.esc_attr($term_id).'" data-taxonomy="'.esc_attr($taxonomy).'" class="local ' . esc_attr( $class_current ) . '">' . esc_html( $term ) . '</span>' . "\n";
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
