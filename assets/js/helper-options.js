// Switches option section
jQuery(document).ready(function() {
    // Hide all by default
    jQuery('.group').hide();

    // Display active group
    var activetab = '';
    if (typeof(localStorage) != 'undefined' && localStorage != null) {
        activetab = localStorage.getItem("activetab");
    }
    if (activetab !== '' && jQuery(activetab).length) {
        jQuery(activetab).fadeIn();
    } else {
        jQuery('.group:first').fadeIn();
    }
    //console.log(activetab);

    if (activetab !== '' && jQuery(activetab + '-tab').length) {
        jQuery(activetab + '-tab').addClass('nav-tab-active');
    } else {
        jQuery('.nav-tab-wrapper a:first').addClass('nav-tab-active');
    }

    jQuery('.nav-tab-wrapper a').click(function(evt) {
        jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active').blur();
        var clicked_group = jQuery(this).attr('href');
        if (typeof(localStorage) != 'undefined' && localStorage != null) {
            localStorage.setItem("activetab", jQuery(this).attr('href'));
        }
        jQuery('.group').hide();
        jQuery(clicked_group).fadeIn();
        evt.preventDefault();
    });
});

jQuery(document).ready(function() {
    jQuery('#input#cloud_max_color').on('ready click blur change focus', function(e) {
        cloudMaxColor();
    });

    jQuery('#input#cloud_min_color').on('ready click blur change focus', function(e) {
        cloudMinColor();
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