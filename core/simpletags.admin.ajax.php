<?php
Class SimpleTags_Admin_Ajax {
	
	function SimpleTags_Admin_Ajax() {
		
	}

	/**
	 * Ajax Dispatcher
	 *
	 */
	function ajaxCheck() {
		if ( $_GET['st_ajax_action'] == 'tags_from_yahoo' ) {
			$this->ajaxYahooTermExtraction();
		} elseif ( $_GET['st_ajax_action'] == 'tags_from_tagthenet' ) {
			$this->ajaxTagTheNet();
		} elseif ( $_GET['st_ajax_action'] == 'helper_js_collection' ) {
			$this->ajaxLocalTags( 'js_collection' );
		} elseif ( $_GET['st_ajax_action'] == 'tags_from_local_db' ) {
			$this->ajaxSuggestLocal();
		} elseif ( $_GET['st_ajax_action'] == 'click_tags' ) {
			$this->ajaxLocalTags( 'html_span' );
		}
	}
	
	/**
	 * Display a span list for click tags or a javascript collection for autocompletion script !
	 *
	 * @param string $format
	 */
	function ajaxLocalTags( $format = 'span' ) {
		$total = wp_count_terms('post_tag');	
		global $simple_tags;	
		if ( $total == 0 || is_null($simple_tags) ) { // No tags to suggest or Simple Tags not exist
			exit();
		}
		
		// Send good header HTTP
		status_header( 200 );
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
			
		if ( $format == 'js_collection' ) {
			echo 'collection = [';
		}
		$counter = 0;
		$flag = false;
		while ( ( $counter * 200 ) < $total ) {
			// Get tags
			$tags = $simple_tags->getTags('hide_empty=false&cloud_selection=count-desc&number=LIMIT '. $counter * 200 . ', '. 200, true);
		
			switch ($format) {
				case 'html_span' :
					foreach ( (array) $tags as $tag ) {					
						echo '<span class="local">'.$tag->name.'</span>'."\n";
					}
					break;
				case 'js_collection' :
				default:
					foreach ( (array) $tags as $tag ) {		
						if ( $flag === false) {
							echo '"'.str_replace('"', '\"', $tag->name).'"';
							$flag = true;
						} else {
							echo ', "'.str_replace('"', '\"', $tag->name).'"';
						}
					}
					break;
			}
			unset($tags, $tag);

			// Increment counter
			$counter++;
		}
		
		if ( $format == 'js_collection' ) { 
			echo '];';
		} else {
			echo '<div class="clear"></div>';
		}
		exit();
	}

	
}
?>