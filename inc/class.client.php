<?php
class SimpleTags_Client {
	/**
	 * PHP4 constructor - Initialize Simple Tags client
	 *
	 * @return SimpleTags
	 */
	function SimpleTags_Client() {
		global $simple_tags;
		
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Add pages in WP_Query
		if ( $options['use_tag_pages'] == 1 ) {
			add_action( 'init', array(&$this, 'registerTagsForPage'), 11 );
		}
		
		// Call autolinks ?
		if ( $options['auto_link_tags'] == '1' ) {
			require( STAGS_DIR . '/inc/class.client.autolinks.php');
			$simple_tags['client-autolinks'] = new SimpleTags_Client_Autolinks();
		}
		
		// Call autolinks ?
		if ( $options['auto_link_tags'] == '1' ) {
			require( STAGS_DIR . '/inc/class.client.autoterms.php');
			$simple_tags['client-autoterms'] = new SimpleTags_Client_Autoterms();
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
		register_taxonomy_for_object_type( 'post_tag', 'page' );
	}
	
	/**
	 * Randomize an array and keep association
	 *
	 * @param array $array
	 * @return boolean
	 */
	function randomArray( &$array ) {
		if ( !is_array($array) || empty($array) ) {
			return false;
		}
		
		$keys = array_keys($array);
		shuffle($keys);
		foreach( (array) $keys as $key ) {
			$new[$key] = $array[$key];
		}
		$array = $new;
		
		return true;
	}
	
	/**
	 * Build rel for tag link
	 *
	 * @return string
	 */
	function buildRel() {
		global $wp_rewrite;
		$rel = ( is_object($wp_rewrite) && $wp_rewrite->using_permalinks() ) ? 'tag' : ''; // Tag ?
		if ( !empty($rel) ) {
			$rel = 'rel="' . $rel . '"'; // Add HTML Tag
		}
		
		return $rel;
	}
}
?>