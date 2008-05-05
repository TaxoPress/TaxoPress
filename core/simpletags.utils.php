<?php
Class SimpleTags_Utils {
	/**
	 * Sort an array without accent for naturel order :)
	 *
	 * @param string $a
	 * @param string $b
	 * @return boolean
	 */
	function uksortByName( $a = '', $b = '' ) {
		return strnatcasecmp( remove_accents($a), remove_accents($b) );
	}
	
	function loadOptionsFromDB( $db_options = '', $default_options = array() ) {
		// Get options from WP options
		$options_from_table = get_option( $db_options );

		// Update default options by getting not empty values from options table
		foreach( (array) $default_options as $default_options_name => $default_options_value ) {
			if ( !is_null($options_from_table[$default_options_name]) ) {
				if ( is_int($default_options_value) ) {
					$default_options[$default_options_name] = (int) $options_from_table[$default_options_name];
				} else {
					$default_options[$default_options_name] = $options_from_table[$default_options_name];
				}
			}
		}
		
		// Clean variables
		$options_from_table = array();
		unset($options_from_table);
		
		return $default_options;
	}
	
	function buildInfo() {
		// Determine installation path & url
		$path = str_replace('\\','/',dirname(__FILE__));
		$path = substr($path, strpos($path, 'plugins') + 8, strlen($path));

		$info['siteurl'] = get_option('siteurl');
		if ( SimpleTags_Utils::isMuPlugin() ) {
			$info['install_url'] = $info['siteurl'] . '/wp-content/mu-plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/mu-plugins';

			if ( $path != 'mu-plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		} else {
			$info['install_url'] = $info['siteurl'] . '/wp-content/plugins';
			$info['install_dir'] = ABSPATH . 'wp-content/plugins';

			if ( $path != 'plugins' ) {
				$info['install_url'] .= '/' . $path;
				$info['install_dir'] .= '/' . $path;
			}
		}

		// Set informations
		return array(
			'home' => get_option('home'),
			'siteurl' => $info['siteurl'],
			'install_url' => $info['install_url'],
			'install_dir' => $info['install_dir']
		);
	}
	
	/**
	 * Test if local installation is mu-plugin or a classic plugin
	 *
	 * @return boolean
	 */
	function isMuPlugin() {
		if ( strpos(dirname(__FILE__), 'mu-plugins') ) {
			return true;
		}
		return false;
	}
	
}
?>