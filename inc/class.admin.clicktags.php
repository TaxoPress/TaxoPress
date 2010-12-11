<?php
class SimpleTags_Admin_ClickTags extends SimpleTags_Admin {
	
	function SimpleTags_Admin_ClickTags() {
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Box for post
		add_action('admin_menu', array(&$this, 'helperClickTags_Post'), 1);
		
		// Box for Page
		if ( $options['use_tag_pages'] == 1 ) {
			add_action('admin_menu', array(&$this, 'helperClickTags_Page'), 1);
		}
		
		wp_register_script('st-helper-click-tags', 		STAGS_URL.'/inc/js/helper-click-tags.min.js', array('jquery', 'st-helper-add-tags'), STAGS_VERSION);
		wp_localize_script('st-helper-click-tags', 'stHelperClickTagsL10n', array( 'site_url' => admin_url('admin.php'), 'show_txt' => __('Display click tags', 'simpletags'), 'hide_txt' => __('Hide click tags', 'simpletags') ) );
		
		// Register location
		global $pagenow;
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages
		if ( in_array($pagenow, $wp_post_pages) || (in_array($pagenow, $wp_page_pages) && $options['use_tag_pages'] == 1 ) ) {
			wp_enqueue_script('st-helper-click-tags');
		}
	}
	
	/**
	 * Click tags
	 *
	 */
	function helperClickTags_Page() {
		add_meta_box('st-clicks-tags', __('Click tags', 'simpletags'), array(&$this, 'boxClickTags'), 'page', 'advanced', 'core');
	}
	
	function helperClickTags_Post() {
		add_meta_box('st-clicks-tags', __('Click tags', 'simpletags'), array(&$this, 'boxClickTags'), 'post', 'advanced', 'core');
	}
	
	function boxClickTags() {
		echo $this->getDefaultContentBox();
	}
	
}
?>