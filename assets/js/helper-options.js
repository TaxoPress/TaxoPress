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

jQuery(document).ready(function($) {
    
    $('.text-color').wpColorPicker();

    $('.simple-tags-dismiss-rating').on('click', function(e) {
      e.preventDefault();

      localStorage.setItem('simple-tags-dismiss-rating', true);
      $('.simple-tags-review-box').hide();

      return false;
    });

    if (localStorage.getItem('simple-tags-dismiss-rating')) {
      $('.simple-tags-review-box').hide();
    }
});

// -------------------------------------------------------------
//   Prevent negative number of input type number
// -------------------------------------------------------------
jQuery(document).on('change paste keyup keydown', 'input[type="number"]', function (e) {
    if (e.which === 69
      || e.which === 189) {
        e.preventDefault();
    }
});

// -------------------------------------------------------------
//   Prevent null of input type number
// -------------------------------------------------------------
jQuery(document).on('change paste', 'input[type="number"]', function (e) {
    if (jQuery(this).val() === '') {
        jQuery(this).val(0);
    }
});
