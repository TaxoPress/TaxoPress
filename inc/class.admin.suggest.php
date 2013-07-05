<?php
class SimpleTags_Admin_Suggest {
	// Application entrypoint -> https://github.com/herewithme/simple-tags/wiki/
	const yahoo_id = 'h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--';
		
	public function __construct() {
		// Ajax action, JS Helper and admin action
		add_action('wp_ajax_'.'simpletags', array(__CLASS__, 'ajax_check'));
		
		// Box for post/page
		add_action('admin_menu', array(__CLASS__, 'admin_menu'), 1);
		
		// Javascript
		add_action('admin_enqueue_scripts', array(__CLASS__, 'admin_enqueue_scripts'), 11);
	}

	/**
	 * Init somes JS and CSS need for this feature
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_enqueue_scripts() {
		global $pagenow;
		
		wp_register_script('st-helper-suggested-tags', 	STAGS_URL.'/assets/js/helper-suggested-tags.js', array('jquery', 'st-helper-add-tags'), STAGS_VERSION);
		wp_localize_script('st-helper-suggested-tags', 'stHelperSuggestedTagsL10n', array( 'title_bloc' => self::get_suggest_tags_title(), 'content_bloc' => __('Choose a provider to get suggested tags (local, yahoo or tag the net).', 'simpletags') ) );
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages
		if ( in_array($pagenow, $wp_post_pages) || (in_array($pagenow, $wp_page_pages) && is_page_have_tags() ) ) {
			wp_enqueue_script('st-helper-suggested-tags');
		}
	}
	
	/**
	 * Get Suggested tags title
	 *
	 */
	public static function get_suggest_tags_title() {
		$title = '<img style="float:right; display:none;" id="st_ajax_loading" src="'.STAGS_URL.'/assets/images/ajax-loader.gif" alt="' .__('Ajax loading', 'simpletags').'" />';
		$title .=  __('Suggested tags from :', 'simpletags').'&nbsp;&nbsp;';
		$title .= '<a class="local_db" href="#suggestedtags">'.__('Local tags', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="yahoo_api" href="#suggestedtags">'.__('Yahoo', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="opencalais_api" href="#suggestedtags">'.__('OpenCalais', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="alchemyapi" href="#suggestedtags">'.__('AlchemyAPI', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="zemanta" href="#suggestedtags">'.__('Zemanta', 'simpletags').'</a>';
		
		return $title;
	}
	
	/**
	 * Register metabox for suggest tags, for post, and optionnaly page.
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public static function admin_menu() {
		add_meta_box('suggestedtags', __('Suggested tags', 'simpletags'), array(__CLASS__, 'metabox'), 'post', 'advanced', 'core');
		if ( is_page_have_tags() )
			add_meta_box('suggestedtags', __('Suggested tags', 'simpletags'), array(__CLASS__, 'metabox'), 'page', 'advanced', 'core');
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
		if ( isset($_GET['st_action']) )  {
			switch( $_GET['st_action'] ) {
				case 'tags_from_opencalais' :
					self::ajax_opencalais();
				break;
				case 'tags_from_alchemyapi' :
					self::ajax_alchemy_api();
				break;
				case 'tags_from_zemanta' :
					self::ajax_zemanta();
				break;
				case 'tags_from_yahoo' :
					self::ajax_yahoo();
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
		header("Content-Type: text/html; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( SimpleTags_Plugin::get_option_value('opencalais_key') == '' ) {
			echo '<p>'.__('OpenCalais need an API key to work. You can register on service website to obtain a key and set it on Simple Tags options.', 'simpletags').'</p>';
			exit();
		}
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		$response = wp_remote_post('http://api.opencalais.com/enlighten/rest/', array('body' => array(
			'licenseID' => SimpleTags_Plugin::get_option_value('opencalais_key'),
			'content' 	=> $content,
			'paramsXML' => self::_get_params_xml_opencalais()
		)));
		
		if( !is_wp_error($response) && $response != null ) {
			if ( wp_remote_retrieve_response_code($response) == 200 ) {
				$data_raw = json_decode(wp_remote_retrieve_body($response), true);
				
				$data = array();
				if ( isset($data_raw) && is_array($data_raw) ) {
					foreach( $data_raw as $_data_raw ) {
						if ( isset($_data_raw['_typeGroup']) && $_data_raw['_typeGroup'] == 'socialTag' ) {
							$data[] = $_data_raw['name'];
						}
					}
				}
			}
		}
		
		if ( empty($data) || is_wp_error($response) ) {
			echo '<p>'.__('No results from OpenCalais service.', 'simpletags').'</p>';
			exit();
		}
		
		// Remove empty terms
		$data = array_filter($data, '_delete_empty_element');
		$data = array_unique($data);
		
		foreach ( (array) $data as $term ) {
			echo '<span class="local">'.esc_html(strip_tags($term)).'</span>'."\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}
	
	private static function _get_params_xml_opencalais() {
		return '
			<c:params xmlns:c="http://s.opencalais.com/1/pred/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
				<c:processingDirectives c:contentType="text/html" c:outputFormat="application/json" c:enableMetadataType="GenericRelations,SocialTags"></c:processingDirectives>
				<c:userDirectives c:allowDistribution="false" c:allowSearch="false" c:externalID="" c:submitter="Simple Tags"></c:userDirectives>
				<c:externalMetadata></c:externalMetadata>
			</c:params>
		';
	}
	
	/**
	 * Suggest tags from AlchemyAPI
	 *
	 */
	public static function ajax_alchemy_api() {
		status_header( 200 );
		header("Content-Type: text/html; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( SimpleTags_Plugin::get_option_value('alchemy_api') == '' ) {
			echo '<p>'.__('AlchemyAPI need an API key to work. You can register on service website to obtain a key and set it on Simple Tags options.', 'simpletags').'</p>';
			exit();
		}
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		// Build params
		$response = wp_remote_post( 'http://access.alchemyapi.com/calls/html/HTMLGetRankedConcepts', array('body' => array(
			'apikey' 	 => SimpleTags_Plugin::get_option_value('alchemy_api'),
			'maxRetrieve' => 30,
			'html' 		 => $content,
			'outputMode' => 'json',
			'sourceText' => 'cleaned'
		)));
		
		if( !is_wp_error($response) && $response != null ) {
			if ( wp_remote_retrieve_response_code($response) == 200 ) {
				$data = wp_remote_retrieve_body($response);
			}
		}
		
		$data = json_decode($data);
		if ( $data == false || !isset($data->concepts) ) {
			return false;
		}

		if ( empty($data->concepts) ) {
			echo '<p>'.__('No results from Alchemy API.', 'simpletags').'</p>';
			exit();
		}
		
		foreach ( (array) $data->concepts as $term ) {
			echo '<span class="local">'.esc_html($term->text).'</span>'."\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}
	
	/**
	 * Suggest tags from Zemanta
	 *
	 */
	public static function ajax_zemanta() {
		status_header( 200 );
		header("Content-Type: text/html; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( SimpleTags_Plugin::get_option_value('zemanta_key') == '' ) {
			echo '<p>'.__('Zemanta need an API key to work. You can register on service website to obtain a key and set it on Simple Tags options.', 'simpletags').'</p>';
			exit();
		}
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		// Build params
		$response = wp_remote_post( 'http://api.zemanta.com/services/rest/0.0/', array('body' => array(
			'method'	=> 'zemanta.suggest',
			'api_key' 	=> SimpleTags_Plugin::get_option_value('zemanta_key'),
			'text' 		=> $content,
			'format' 	=> 'json',
			'return_rdf_links' => 0,
			'return_images' => 0
		)));
		if( !is_wp_error($response) && $response != null ) {
			if ( wp_remote_retrieve_response_code($response) == 200 ) {
				$data = wp_remote_retrieve_body($response);
			}
		}
		
		$data = json_decode($data);
		$data = $data->keywords;
		
		if ( empty($data) ) {
			echo '<p>'.__('No results from Zemanta API.', 'simpletags').'</p>';
			exit();
		}
		
		foreach ( (array) $data as $term ) {
			echo '<span class="local">'.esc_html($term->name).'</span>'."\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}
	
	/**
	 * Suggest tags from Yahoo Term Extraction
	 *
	 */
	public static function ajax_yahoo() {
		status_header( 200 );
		header("Content-Type: text/html; charset=" . get_bloginfo('charset'));
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		// Build params
		$param = 'appid='.self::yahoo_id; // Yahoo ID
		$param .= '&context='.urlencode($content); // Post content
		if ( !empty($_POST['tags']) ) {
			$param .= '&query='.urlencode(stripslashes($_POST['tags'])); // Existing tags
		}
		$param .= '&output=php'; // Get PHP Array !
		
		$data = array();
		$response = wp_remote_post( 'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction', array('body' =>$param) );
		if( !is_wp_error($response) && $response != null ) {
			if ( wp_remote_retrieve_response_code($response) == 200 ) {
				$data = maybe_unserialize( wp_remote_retrieve_body($response) );
			}
		}
		
		if ( empty($data) || empty($data['ResultSet']) || is_wp_error($data) ) {
			echo '<p>'.__('No results from Yahoo! service.', 'simpletags').'</p>';
			exit();
		}
		
		// Get result value
		$data = (array) $data['ResultSet']['Result'];
		
		// Remove empty terms
		$data = array_filter($data, '_delete_empty_element');
		$data = array_unique($data);
		
		foreach ( (array) $data as $term ) {
			echo '<span class="yahoo">'.esc_html($term).'</span>'."\n";
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
		header("Content-Type: text/html; charset=" . get_bloginfo('charset'));
		
		if ( ((int) wp_count_terms('post_tag', 'ignore_empty=false')) == 0) { // No tags to suggest
			echo '<p>'.__('No terms in your WordPress database.', 'simpletags').'</p>';
			exit();
		}
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		// Get all terms
		$terms = SimpleTags_Admin::getTermsForAjax( 'post_tag', '' );
		if ( empty($terms) || $terms == false ) {
			echo '<p>'.__('No results from your WordPress database.', 'simpletags').'</p>';
			exit();
		}
		
		$flag = false;
		foreach ( (array) $terms as $term ) {
			$term = stripslashes($term->name);
			if ( is_string($term) && !empty($term) && stristr($content, $term) ) {
				$flag = true;
				echo '<span class="local">'.esc_html($term).'</span>'."\n";
			}
		}
		
		if ( $flag == false ) {
			echo '<p>'.__('No correspondance between your content and terms from the WordPress database.', 'simpletags').'</p>';
		} else {
			echo '<div class="clear"></div>';
		}
		
		exit();
	}
}