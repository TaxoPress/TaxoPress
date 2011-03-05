<?php
class SimpleTags_Admin_Autocomplete extends SimpleTags_Admin {
	
	function SimpleTags_Admin_Autocomplete() {
		global $pagenow;
		
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
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
		
		// Register JS/CSS
		if ( isset($options['autocomplete_mode']) && $options['autocomplete_mode'] == 'jquery-autocomplete' ) {
			wp_register_script('jquery-bgiframe',			STAGS_URL.'/ressources/jquery.bgiframe.min.js', array('jquery'), '2.1.1');
			wp_register_script('jquery-autocomplete',		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.min.js', array('jquery', 'jquery-bgiframe'), '1.1');
			wp_register_script('st-helper-autocomplete', 	STAGS_URL.'/inc/js/helper-autocomplete.min.js', array('jquery', 'jquery-autocomplete'), STAGS_VERSION);	
			wp_register_style ('jquery-autocomplete', 		STAGS_URL.'/ressources/jquery.autocomplete/jquery.autocomplete.css', array(), '1.1', 'all' );
		} else {
			wp_register_script('protoculous-effects-shrinkvars', 	STAGS_URL.'/ressources/protomultiselect/protoculous-effects-shrinkvars.js', array(), '1.6.0.2-1.8.1');
			wp_register_script('textboxlist', 						STAGS_URL.'/ressources/protomultiselect/textboxlist.js', array('protoculous-effects-shrinkvars'), '0.2');
			wp_register_script('protomultiselect', 					STAGS_URL.'/ressources/protomultiselect/test.js', array('textboxlist'), '0.2');
			wp_register_script('st-helper-protomultiselect', 		STAGS_URL.'/inc/js/helper-protomultiselect.min.js', array('protomultiselect'), STAGS_VERSION);
			wp_register_style ('protomultiselect', 				STAGS_URL.'/ressources/protomultiselect/test.css', array(), '0.2', 'all' );
		}
		
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages and for Auto Tags, Mass Edit Tags and Manage tags !
		if ( (in_array($pagenow, $wp_post_pages) || ( in_array($pagenow, $wp_page_pages) && is_page_have_tags() )) || (isset($_GET['page']) && in_array( $_GET['page'], array('st_auto', 'st_mass_terms', 'st_manage') )) ) {
			if ( isset($options['autocomplete_mode']) && $options['autocomplete_mode'] == 'jquery-autocomplete' ) {
				wp_enqueue_script('st-helper-autocomplete');
				wp_enqueue_style ('jquery-autocomplete');
			} else {
				wp_enqueue_script('st-helper-protomultiselect');
				wp_enqueue_style ('protomultiselect');
			}
		}
	}
	
	/**
	 * Ajax Dispatcher
	 *
	 */
	function ajaxCheck() {
		if ( isset($_GET['st_action']) && $_GET['st_action'] == 'collection_jquery_autocomplete' )  {
			$this->ajaxjQueryAutoComplete();
		} elseif( isset($_GET['st_action']) && $_GET['st_action'] == 'collection_protomultiselect' ) {
			$this->ajaxProtoMultiSelect();
		}
	}
	
	/**
	 * Display a javascript collection for jquery autocomple script !
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxProtoMultiSelect() {
		status_header( 200 ); // Send good header HTTP
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
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
		$output = array();
		foreach ( (array) $terms as $term ) {
			$term->name = stripslashes($term->name);
			$term->name = str_replace( array("\r\n", "\r", "\n"), '', $term->name );
			
			$output[] = array( 'caption' => $term->name, 'value' => $term->term_id );
		}
		
		echo json_encode($output);
		exit();
	}
	
	/**
	 * Display a javascript collection for jquery autocomple script !
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxjQueryAutoComplete() {
		status_header( 200 ); // Send good header HTTP
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
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
		echo '<div id="wrap-old-input-tags">';
		if ( isset($options['autocomplete_mode']) && $options['autocomplete_mode'] == 'jquery-autocomplete' ) :
			?>
			<p>
				<input type="text" class="widefat" name="adv-tags-input" id="adv-tags-input" value="<?php echo esc_attr($this->getTermsToEdit( 'post_tag', $post->ID )); ?>" />
				<?php _e('Separate tags with commas', 'simpletags'); ?>
			</p>
			<script type="text/javascript">
				<!--
				initjQueryAutoComplete( '#adv-tags-input', '<?php echo admin_url("admin-ajax.php?action=simpletags&st_action=collection_jquery_autocomplete"); ?>', 300 );
				-->
			</script>
			<?php
		else :
			?>
			<p id="facebook-list" class="input-text">
				<input type="text" name="adv-tags-input" value="" id="facebook-demo" />
				<div id="facebook-auto">
					<div class="default"><?php _e('Type the name of an argentine writer you like', 'simpletags'); ?></div> 
					<ul class="feed">
						<?php
						$terms = wp_get_post_terms( $post->ID, 'post_tag' );
						foreach( $terms as $term ) {
							echo '<li value="'.$term->term_id.'">'.esc_html($term->name).'</li>';
						}
						?>
					</ul>
				</div>
			</p>
			<script type="text/javascript">
				<!--
				initProtoMultiSelect( 'facebook-demo', 'facebook-auto', '<?php echo admin_url("admin-ajax.php?action=simpletags&st_action=collection_protomultiselect"); ?>' );
				-->
			</script>
		<?php
		endif;
		echo '</div>';
	}
	
	/**
	 * Function called on auto terms page
	 *
	 * @param string $taxonomy 
	 * @return void
	 * @author Amaury Balmer
	 */
	function autoTermsJavaScript( $taxonomy = '' ) {
		?>
		<script type="text/javascript">
			<!--
			initjQueryAutoComplete( '#auto_list', "<?php echo admin_url('admin-ajax.php?action=simpletags&st_action=collection_jquery_autocomplete&taxonomy='.$taxonomy);; ?>", 300 );
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
		?>
		<script type="text/javascript">
			<!--
			initjQueryAutoComplete( '.autocomplete-input', "<?php echo admin_url('admin.php?st_ajax_action=collection_jquery_autocomplete&taxonomy='.$taxonomy); ?>", 300 );
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
		?>
		<script type="text/javascript">
			<!--
			initjQueryAutoComplete( '.autocomplete-input', '<?php echo admin_url('admin-ajax.php') .'?action=simpletags&st_action=collection_jquery_autocomplete&taxonomy='.$taxonomy; ?>', 300 );
			-->
		</script>
		<?php
	}
}
?>