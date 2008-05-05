<?php
Class SimpleTags_TagForPage {
	
	function SimpleTags_TagForPage() {
		// Remove default taxonomy
		global $wp_taxonomies;
		unset($wp_taxonomies['post_tag']);
		
		// Add the same taxonomy with an another callback who allow page and post
		register_taxonomy( 'post_tag', 'post', array('hierarchical' => false, 'update_count_callback' => array(&$this, '_update_post_and_page_term_count')) );
		add_filter('posts_where', array(&$this, 'prepareQuery'));
		
		if ( is_admin() ) {
			add_action('edit_page_form', array(&$this, 'helperTagsPage'), 1); // Tag input	
		}
	}
	
	/**
	 * Add page in tag search
	 *
	 * @param string $where
	 * @return string
	 */
	function prepareQuery( $where ) {
		if ( is_tag() ) {
			$where = str_replace('post_type = \'post\'', 'post_type IN(\'page\', \'post\')', $where);
		}
		return $where;
	}
	
	/**
	 * Update taxonomy counter for post AND page
	 *
	 * @param array $terms
	 */
	function _update_post_and_page_term_count( $terms ) {
		global $wpdb;
		foreach ( (array) $terms as $term ) {
			$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->term_relationships, $wpdb->posts WHERE $wpdb->posts.ID = $wpdb->term_relationships.object_id AND post_status = 'publish' AND post_type IN ('page', 'post') AND term_taxonomy_id = %d", $term ) );
			$wpdb->update( $wpdb->term_taxonomy, compact( 'count' ), array( 'term_taxonomy_id' => $term ) );
		}
		return true;
	}	
	
	/**
	 * Display tags input for page
	 *
	 */
	function helperTagsPage() {
		global $post_ID;
		?>
		<div id="old-tagsdiv" class="postbox <?php echo postbox_classes('old-tagsdiv', 'post'); ?>">
			<h3><?php _e('Tags <small>(separate multiple tags with commas: cats, pet food, dogs)</small>', 'simpletags'); ?></h3>
			<div class="inside">
				<input type="text" name="old_tags_input" id="old_tags_input" size="40" tabindex="3" value="<?php echo get_tags_to_edit( $post_ID ); ?>" />
				<div id="st_click_tags" class="container_clicktags"></div>
			</div>
		</div>
		<?php
	}
}
?>