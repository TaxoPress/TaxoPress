<?php
Class SimpleTags_Admin_ClickTags {
	
	function SimpleTags_Admin_ClickTags() {
		add_action('edit_form_advanced', array(&$this, 'helperClickTags'), 1);
		add_action('edit_page_form', array(&$this, 'helperClickTags'), 1); // Options
	}
	
	function helperClickTags() {
		?>
	   	<script type="text/javascript">
	    // <![CDATA[
			var site_url = '<?php echo $this->info['siteurl']; ?>';
			var show_txt = '<?php _e('Display click tags', 'simpletags'); ?>';
			var hide_txt = '<?php _e('Hide click tags', 'simpletags'); ?>';
		// ]]>
	    </script>
	    <script type="text/javascript" src="<?php echo $this->info['install_url'] ?>/inc/js/helper-click-tags.js?ver=<?php echo $this->version; ?>""></script>  
		<?php
	}

}
?>