<?php
class SimpleTags_Admin_ClickTags extends SimpleTags_Admin {
	/**
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function SimpleTags_Admin_ClickTags() {
		global $pagenow;
		
		// Ajax action, JS Helper and admin action
		add_action('wp_ajax_'.'simpletags', array(&$this, 'ajaxCheck'));
		
		// Box for post/page
		add_action('admin_menu', array(&$this, 'helperClickTags'), 1);
		
		wp_register_script('st-helper-click-tags', STAGS_URL.'/inc/js/helper-click-tags.min.js', array('jquery', 'st-helper-add-tags'), STAGS_VERSION);
		wp_localize_script('st-helper-click-tags', 'stHelperClickTagsL10n', array( 'show_txt' => __('Display click tags', 'simpletags'), 'hide_txt' => __('Hide click tags', 'simpletags') ) );
		
		// Register location
		$wp_post_pages = array('post.php', 'post-new.php');
		$wp_page_pages = array('page.php', 'page-new.php');
		
		// Helper for posts/pages
		if ( in_array($pagenow, $wp_post_pages) || ( in_array($pagenow, $wp_page_pages) && is_page_have_tags() ) ) {
			wp_enqueue_script('st-helper-click-tags');
		}
	}
	
	/**
	 * Register metabox
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function helperClickTags() {
		add_meta_box('st-clicks-tags', __('Click tags', 'simpletags'), array(&$this, 'boxClickTags'), 'post', 'advanced', 'core');
		if ( is_page_have_tags() )
			add_meta_box('st-clicks-tags', __('Click tags', 'simpletags'), array(&$this, 'boxClickTags'), 'page', 'advanced', 'core');
	}
	
	/**
	 * Put default HTML for people without JS
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function boxClickTags() {
		echo $this->getDefaultContentBox();
	}
	
	/**
	 * Ajax Dispatcher
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxCheck() {
		if ( isset($_GET['st_action']) && $_GET['st_action'] == 'click_tags' )  {
			$this->ajaxClickTags();
		}
	}
	
	/**
	 * Display a span list for click tags
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	function ajaxClickTags() {
		status_header( 200 ); // Send good header HTTP
		header("Content-Type: text/javascript; charset=" . get_bloginfo('charset'));
		
		if ((int) wp_count_terms('post_tag', 'ignore_empty=false') == 0 ) { // No tags to suggest
			echo '<p>'.__('No terms in your WordPress database.', 'simpletags').'</p>';
			exit();
		}
		
		// Prepare search
		$search = ( isset($_GET['q']) ) ? trim(stripslashes($_GET['q'])) : '';
		
		// Get options
		$options = get_option( STAGS_OPTIONS_NAME );
		
		// Order tags before selection (count-asc/count-desc/name-asc/name-desc/random)
		$options['order_click_tags'] = strtolower($options['order_click_tags']);
		$order_by = $order = '';
		switch ( $options['order_click_tags'] ) {
			case 'count-asc':
				$order_by = 'tt.count';
				$order = 'ASC';
				break;
			case 'random':
				$order_by = 'RAND()';
				$order = '';
				break;
			case 'count-desc':
				$order_by = 'tt.count';
				$order = 'DESC';
				break;
			case 'name-desc':
				$order_by = 't.name';
				$order = 'DESC';
				break;
			default : // name-asc
				$order_by = 't.name';
				$order = 'ASC';
			break;
		}
		
		// Get all terms, or filter with search
		$terms = $this->getTermsForAjax( 'post_tag', $search, $order_by, $order );
		if ( empty($terms) || $terms == false ) {
			echo '<p>'.__('No results from your WordPress database.', 'simpletags').'</p>';
			exit();
		}
		
		foreach ( (array) $terms as $term ) {
			echo '<span class="local">'.esc_html(stripslashes($term->name)).'</span>'."\n";
		}
		echo '<div class="clear"></div>';

		exit();
	}
}
?>