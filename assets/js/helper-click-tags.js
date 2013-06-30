jQuery(document).ready(function() {
	// Display initial link
	jQuery("#st-clicks-tags .inside").html('<a href="#st_click_tags" id="open_clicktags">'+stHelperClickTagsL10n.show_txt+'</a><a href="#st_click_tags" id="close_clicktags">'+stHelperClickTagsL10n.hide_txt+'</a><div class="container_clicktags"></div>');
	
	// Show click tags
	jQuery("#open_clicktags").click(function(event) {
		event.preventDefault();
	
		jQuery("#st-clicks-tags .container_clicktags")
			.fadeIn('slow')
			.load( ajaxurl + '?action=simpletags&st_action=click_tags', function() {
				jQuery("#st-clicks-tags .container_clicktags span").click(function(event) {
					event.preventDefault();
					addTag(this.innerHTML);
				});
				jQuery("#open_clicktags").hide();
				jQuery("#close_clicktags").show();
			});
		
		return false;
	});
	
	// Hide click tags
	jQuery("#close_clicktags").click(function(event) {
		event.preventDefault();
		
		jQuery("#st-clicks-tags .container_clicktags").fadeOut('slow', function() {
			jQuery("#open_clicktags").show();
			jQuery("#close_clicktags").hide();
		});
		return false;
	});
});