<?php
class SimpleTags_Admin_Suggest extends SimpleTags_Admin {
	// Application entrypoint -> http://redmine.beapi.fr/projects/show/simple-tags/
	var $yahoo_id = 'h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--';
		
	function SimpleTags_Admin_Suggest() {
		global $pagenow;
		
		// Ajax action, JS Helper and admin action
		add_action('wp_ajax_'.'simpletags', array(&$this, 'ajaxCheck'));
		
		// Box for post
		add_action('admin_menu', array(&$this, 'helperSuggestTags_Post'), 1);
		
		// Box for Page
		if ( is_page_have_tags() ) {
			add_action('admin_menu', array(&$this, 'helperSuggestTags_Page'), 1);
		}
		
		wp_register_script('st-helper-suggested-tags', 	STAGS_URL.'/inc/js/helper-suggested-tags.min.js', array('jquery', 'st-helper-add-tags'), STAGS_VERSION);
		wp_localize_script('st-helper-suggested-tags', 'stHelperSuggestedTagsL10n', array( 'title_bloc' => $this->getSuggestTagsTitle(), 'content_bloc' => __('Choose a provider to get suggested tags (local, yahoo or tag the net).', 'simpletags') ) );
		
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
	function getSuggestTagsTitle() {
		$title = '<img style="float:right; display:none;" id="st_ajax_loading" src="'.STAGS_URL.'/inc/images/ajax-loader.gif" alt="' .__('Ajax loading', 'simpletags').'" />';
		$title .=  __('Suggested tags from :', 'simpletags').'&nbsp;&nbsp;';
		$title .= '<a class="local_db" href="#suggestedtags">'.__('Local tags', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="yahoo_api" href="#suggestedtags">'.__('Yahoo', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="opencalais_api" href="#suggestedtags">'.__('OpenCalais', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="alchemyapi" href="#suggestedtags">'.__('AlchemyAPI', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="zemanta" href="#suggestedtags">'.__('Zemanta', 'simpletags').'</a>&nbsp;&nbsp;-&nbsp;&nbsp;';
		$title .= '<a class="ttn_api" href="#suggestedtags">'.__('Tag The Net', 'simpletags').'</a>';
		return $title;
	}
	
	function helperSuggestTags_Post() {
		add_meta_box('suggestedtags', __('Suggested tags', 'simpletags'), array(&$this, 'boxSuggestTags'), 'post', 'advanced', 'core');
	}
	
	function helperSuggestTags_Page() {
		add_meta_box('suggestedtags', __('Suggested tags', 'simpletags'), array(&$this, 'boxSuggestTags'), 'page', 'advanced', 'core');
	}
	
	/**
	  * Print HTML for suggest tags box
	  *
	  **/
	function boxSuggestTags() {
		?>
		<span class="container_clicktags">
			<?php echo $this->getDefaultContentBox(); ?>
			<div class="clear"></div>
		</span>
	    <?php
	}
	
	/**
	 * Ajax Dispatcher
	 *
	 */
	function ajaxCheck() {
		if ( isset($_GET['st_action']) )  {
			switch( $_GET['st_action'] ) {
				case 'tags_from_opencalais' :
					$this->ajaxOpenCalais();
				break;
				case 'tags_from_alchemyapi' :
					$this->ajaxAlchemyApi();
				break;
				case 'tags_from_zemanta' :
					$this->ajaxZemanta();
				break;
				case 'tags_from_yahoo' :
					$this->ajaxYahooTermExtraction();
				break;
				case 'tags_from_tagthenet' :
					$this->ajaxTagTheNet();
				break;
				case 'tags_from_local_db' :
					$this->ajaxSuggestLocal();
				break;
			}
		}
	}
	
	function getParamsXML() {
		return '
			<c:params xmlns:c="http://s.opencalais.com/1/pred/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
				<c:processingDirectives c:contentType="text/html" c:outputFormat="Text/Simple" c:enableMetadataType="GenericRelations,SocialTags"></c:processingDirectives>
				<c:userDirectives c:allowDistribution="false" c:allowSearch="false" c:externalID="" c:submitter="Simple Tags"></c:userDirectives>
				<c:externalMetadata></c:externalMetadata>
			</c:params>
		';
	}
	
	/**
	 * Suggest tags from OpenCalais Service
	 *
	 */
	function ajaxOpenCalais() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( empty($options['opencalais_key']) ) {
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
		
		$reponse = wp_remote_post('http://api.opencalais.com/enlighten/rest/', array('body' => array(
			'licenseID' => $options['opencalais_key'],
			'content' 	=> $content,
			'paramsXML' => $this->getParamsXML()
		)));
		
		if( !is_wp_error($reponse) && $reponse != null ) {
			if ( wp_remote_retrieve_response_code($reponse) == 200 ) {
				$data = $results = array();
				preg_match('/<CalaisSimpleOutputFormat>(.*?)<\/CalaisSimpleOutputFormat>/s', wp_remote_retrieve_body($reponse), $data );
				preg_match_all('/<(.*?)>(.*?)<\/(.*?)>/s', $data[1], $results );
				$data = $results[2];
			}
		}
		
		if ( empty($data) || is_wp_error($reponse) ) {
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
	
	/**
	 * Suggest tags from AlchemyAPI
	 *
	 */
	function ajaxAlchemyApi() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( empty($options['alchemy_api']) ) {
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
		$data = array();
		$reponse = wp_remote_post( 'http://access.alchemyapi.com/calls/html/HTMLGetRankedNamedEntities', array('body' => array(
			'apikey' 	 => $options['alchemy_api'],
			'url' 		 => ' ',
			'html' 		 => $content,
			'outputMode' => 'json'
		)));
		if( !is_wp_error($reponse) && $reponse != null ) {
			if ( wp_remote_retrieve_response_code($reponse) == 200 ) {
				$data = wp_remote_retrieve_body($reponse);
			}
		}
		
		$data = json_decode($data);
		$data = $data->entities;
		
		if ( empty($data) ) {
			echo '<p>'.__('No results from Alchemy API.', 'simpletags').'</p>';
			exit();
		}
		
		foreach ( (array) $data as $term ) {
			echo '<span class="local">'.esc_html($term->text).'</span>'."\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}
	
	/**
	 * Suggest tags from Zemanta
	 *
	 */
	function ajaxZemanta() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// API Key ?
		if ( empty($options['zemanta_key']) ) {
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
		$data = array();
		$reponse = wp_remote_post( 'http://api.zemanta.com/services/rest/0.0/', array('body' => array(
			'method'	=> 'zemanta.suggest',
			'api_key' 	=> $options['zemanta_key'],
			'text' 		=> $content,
			'format' 	=> 'json',
			'return_rdf_links' => 0,
			'return_images' => 0
		)));
		if( !is_wp_error($reponse) && $reponse != null ) {
			if ( wp_remote_retrieve_response_code($reponse) == 200 ) {
				$data = wp_remote_retrieve_body($reponse);
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
	function ajaxYahooTermExtraction() {
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		// Build params
		$param = 'appid='.$this->yahoo_id; // Yahoo ID
		$param .= '&context='.urlencode($content); // Post content
		if ( !empty($_POST['tags']) ) {
			$param .= '&query='.urlencode(stripslashes($_POST['tags'])); // Existing tags
		}
		$param .= '&output=php'; // Get PHP Array !
		
		$data = array();
		$reponse = wp_remote_post( 'http://search.yahooapis.com/ContentAnalysisService/V1/termExtraction', array('body' =>$param) );
		if( !is_wp_error($reponse) && $reponse != null ) {
			if ( wp_remote_retrieve_response_code($reponse) == 200 ) {
				$data = maybe_unserialize( wp_remote_retrieve_body($reponse) );
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
	 * Suggest tags from Tag The Net
	 *
	 */
	function ajaxTagTheNet() {
		// Send good header HTTP
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			echo '<p>'.__('No text was sent.', 'simpletags').'</p>';
			exit();
		}
		
		$data = '';
		$reponse = wp_remote_post( 'http://tagthe.net/api/n', array('body' => 'text='.urlencode($content).'&view=json&count=200' ) );
		if( !is_wp_error($reponse) ) {
			if ( wp_remote_retrieve_response_code($reponse) == 200 ) {
				$data = maybe_unserialize( wp_remote_retrieve_body($reponse) );
			}
		}
		
		$data = json_decode($data);
		$data = $data->memes[0];
		$data = $data->dimensions;
		
		if ( !isset($data->topic) && !isset($data->location) && !isset($data->person) ) {
			echo '<p>'.__('No results from Tag The Net service.', 'simpletags').'</p>';
			exit();
		}
		
		$terms = array();
		
		// Get all topics
		if ( isset($data->topic) ) {
			foreach ( (array) $data->topic as $topic ) {
				$terms[] = '<span class="ttn_topic">'.esc_html($topic).'</span>';
			}
		}
		
		// Get all locations
		if ( isset($data->location) ) {
			foreach ( (array) $data->location as $location ) {
				$terms[] = '<span class="ttn_location">'.esc_html($location).'</span>';
			}
		}
		
		// Get all persons
		if ( isset($data->person) ) {
			foreach ( (array) $data->person as $person ) {
				$terms[] = '<span class="ttn_person">'.esc_html($person).'</span>';
			}
		}
		
		// Remove empty terms
		$terms = array_filter($terms, '_delete_empty_element');
		$terms = array_unique($terms);
		
		echo implode("\n", $terms);
		echo '<div class="clear"></div>';
		exit();
	}
	
	/**
	 * Suggest tags from local database
	 *
	 */
	function ajaxSuggestLocal() {
		status_header( 200 ); // Send good header HTTP
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
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
		$terms = $this->getTermsForAjax( 'post_tag', '' );
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
?>