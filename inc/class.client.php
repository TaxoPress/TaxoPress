<?php
class SimpleTags {
	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Add pages in WP_Query
		if ( $options['use_tag_pages'] == 1 ) {
			add_action( 'init', array(&$this, 'registerTagsForPage'), 11 );
		}
		
		return true;
	}
	
	/**
	 * Register taxonomy post_tags for page post type
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function registerTagsForPage() {
		register_taxonomy_for_object_type( 'post_tags', 'page' );
	}
}
?>