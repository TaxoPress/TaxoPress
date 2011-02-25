<?php
class SimpleTags_Admin_Autocomplete extends SimpleTags_Admin {
	
	function SimpleTags_Admin_Autocomplete() {
		global $pagenow;
		
		// Save tags from advanced input
		add_action( 'publish_post', array(&$this, 'saveAdvancedTagsInput'), 10, 2 );
		add_action( 'save_post', 	array(&$this, 'saveAdvancedTagsInput'), 10, 2 );
		add_action( 'do_meta_boxes', array(&$this, 'removeOldTagsInput'), 1 );
		
		// Box for advanced tags
		add_action( 'admin_menu', array(&$this, 'helperAdvancedTags'), 1 );
		
		wp_register_script('jquery-bgiframe',			STAGS_URL.'/inc/js/jquery.bgiframe.min.js', array('jquery'), '2.1.1');
		wp_register_script('jquery-autocomplete',		STAGS_URL.'/inc/js/jquery.autocomplete.min.js', array('jquery', 'jquery-bgiframe'), '1.1');
		
		wp_register_script('st-helper-autocomplete', 	STAGS_URL.'/inc/js/helper-autocomplete.min.js', array('jquery', 'jquery-autocomplete'), STAGS_VERSION);	
		wp_register_style ('jquery-autocomplete', 		STAGS_URL.'/inc/css/jquery.autocomplete.css', array(), '1.1', 'all' );
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages
		if ( in_array($pagenow, $wp_post_pages) || ( in_array($pagenow, $wp_page_pages) && is_page_have_tags() ) ) {
			wp_enqueue_script('jquery-autocomplete');
			wp_enqueue_script('st-helper-autocomplete');
			wp_enqueue_style ('jquery-autocomplete');
		}
		
		// add JS for Auto Tags, Mass Edit Tags and Manage tags !
		if ( isset($_GET['page']) && in_array( $_GET['page'], array('st_auto', 'st_mass_terms') ) ) {
			wp_enqueue_script('jquery-autocomplete');
			wp_enqueue_script('st-helper-autocomplete');
			wp_enqueue_style ('jquery-autocomplete');
		}
	}
	
	/**
	 * Remove the old tags input
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function removeOldTagsInput() {
		remove_meta_box('tagsdiv-post_tag', 'post', 'side');
	}
	
	/**
	 * Save tags input for old field
	 *
	 * @param string $post_id 
	 * @param object $object 
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function saveAdvancedTagsInput( $post_id = 0, $object = null ) {
		if ( isset($_POST['adv-tags-input']) ) {
			// Trim/format data
			$tags = preg_replace( "/[\n\r]/", ', ', stripslashes($_POST['adv-tags-input']) );
			$tags = trim($tags);
			
			// String to array
			$tags = explode( ',', $tags );
			
			// Remove empty and trim tag
			$tags = array_filter($tags, '_delete_empty_element');
			
			// Add new tag (no append ! replace !)
			wp_set_object_terms( $post_id, $tags, 'post_tag' ); // TODO ?
			
			// Clean cache
			if ( 'page' == $object->post_type ) {
				clean_page_cache($post_id);
			} else {
				clean_post_cache($post_id);
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Call meta box function if option is active on post/page
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function helperAdvancedTags() {
		add_meta_box('adv-tagsdiv', __('Tags (Simple Tags)', 'simpletags'), array(&$this, 'boxTags'), 'post', 'side', 'core', array('taxonomy'=>'post_tag') );
		
		if ( is_page_have_tags() )
			add_meta_box('adv-tagsdiv', __('Tags (Simple Tags)', 'simpletags'), array(&$this, 'boxTags'), 'page', 'side', 'core', array('taxonomy'=>'post_tag') );
	}
	
	/**
	 * Content of custom meta box of Simple Tags
	 *
	 * @param object $post
	 * @return void
	 * @author Amaury Balmer
	 */
	function boxTags( $post ) {
		?>
		<textarea name="adv-tags-input" id="adv-tags-input" tabindex="3" rows="3" cols="5"><?php echo $this->getTermsToEdit( 'post_tag', $post->ID ); ?></textarea>
		<script type="text/javascript">
			<!--
			initAutoComplete( '#adv-tags-input', '<?php echo admin_url("admin-ajax.php?action=simpletags&st_action=helper_js_collection"); ?>', 300 );
			-->
		</script>
		<?php
		_e('Separate tags with commas', 'simpletags');
	}
	
}
?>