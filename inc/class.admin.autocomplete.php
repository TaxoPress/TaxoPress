<?php
class SimpleTags_Admin_Autocomplete extends SimpleTags_Admin {
	
	function SimpleTags_Admin_Autocomplete() {
		// Ajax action, JS Helper and admin action
		add_action('wp_ajax_'.'simpletags', array(&$this, 'ajaxCheck'));
		
		// Save tags from advanced input
		add_action( 'save_post', 	array(&$this, 'saveAdvancedTagsInput'), 10, 2 );
		
		// Box for advanced tags
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox'), 999 );
		
		// Simple Tags hook
		add_action( 'simpletags-auto_terms', array(&$this, 'autoTermsJavaScript') );
		add_action( 'simpletags-manage_terms', array(&$this, 'manageTermsJavaScript') );
		add_action( 'simpletags-mass_terms', array(&$this, 'massTermsJavascript') );
		
		// Javascript
		add_action('admin_enqueue_scripts', array(&$this, 'initJavaScript'), 11);
	}

	/**
	 * Init somes JS and CSS need for this feature
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function initJavaScript() {
		global $pagenow;
		
		// Register JS/CSS
		wp_register_script('jquery-bgiframe',			STAGS_URL.'/ressources/jquery.bgiframe.min.js', array('jquery'), '2.1.1');
		wp_register_script('jquery-autocomplete',		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.min.js', array('jquery', 'jquery-bgiframe'), '1.2.2');
		
		wp_register_script('st-helper-autocomplete', 	STAGS_URL.'/inc/js/helper-autocomplete.min.js', array('jquery', 'jquery-autocomplete'), STAGS_VERSION);	
		wp_register_style ('jquery-autocomplete', 		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.css', array(), '1.2.2', 'all' );
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages and for Auto Tags, Mass Edit Tags and Manage tags !
		if ( (in_array($pagenow, $wp_post_pages) || ( in_array($pagenow, $wp_page_pages) && is_page_have_tags() )) || ( isset($_GET['page']) && in_array( $_GET['page'], array('st_auto', 'st_mass_terms', 'st_manage') ) ) ) {
			wp_enqueue_script('jquery-autocomplete');
			wp_enqueue_script('st-helper-autocomplete');
			wp_enqueue_style ('jquery-autocomplete');
		}
	}
	
	/**
	 * Ajax Dispatcher
	 *
	 */
	function ajaxCheck() {
		if ( isset($_GET['st_action']) && $_GET['st_action'] == 'helper_js_collection' )  {
			$this->ajaxLocalTags();
		}
	}
	
	/**
	 * Display a javascript collection for autocompletion script !
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxLocalTags() {
		status_header( 200 ); // Send good header HTTP
		header("Content-Type: text/plain; charset=" . get_bloginfo('charset'));
		
		$taxonomy = 'post_tag';
		if ( isset($_REQUEST['taxonomy']) && taxonomy_exists($_REQUEST['taxonomy']) ) {
			$taxonomy = $_REQUEST['taxonomy'];
		}
		
		if ( (int) wp_count_terms($taxonomy, 'ignore_empty=false') == 0 ) { // No tags to suggest
			exit();
		}
		
		// Prepare search
		$search = ( isset($_GET['q']) ) ? trim(stripslashes($_GET['q'])) : '';
		
		// Get all terms, or filter with search
		$terms = $this->getTermsForAjax( $taxonomy, $search );
		if ( empty($terms) || $terms == false ) {
			exit();
		}
		
		// Format terms
		foreach ( (array) $terms as $term ) {
			$term->name = stripslashes($term->name);
			$term->name = str_replace( array("\r\n", "\r", "\n"), '', $term->name );
			
			echo "$term->term_id|$term->name\n";
		}
		
		exit();
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
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		if ( !isset($options['autocomplete_min']) )
			$options['autocomplete_min'] = 0;
		?>
		<p>
			<?php if ( isset($options['autocomplete_type']) && $options['autocomplete_type'] == 'textarea' ) : ?>
				<textarea class="widefat" name="adv-tags-input" id="adv-tags-input" rows="3" cols="5"><?php echo $this->getTermsToEdit( 'post_tag', $post->ID ); ?></textarea>
			<?php else : ?>
				<input type="text" class="widefat" name="adv-tags-input" id="adv-tags-input" value="<?php echo esc_attr($this->getTermsToEdit( 'post_tag', $post->ID )); ?>" />
			<?php endif; ?>
			
			<?php _e('Separate tags with commas', 'simpletags'); ?>
		</p>
		<script type="text/javascript">
			<!--
			initAutoComplete( '#adv-tags-input', '<?php echo admin_url("admin-ajax.php?action=simpletags&st_action=helper_js_collection"); ?>', <?php echo (int) $options['autocomplete_min']; ?> );
			-->
		</script>
		<?php
	}
	
	/**
	 * Function called on auto terms page
	 *
	 * @param string $taxonomy 
	 * @return void
	 * @author Amaury Balmer
	 */
	function autoTermsJavaScript( $taxonomy = '' ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		if ( !isset($options['autocomplete_min']) )
			$options['autocomplete_min'] = 0;
		?>
		<script type="text/javascript">
			<!--
			initAutoComplete( '#auto_list', "<?php echo admin_url('admin-ajax.php?action=simpletags&st_action=helper_js_collection&taxonomy='.$taxonomy); ?>", <?php echo (int) $options['autocomplete_min']; ?> );
			-->
		</script>
		<?php
	}
	
	/**
	 * Function called on manage terms page
	 *
	 * @param string $taxonomy 
	 * @return void
	 * @author Amaury Balmer
	 */
	function manageTermsJavaScript( $taxonomy = '' ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		if ( !isset($options['autocomplete_min']) )
			$options['autocomplete_min'] = 0;
		?>
		<script type="text/javascript">
			<!--
			initAutoComplete( '.autocomplete-input', "<?php echo admin_url('admin-ajax.php?action=simpletags&st_action=helper_js_collection&taxonomy='.$taxonomy); ?>", <?php echo (int) $options['autocomplete_min']; ?> );
			-->
		</script>
		<?php
	}
	
	/**
	 * Function called on mass terms page
	 *
	 * @param string $taxonomy 
	 * @return void
	 * @author Amaury Balmer
	 */
	function massTermsJavascript( $taxonomy = '' ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		if ( !isset($options['autocomplete_min']) )
			$options['autocomplete_min'] = 0;
		?>
		<script type="text/javascript">
			<!--
			initAutoComplete( '.autocomplete-input', "<?php echo admin_url('admin-ajax.php?action=simpletags&st_action=helper_js_collection&taxonomy='.$taxonomy); ?>", <?php echo (int) $options['autocomplete_min']; ?> );
			-->
		</script>
		<?php
	}
}
?>