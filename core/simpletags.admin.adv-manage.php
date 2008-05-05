<?php
Class SimpleTags_Admin_AdvManage {
	
	function SimpleTags_Admin_AdvManage() {
		
	}

	/**
	 * Add WP admin menu for Tags
	 *
	 */
	function adminMenu() {	
		add_management_page( __('Simple Tags: Manage Tags', 'simpletags'), __('Manage Tags', 'simpletags'), 'simple_tags', 'st_manage', array(&$this, 'pageManageTags'));
	}
	
	/**
	 * Clean database - Remove empty terms
	 *
	 */
	function cleanDatabase() {
		global $wpdb;

		// Counter
		$counter = 0;

		// Get terms id empty
		$terms_id = $wpdb->get_col("SELECT term_id FROM {$wpdb->terms} WHERE name IN ('', ' ', '  ', '&nbsp;') GROUP BY term_id");
		if ( empty($terms_id) ) {
			$this->message = __('Nothing to muck. Good job !', 'simpletags');
			return;
		}
		
		// Prepare terms SQL List
		$terms_list = "'" . implode("', '", $terms_id) . "'";

		// Remove term empty
		$counter += $wpdb->query("DELETE FROM {$wpdb->terms} WHERE term_id IN ( {$terms_list} )");

		// Get term_taxonomy_id from term_id on term_taxonomy table
		$tts_id = $wpdb->get_col("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} ) GROUP BY term_taxonomy_id");

		if ( !empty($tts_id) ) {
			// Clean term_taxonomy table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_taxonomy} WHERE term_id IN ( {$terms_list} )");

			// Prepare terms SQL List
			$tts_list = "'" . implode("', '", $tts_id) . "'";

			// Clean term_relationships table
			$counter += $wpdb->query("DELETE FROM {$wpdb->term_relationships} WHERE term_taxonomy_id IN ( {$tts_list} )");
		}
		
		// Delete cache
		clean_term_cache($terms_id, array('category', 'post_tag'));
		clean_object_term_cache($tts_list, 'post');		

		$this->message = sprintf(__('%s rows deleted. WordPress DB is clean now !', 'simpletags'), $counter);
		return;
	}
	
}
?>