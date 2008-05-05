<?php
Class SimpleTagsAdmin extends SimpleTags {
	var $admin_base_url = '';

	// Error management
	var $message = '';
	var $status = '';
	
	var $info = array();

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return SimpleTagsAdmin
	 */
	function SimpleTagsAdmin() {
		$this->info = parent::info();

		// 8. Admin URL and Pagination
		$this->admin_base_url = $this->info['siteurl'] . '/wp-admin/admin.php?page=';

		// 9. Admin Capabilities
		$role = get_role('administrator');
			if( !$role->has_cap('simple_tags') ) {
				$role->add_cap('simple_tags');
			}
			if( !$role->has_cap('admin_simple_tags') ) {
				$role->add_cap('admin_simple_tags');
			}
		$role = get_role('editor');
			if( !$role->has_cap('simple_tags') ) {
				$role->add_cap('simple_tags');
			}

		// 10. Admin menu
		add_action('admin_notices', array(&$this, 'displayMessage'));		

		// 11. Ajax action, JS Helper and admin action
		add_action('init', array(&$this, 'ajaxCheck'));
		
		// 17. Helper JS & jQuery & Prototype
		global $pagenow;
		$wp_pages = array('post.php', 'post-new.php', );
		if ( $this->options['use_tag_pages'] == 1 ) {
			$wp_pages[] = 'page.php';
			$wp_pages[] = 'page-new.php';
		}
		$st_pages = array('st_manage', 'st_mass_tags', 'st_auto', 'st_options');
		if ( in_array($pagenow, $wp_pages) || in_array($_GET['page'], $st_pages) ) {
			add_action('admin_head', array(&$this, 'helperHeaderST'));
			wp_enqueue_script('jquery');
			wp_enqueue_script('prototype');
			
			if ( $this->options['use_autocompletion'] == 1 ) {
				if ( $pagenow == 'post.php' || $pagenow == 'post-new.php' ) {
					add_action('admin_head', array(&$this, 'remplaceTagsHelper'));
				}
				if ( !in_array($_GET['page'], $st_pages) ) {
					add_action('admin_head', array(&$this, 'helperBCompleteJS'));
				}
			}
		}

		// 18. Helper Bcomplete JS


		return;
	}


}
?>