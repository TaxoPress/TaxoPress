<?php
class SimpleTags_Admin_Autocomplete extends SimpleTags_Admin {
	
	function SimpleTags_Admin_Autocomplete() {
		global $pagenow;
		
		// Save tags from advanced input
		add_action( 'save_post', 	array(&$this, 'saveAdvancedTagsInput'), 10, 2 );
		
		// Box for advanced tags
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox'), 999 );
		
		wp_register_script('jquery-bgiframe',			STAGS_URL.'/ressources/jquery.bgiframe.min.js', array('jquery'), '2.1.1');
		wp_register_script('jquery-autocomplete',		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.min.js', array('jquery', 'jquery-bgiframe'), '1.1');
		
		wp_register_script('st-helper-autocomplete', 	STAGS_URL.'/inc/js/helper-autocomplete.min.js', array('jquery', 'jquery-autocomplete'), STAGS_VERSION);	
		wp_register_style ('jquery-autocomplete', 		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.css', array(), '1.1', 'all' );
		
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
		if ( isset($_GET['page']) && in_array( $_GET['page'], array('st_auto', 'st_mass_terms', 'st_manage') ) ) {
			wp_enqueue_script('jquery-autocomplete');
			wp_enqueue_script('st-helper-autocomplete');
			wp_enqueue_style ('jquery-autocomplete');
		}
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
			wp_set_object_terms( $post_id, $tags, 'post_tag' );
			
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
	 * Call meta box function for taxonomy tags for each CPT
	 *
	 * @param string $post_type 
	 * @return boolean
	 * @author Amaury Balmer
	 */
	function registerMetaBox( $post_type ) {
		$taxonomies = get_object_taxonomies( $post_type );
		if ( in_array('post_tag', $taxonomies) ) {
			if ( $post_type == 'page' && !is_page_have_tags() )
				return false;
			
			remove_meta_box( 'post_tag'.'div', $post_type, 'side' );
			remove_meta_box( 'tagsdiv-'.'post_tag', $post_type, 'side' );
			
			add_meta_box('adv-tagsdiv', __('Tags (Simple Tags)', 'simpletags'), array(&$this, 'boxTags'), $post_type, 'side', 'core', array('taxonomy'=>'post_tag') );
			return true;
		}
		
		return false;
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
		<p>
			<input type="text" class="widefat" name="adv-tags-input" id="adv-tags-input" value="<?php echo esc_attr($this->getTermsToEdit( 'post_tag', $post->ID )); ?>" />
			<?php _e('Separate tags with commas', 'simpletags'); ?>
		</p>
		<script type="text/javascript">
			<!--
			initAutoComplete( '#adv-tags-input', '<?php echo admin_url("admin-ajax.php?action=simpletags&st_action=helper_js_collection"); ?>', 300 );
			-->
		</script>
		<?php
	}
	
}
?>