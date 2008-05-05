<?php
Class SimpleTags_Admin_Suggested {
	
	function SimpleTags_Admin_Suggested() {
		add_action('init', array(&$this, 'checkFormMassEdit'));
		
		add_action('edit_form_advanced', array(&$this, 'helperSuggestTags'), 1);
		add_action('edit_page_form', array(&$this, 'helperSuggestTags'), 1);

	}	

	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {	
		add_management_page( __('Simple Tags: Mass Edit Tags', 'simpletags'), __('Mass Edit Tags', 'simpletags'), 'simple_tags', 'st_mass_tags', array(&$this, 'pageMassEditTags'));
	}
	
	/**
	 * Suggested tags
	 *
	 */
	function helperSuggestTags() {
		?>
		<div id="suggestedtags" class="postbox <?php echo postbox_classes('suggestedtags', 'post'); ?>">
			<h3>
				<img style="float:right; display:none;" id="st_ajax_loading" src="<?php echo $this->info['install_url']; ?>/inc/images/ajax-loader.gif" alt="Ajax loading" />
				<?php _e('Suggested tags from :', 'simpletags'); ?>&nbsp;&nbsp;
				<a class="local_db" href="#suggestedtags"><?php _e('Local tags', 'simpletags'); ?></a>&nbsp;&nbsp;-&nbsp;&nbsp;
				<a class="yahoo_api" href="#suggestedtags"><?php _e('Yahoo', 'simpletags'); ?></a>&nbsp;&nbsp;-&nbsp;&nbsp;
				<a class="ttn_api" href="#suggestedtags"><?php _e('Tag The Net', 'simpletags'); ?></a>
			</h3>
			<div class="inside container_clicktags">
				<?php _e('Choose a provider to get suggested tags (local, yahoo or tag the net).', 'simpletags'); ?>
				<div class="clear"></div>
			</div>
		</div>
	   	<script type="text/javascript">
	    // <![CDATA[
			var site_url = '<?php echo $this->info['siteurl']; ?>';
		// ]]>
	    </script>
	    <script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/helper-suggested-tags.js?ver=<?php echo $this->version; ?>""></script>
    <?php
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
			exit();
		}

		// Application entrypoint -> http://code.google.com/p/simple-tags/
		// Yahoo ID : h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--
		$yahoo_id = 'h4c6gyLV34Fs7nHCrHUew7XDAU8YeQ_PpZVrzgAGih2mU12F0cI.ezr6e7FMvskR7Vu.AA--';
		$yahoo_api_host = 'search.yahooapis.com'; // Api URL
		$yahoo_api_path = '/ContentAnalysisService/V1/termExtraction'; // Api URL
		$tags = stripslashes($_POST['tags']);

		// Build params
		$param = 'appid='.$yahoo_id; // Yahoo ID
		$param .= '&context='.urlencode($content); // Post content
		if ( !empty($tags) ) {
			$param .= '&query='.urlencode($tags); // Existing tags
		}
		$param .= '&output=php'; // Get PHP Array !

		$data = '';
		if ( function_exists('curl_init') ) { // Curl exist ?
			$curl = curl_init();

			curl_setopt($curl, CURLOPT_URL, 'http://'.$yahoo_api_host.$yahoo_api_path.'?'.$param);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    		curl_setopt($curl, CURLOPT_POST, true);

			$data = curl_exec($curl);
			curl_close($curl);

			$data = unserialize($data);
		} else { // Fsocket
			$request = 'appid='.$yahoo_id.$param;

			$http_request  = "POST $yahoo_api_path HTTP/1.0\r\n";
			$http_request .= "Host: $yahoo_api_host\r\n";
			$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
			$http_request .= "Content-Length: " . strlen($request) . "\r\n";
			$http_request .= "\r\n";
			$http_request .= $request;

			if( false != ( $fs = @fsockopen( $yahoo_api_host, 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
				fwrite($fs, $http_request);

				while ( !feof($fs) )
					$data .= fgets($fs, 1160); // One TCP-IP packet
				fclose($fs);
				$data = explode("\r\n\r\n", $data, 2);
			}

			$data = unserialize($data[1]);
		}

		$data = (array) $data['ResultSet']['Result'];
		
		// Remove empty terms
		$data = array_filter($data, array(&$this, 'deleteEmptyElement'));

		foreach ( $data as $term ) {
			echo '<span class="yahoo">'.$term.'</span>'."\n";
		}
		echo '<div class="clear"></div>';
		exit();
	}

	/**
	 * Suggest tags from Tag The Net
	 *
	 */
	function ajaxTagTheNet() {
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			exit();
		}
		
		// Send good header HTTP
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		// Build params
		$api_host = 'tagthe.net'; // Api URL
		$api_path = '/api/'; // Api URL
		$param .= 'text='.urlencode($content); // Post content
		$param .= '&view=xml&count=50';

		$data = '';
		$request = $param;

		$http_request  = "POST $api_path HTTP/1.0\r\n";
		$http_request .= "Host: $api_host\r\n";
		$http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
		$http_request .= "Content-Length: " . strlen($request) . "\r\n";
		$http_request .= "\r\n";
		$http_request .= $request;

		if( false != ( $fs = @fsockopen( $api_host, 80, $errno, $errstr, 3) ) && is_resource($fs) ) {
			fwrite($fs, $http_request);

			while ( !feof($fs) )
				$data .= fgets($fs, 1160); // One TCP-IP packet
			fclose($fs);
			$data = explode("\r\n\r\n", $data, 2);
		}

		$data = $data[1];
		$terms = $all_topics = $all_locations = $all_persons = $persons = $topics = $locations = array();

		// Get all topics
		preg_match_all("/(.*?)<dim type=\"topic\">(.*?)<\/dim>(.*?)/s", $data, $all_topics );
		$all_topics = $all_topics[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_topics, $topics );
		$topics = $topics[2];

		foreach ( (array) $topics as $topic ) {
			$terms[] = '<span class="ttn_topic">'.$topic.'</span>';
		}

		// Get all locations
		preg_match_all("/(.*?)<dim type=\"location\">(.*?)<\/dim>(.*?)/s", $data, $all_locations );
		$all_locations = $all_locations[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_locations, $locations );
		$locations = $locations[2];

		foreach ( (array) $locations as $location ) {
			$terms[] = '<span class="ttn_location">'.$location.'</span>';
		}

		// Get all persons
		preg_match_all("/(.*?)<dim type=\"person\">(.*?)<\/dim>(.*?)/s", $data, $all_persons );
		$all_persons = $all_persons[2][0];

		preg_match_all("/(.*?)<item>(.*?)<\/item>(.*?)/s", $all_persons, $persons );
		$persons = $persons[2];

		foreach ( (array) $persons as $person ) {
			$terms[] = '<span class="ttn_person">'.$person.'</span>';
		}
		
		// Remove empty terms
		$terms = array_filter($terms, array(&$this, 'deleteEmptyElement'));

		echo implode("\n", $terms);
		echo '<div class="clear"></div>';
		exit();
	}

	/**
	 * Suggest tags from local database
	 *
	 */
	function ajaxSuggestLocal() {
		$total = wp_count_terms('post_tag');	
		global $simple_tags;	
		if ( $total == 0 || is_null($simple_tags) ) { // No tags to suggest or Simple Tags not exist
			exit();
		}
				
		// Get data
		$content = stripslashes($_POST['content']) .' '. stripslashes($_POST['title']);
		$content = trim($content);
		if ( empty($content) ) {
			exit();
		}
		
		// Send good header HTTP
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));

		$counter = 0;
		while ( ( $counter * 200 ) < $total ) {
			// Get tags
			$tags = $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);

			foreach ( (array) $tags as $tag ) {
				if ( is_string($tag->name) && !empty($tag->name) && stristr($content, $tag->name) ) {
					echo '<span class="local">'.$tag->name.'</span>'."\n";
				}
			}
			unset($tags, $tag);

			// Increment counter
			$counter++;
		}
		echo '<div class="clear"></div>';
		exit();
	}
}
?>