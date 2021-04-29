<?php

class SimpleTags_Admin_Suggest {

	// Application entrypoint -> https://wordpress.org/plugins/simple-tags/wiki/

	/**
	 * SimpleTags_Admin_Suggest constructor.
	 */
	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action( 'wp_ajax_simpletags', array( __CLASS__, 'ajax_check' ) );

		// Box for post/page
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 1 );

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
		global $pagenow;

		wp_register_script( 'st-helper-suggested-tags', STAGS_URL . '/assets/js/helper-suggested-tags.js', array(
			'jquery',
			'st-helper-add-tags'
		), STAGS_VERSION );
		wp_localize_script( 'st-helper-suggested-tags', 'stHelperSuggestedTagsL10n', array(
			'title_bloc'   => self::get_suggest_tags_title(),
			'content_bloc' => __( 'Click a provider name.', 'simpletags' )
		) );

		// Register location
		$wp_post_pages = array( 'post.php', 'post-new.php' );
		$wp_page_pages = array( 'page.php', 'page-new.php' );

		// Helper for posts/pages
		if ( in_array( $pagenow, $wp_post_pages ) || ( in_array( $pagenow, $wp_page_pages ) && is_page_have_tags() ) ) {
			wp_enqueue_script( 'st-helper-suggested-tags' );
		}
	}

	/**
	 * Get Suggested tags title
	 *
	 */
	public static function get_suggest_tags_title() {
		$title = '<img style="display:none;" id="st_ajax_loading" src="' . STAGS_URL . '/assets/images/ajax-loader.gif" alt="' . __( 'Ajax loading', 'simpletags' ) . '" />';
		$title .= __( 'Automatic tag suggestions:', 'simpletags' ) . '';
		
		$title .= '&nbsp; <a data-ajaxaction="tags_from_local_db" class="suggest-action-link" href="#suggestedtags">' . __( 'Local tags', 'simpletags' ) . '</a>';

		if ( SimpleTags_Plugin::get_option_value( 'datatxt_access_token' ) !== '' ) {
		$title .= '&nbsp; - &nbsp;<a data-ajaxaction="tags_from_datatxt" class="suggest-action-link" href="#suggestedtags">' . __( 'dataTXT by Dandelion', 'simpletags' ) . '</a>';
		}

		if ( SimpleTags_Plugin::get_option_value( 'opencalais_key' ) !== '' ) {
		$title .= '&nbsp; - &nbsp;<a data-ajaxaction="tags_from_opencalais" class="suggest-action-link" href="#suggestedtags">' . __( 'OpenCalais', 'simpletags' ) . '</a>';
		}


		return $title;
	}

	/**
	 * Register metabox for suggest tags, for post, and optionnaly page.
	 *
	 * @return void
	 * @author WebFactory Ltd
	 */
	public static function admin_menu() {
		add_meta_box( 'suggestedtags', __( 'Suggested tags', 'simpletags' ), array(
			__CLASS__,
			'metabox'
		), 'post', 'advanced', 'core' );
		if ( is_page_have_tags() ) {
			add_meta_box( 'suggestedtags', __( 'Suggested tags', 'simpletags' ), array(
				__CLASS__,
				'metabox'
			), 'page', 'advanced', 'core' );
		}
	}

	/**
	 * Print HTML for suggest tags box
	 *
	 **/
	public static function metabox() {
		?>
        <span class="container_clicktags">
			<?php echo SimpleTags_Admin::getDefaultContentBox(); ?>
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
		}
	}

	/**
	 * Suggest tags from OpenCalais Service
	 *
	 */
	public static function ajax_opencalais() {
		status_header( 200 );
		header( "Content-Type: text/html; charset=" . get_bloginfo( 'charset' ) );

		// API Key ?
		if ( SimpleTags_Plugin::get_option_value( 'opencalais_key' ) == '' ) {
			echo '<p>' . __( 'OpenCalais need an API key to work. You can register on service website to obtain a key and set it on TaxoPress options.', 'simpletags' ) . '</p>';
			exit();
		}

		// Get data
		$content = stripslashes( $_POST['content'] ) . ' ' . stripslashes( $_POST['title'] );
		$content = trim( $content );
		if ( empty( $content ) ) {
			echo '<p>' . __( 'No text was sent.', 'simpletags' ) . '</p>';
			exit();
		}

		$response = wp_remote_post( 'https://api-eit.refinitiv.com/permid/calais', array(
			'timeout' => 30,
			'headers' => array(
				'X-AG-Access-Token' => SimpleTags_Plugin::get_option_value( 'opencalais_key' ),
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
			echo '<p>' . __( 'No results from OpenCalais service.', 'simpletags' ) . '</p>';
			exit();
		}

		// Remove empty terms
		$data = array_filter( $data, '_delete_empty_element' );
		$data = array_unique( $data );

		foreach ( (array) $data as $term ) {
			echo '<span class="local">' . esc_html( strip_tags( $term ) ) . '</span>' . "\n";
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

		// Get data
		$content = stripslashes( $_POST['content'] ) . ' ' . stripslashes( $_POST['title'] );
		$content = trim( $content );
		if ( empty( $content ) ) {
			echo '<p>' . __( 'No text was sent.', 'simpletags' ) . '</p>';
			exit();
		}

		$request_ws_args['text'] = $content;

		// Custom confidence ?
		$request_ws_args['min_confidence'] = 0.6;
		if ( SimpleTags_Plugin::get_option_value( 'datatxt_min_confidence' ) != "" ) {
			$request_ws_args['min_confidence'] = SimpleTags_Plugin::get_option_value( 'datatxt_min_confidence' );
		}

		$request_ws_args['token'] = SimpleTags_Plugin::get_option_value( 'datatxt_access_token' );

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
				echo '<p>' . __( 'Invalid access token !', 'simpletags' ) . '</p>';
				exit();
			}
		}

		$data = json_decode( $data );

		// echo $data;

		$data = $data->annotations;

		if ( empty( $data ) ) {
			echo '<p>' . __( 'No results from dataTXT API.', 'simpletags' ) . '</p>';
			exit();
		}

		foreach ( (array) $data as $term ) {
			echo '<span class="local">' . esc_html( $term->title ) . '</span>' . "\n";
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

		if ( ( (int) wp_count_terms( 'post_tag', array( 'hide_empty' => false ) ) ) == 0 ) { // No tags to suggest
			echo '<p>' . __( 'No terms in your WordPress database.', 'simpletags' ) . '</p>';
			exit();
		}

		// Get data
		$content = stripslashes( $_POST['content'] ) . ' ' . stripslashes( $_POST['title'] );
		$content = trim( $content );

		if ( empty( $content ) ) {
			echo '<p>' . __( 'No text was sent.', 'simpletags' ) . '</p>';
			exit();
		}

		// Get all terms
		$terms = SimpleTags_Admin::getTermsForAjax( 'post_tag', '' );
		if ( empty( $terms ) || $terms == false ) {
			echo '<p>' . __( 'No results from your WordPress database.', 'simpletags' ) . '</p>';
			exit();
		}

		$flag = false;
		foreach ( (array) $terms as $term ) {
			$term = stripslashes( $term->name );
			if ( is_string( $term ) && ! empty( $term ) && stristr( $content, $term ) ) {
				$flag = true;
				echo '<span class="local">' . esc_html( $term ) . '</span>' . "\n";
			}
		}

		if ( $flag == false ) {
			echo '<p>' . __( 'No correspondence between your content and terms from the WordPress database.', 'simpletags' ) . '</p>';
		} else {
			echo '<div class="clear"></div>';
		}

		exit();
	}
}
