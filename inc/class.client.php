<?php
class SimpleTags {
	var $options;
	
	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {
		// Options
		$this->options = (array) include( dirname(__FILE__) . '/default.options.php' );
		
		// Get options from WP options
		$options_from_table = get_option( STAGS_OPTIONS_NAME );
		
		// Update default options by getting not empty values from options table
		foreach( (array) $this->options as $key => $value ) {
			if ( isset($options_from_table[$key]) && !is_null($options_from_table[$key]) ) {
				$this->options[$key] = $options_from_table[$key];
			}
		}
		
		// Clean memory
		$options_from_table = array();
		unset($options_from_table, $value);
		
		// Add pages in WP_Query
		if ( $this->options['use_tag_pages'] == 1 ) {
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