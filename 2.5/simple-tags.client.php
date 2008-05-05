<?php
Class SimpleTags {
	var $version = '2.0';
	var $info;


	/**
	 * PHP4 constructor - Initialize ST
	 *
	 * @return SimpleTags
	 */
	function SimpleTags() {	
		// Load info
		$this->info = SimpleTags_Utils::buildInfo();
		
		// Localization
		$locale = get_locale();
		if ( !empty( $locale ) ) {
			$mofile = str_replace('/2.5', '', $this->info['install_dir']).'/languages/simpletags-'.$locale.'.mo';
			load_textdomain('simpletags', $mofile);
		}

		// Options
		$default_options = array(
			'no_follow' => 0,
		);

		return true;
	}
	
	function info() {
		$vars = get_class_vars(__CLASS__);
		return $vars[strtoupper(__FUNCTION__)];
	}


}

// Init ST
$simple_tags = null;
function st_init() {
	global $simple_tags;
	$simple_tags = new SimpleTags();
	
	// Admin and XML-RPC
	if ( is_admin() || ( defined('XMLRPC_REQUEST') && XMLRPC_REQUEST ) ) {
		require(dirname(__FILE__).'/inc/simple-tags.admin.php');
		$simple_tags_admin = new SimpleTagsAdmin( $simple_tags->default_options, $simple_tags->version, $simple_tags->info );

		// Installation
		register_activation_hook(__FILE__, array(&$simple_tags_admin, 'installSimpleTags') );
	}

	// Templates functions
	require(dirname(__FILE__).'/inc/simple-tags.functions.php');

	// Widgets
	require(dirname(__FILE__).'/inc/simple-tags.widgets.php');
}
add_action('plugins_loaded', 'st_init');
?>
