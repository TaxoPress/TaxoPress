jQuery(document).ready(function() {
	jQuery("#old-tagsdiv h3").prepend('<div style="float:right;"><a href="#st_click_tags" id="clicktags">'+show_txt+'</a><a href="#st_click_tags" id="close_clicktags">'+hide_txt+'</a></div>');
	    		    		
	jQuery("a#clicktags").click(function() {					
		jQuery("#st_click_tags")
			.fadeIn('slow')
			.load( site_url + '/wp-admin/admin.php?st_ajax_action=click_tags', function(){
				jQuery("#st_click_tags span").click(function() { addTag(this.innerHTML); });
				jQuery("a#clicktags").hide();
				jQuery("a#close_clicktags").show();
			});						
		return false;
	});
	
	jQuery("a#close_clicktags").click(function() {	
		jQuery("#st_click_tags").fadeOut('slow', function() {
			jQuery("a#clicktags").show();
			jQuery("a#close_clicktags").hide();
		});
		return false;
	});
});