// Switches option section
jQuery(document).ready(function($) {
	// Hide all by default
	$('.group').hide();
	
	// Display active group
	var activetab = '';
	if (typeof(localStorage) != 'undefined') {
		activetab = localStorage.getItem("activetab");
	}
	if (activetab != '' && $(activetab).length) {
		$(activetab).fadeIn();
	} else {
		$('.group:first').fadeIn();
	}
	console.log(activetab);

	if (activetab != '' && $(activetab + '-tab').length) {
		$(activetab + '-tab').addClass('nav-tab-active');
	} else {
		$('.nav-tab-wrapper a:first').addClass('nav-tab-active');
	}

	$('.nav-tab-wrapper a').click(function(evt) {
		$('.nav-tab-wrapper a').removeClass('nav-tab-active');
		$(this).addClass('nav-tab-active').blur();
		var clicked_group = $(this).attr('href');
		if (typeof(localStorage) != 'undefined') {
			localStorage.setItem("activetab", $(this).attr('href'));
		}
		$('.group').hide();
		$(clicked_group).fadeIn();
		evt.preventDefault();
	});
});

jQuery(document).ready(function() {
	jQuery('input#cloud_max_color')
		.ready(function() {
		cloudMaxColor()
	})
		.click(function() {
		cloudMaxColor()
	})
		.blur(function() {
		cloudMaxColor()
	})
		.change(function() {
		cloudMaxColor()
	})
		.focus(function() {
		cloudMaxColor()
	});

	jQuery('input#cloud_min_color')
		.ready(function() {
		cloudMinColor()
	})
		.click(function() {
		cloudMinColor()
	})
		.blur(function() {
		cloudMinColor()
	})
		.change(function() {
		cloudMinColor()
	})
		.focus(function() {
		cloudMinColor()
	});
});

function cloudMaxColor() {
	jQuery('div.cloud_max_color').css({
		backgroundColor: jQuery('input#cloud_max_color').val()
	});
}

function cloudMinColor() {
	jQuery('div.cloud_min_color').css({
		backgroundColor: jQuery('input#cloud_min_color').val()
	});
}	