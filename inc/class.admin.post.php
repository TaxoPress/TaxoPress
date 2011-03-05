<?php
class SimpleTags_Admin_Post_Settings extends SimpleTags_Admin {
	/**
	 * Constructor, PHP4 mode
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function SimpleTags_Admin_Post_Settings() {
		// Save tags from advanced input
		add_action( 'save_post', 	array(&$this, 'savePostSettings'), 10, 2 );
		
		// Box for advanced tags
		add_action( 'add_meta_boxes', array(&$this, 'registerMetaBox'), 10, 1 );
	}
	
	/**
	 * Register a new box for simple tags settings
	 *
	 * @param string $post_type 
	 * @return void
	 * @author Amaury Balmer
	 */
	function registerMetaBox( $post_type ) {
		add_meta_box('simpletags-settings', __('Simple Tags - Settings', 'simpletags'), array(&$this, 'metaBoxSettings'), $post_type, 'side', 'low' );
	}
	
	/**
	 * Build HTML of form
	 *
	 * @param object $post 
	 * @return void
	 * @author Amaury Balmer
	 */
	function metaBoxSettings( $post ) {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Get auto options
		$auto_options = get_option( STAGS_OPTIONS_NAME_AUTO );
		
		// Auto terms for this CPT ?
		if ( isset($options['active_autotags']) && $options['active_autotags'] == 1 && isset($auto_options[$post->post_type]) && !empty($auto_options[$post->post_type]) ) {
			$meta_value = get_post_meta( $post->ID, '_exclude_autotags', true );
			echo '<p>' . "\n";
				echo '<label><input type="checkbox" name="exclude_autotags" value="true" '.checked($meta_value, true, false).' /> '.__('Disable auto tags ?', 'simpletags').'</label><br />' . "\n";
			echo '</p>' . "\n";
			echo '<input type="hidden" name="_meta_autotags" value="true" />';
		} 
		
		$taxonomies = get_object_taxonomies( $post->post_type );
		if( isset($options['auto_link_tags']) && $options['auto_link_tags'] == '1' && in_array('post_tag', $taxonomies) ) {
			$meta_value = get_post_meta( $post->ID, '_exclude_autolinks', true );
			echo '<p>' . "\n";
				echo '<label><input type="checkbox" name="exclude_autolinks" value="true" '.checked($meta_value, true, false).' /> '.__('Disable auto links ?', 'simpletags').'</label><br />' . "\n";
			echo '</p>' . "\n";
			echo '<input type="hidden" name="_meta_autolink" value="true" />';
		}
	}
	
	/**
	 * Save this settings in post meta, delete if no exclude, clean DB :)
	 *
	 * @param integer $object_id 
	 * @param object $object 
	 * @return void
	 * @author Amaury Balmer
	 */
	function savePostSettings( $object_id = 0, $object = null ) {
		if ( !isset($object) || $object == null ) {
			$object = get_post( $object_id );
		}
	
		if ( isset($_POST['_meta_autotags']) && $_POST['_meta_autotags'] == 'true' ) {
			if ( isset($_POST['exclude_autotags']) ) {
				update_post_meta( $object->ID, '_exclude_autotags', true );
			} else {
				delete_post_meta( $object->ID, '_exclude_autotags' );
			}
		}
		
		if( isset($_POST['_meta_autolink']) && $_POST['_meta_autolink'] == 'true' ) {
			if ( isset($_POST['exclude_autolinks']) ) {
				update_post_meta( $object->ID, '_exclude_autolinks', true );
			} else {
				delete_post_meta( $object->ID, '_exclude_autolinks' );
			}
		}
	}
}
?>