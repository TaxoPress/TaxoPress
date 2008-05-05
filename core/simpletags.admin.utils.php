<?php
Class SimpleTags_UtilsAdmin {
	/**
	 * Reset to default options
	 *
	 */
	function resetToDefaultOptions() {
		update_option($this->db_options, $this->default_options);
		$this->options = $this->default_options;
	}	
	
	/**
	 * trim and remove empty element
	 *
	 * @param string $element
	 * @return string
	 */
	function deleteEmptyElement( &$element ) {
		$element = trim($element);
		if ( !empty($element) ) {
			return $element;
		}
	}
	
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter() {
		?>
		<p class="footer_st"><?php printf(__('&copy; Copyright 2007 <a href="http://www.herewithme.fr/" title="Here With Me">Amaury Balmer</a> | <a href="http://wordpress.org/extend/plugins/simple-tags">Simple Tags</a> | Version %s', 'simpletags'), $this->version); ?></p>
		<?php
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage() {
		if ( $this->message != '') {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}

		if ( $message ) {
		?>
			<div id="message" class="<?php echo ($status != '') ? $status :'updated'; ?> fade">
				<p><strong><?php echo $message; ?></strong></p>
			</div>
		<?php
		}
	}	

	/**
	 * Print link to ST File CSS and addTag javascript function
	 *
	 */
	function helperHeaderST() {
		echo '<link rel="stylesheet" href="'.$this->info['install_url'].'/inc/css/simple-tags.admin.css?ver='.$this->version.'" type="text/css" />';
		echo '<script type="text/javascript" src="'.$this->info['install_url'].'/inc/js/helper-add-tags.js?ver='.$this->version.'"></script>';
	}
}
?>