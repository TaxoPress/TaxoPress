<?php
Class SimpleTags_Admin_AutoCompletion {
	
	function SimpleTags_Admin_AutoCompletion() {
		add_action('dbx_page_advanced', array(&$this, 'helperBCompleteJS'));
		add_action('save_post', array(&$this, 'saveTagsOldInput'));
		add_action('publish_post', array(&$this, 'saveTagsOldInput'));	
	}
	
	function remplaceTagsHelper() {
		global $post_ID;
		?>
	   	<script type="text/javascript">
		    // <![CDATA[
			jQuery(document).ready(function() {
				jQuery("#tagsdiv").after('<div id="old-tagsdiv" class="postbox <?php echo postbox_classes('old-tagsdiv', 'post'); ?>"><h3><?php echo _e('Tags <small>(separate multiple tags with commas: cats, pet food, dogs)</small>', 'simpletags'); ?></h3><div class="inside"><input type="text" name="old_tags_input" id="old_tags_input" size="40" tabindex="3" value="<?php echo js_escape(get_tags_to_edit( $post_ID )); ?>" /><div id="st_click_tags" class="container_clicktags"></div></div></div>');
			});
			// ]]>
	    </script>
	    <style type="text/css">
	    	#tagsdiv { display:none; }
	    </style>	   
		<?php
	}

	function saveTagsOldInput( $post_id = null, $post_data = null ) {
		$object = get_post($post_id);
		if ( $object == false || $object == null ) {
			return false;
		}
		
		if ( isset($_POST['old_tags_input']) ) {
			// Post data
			$tags = stripslashes($_POST['old_tags_input']);
			
			// Trim data
			$tags = trim(stripslashes($tags));

			// String to array
			$tags = explode( ',', $tags );

			// Remove empty and trim tag
			$tags = array_filter($tags, array(&$this, 'deleteEmptyElement'));

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
	 * Helper type-ahead (single post)
	 *
	 */
	function helperBCompleteJS( $id = 'old_tags_input' ) {
		$tags = (int) wp_count_terms('post_tag');		
		if ( $tags == 0 ) { // If no tags => exit !
			return;
		}
		?>
		<script type="text/javascript" src="<?php echo $this->info['siteurl'] ?>/wp-admin/admin.php?st_ajax_action=helper_js_collection&ver=<?php echo $this->version; ?>"></script>
		<script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/bcomplete.js?ver=<?php echo $this->version; ?>"></script>
		<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/css/bcomplete.css?ver=<?php echo $this->version; ?>" />
		<?php if ( 'rtl' == get_bloginfo( 'text_direction' ) ) : ?>
			<link rel="stylesheet" type="text/css" href="<?php echo $this->info['install_url'] ?>/inc/css/bcomplete-rtl.css?ver=<?php echo $this->version; ?>" />
		<?php endif; ?>
		<script type="text/javascript">
		// <![CDATA[
			jQuery(document).ready(function() {
				if ( document.getElementById('<?php echo ( empty($id) ) ? 'old_tags_input' : $id; ?>') ) {
					var tags_input = new BComplete('<?php echo ( empty($id) ) ? 'old_tags_input' : $id; ?>');
					tags_input.setData(collection);
				}
			});
		// ]]>
		</script>
		<?php
	}
	
	
}
?>