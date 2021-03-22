jQuery(document).ready(function () {
  if (stHelperClickTagsL10n.state === 'hide') {
    // Display initial link
    jQuery('#st-clicks-tags .inside').html('' + stHelperClickTagsL10n.search_box + ' <a href="#st_click_tags" id="open_clicktags">' + stHelperClickTagsL10n.show_txt + '</a><span id="close_clicktags">' + stHelperClickTagsL10n.hide_txt + '</span><div class="container_clicktags"></div>')
  } else {
    jQuery('#st-clicks-tags .inside').html('' + stHelperClickTagsL10n.search_box + ' <a href="#st_click_tags" id="open_clicktags">' + stHelperClickTagsL10n.show_txt + '</a><span id="close_clicktags">' + stHelperClickTagsL10n.hide_txt + '</span><div class="container_clicktags"></div>')
  }

  // Take current post ID
  current_post_id = jQuery('#post_ID').val()

  if (stHelperClickTagsL10n.state === 'show') {
    load_click_tags()
  }

  // Show click tags
  jQuery('#open_clicktags').click(function (event) {
    event.preventDefault()
    load_click_tags()
    return false
  })
})

function load_click_tags(search = '') {
  if (search) {
    jQuery(".click-tag-search-box").css("background", "url(" + stHelperClickTagsL10n.search_icon + ") no-repeat 99%");
  }
  jQuery('#st-clicks-tags .container_clicktags')
    .fadeIn('slow')
    .load(ajaxurl + '?action=simpletags&stags_action=click_tags&post_id=' + current_post_id+'&q=' + encodeURI(search), function () {
      jQuery('#st-clicks-tags .container_clicktags span').click(function (event) {
        event.preventDefault();
        addTag(this.innerHTML)
        jQuery(this).addClass('used_term')
      })

      jQuery('#open_clicktags').hide();
      jQuery('#close_clicktags').show();
      jQuery(".click-tag-search-box").css("background", "");
    })
}

//inititiate click tags search when user start typying
    //setup before functions
    var typingTimer;                //timer identifier
    var doneTypingInterval = 500;  //time in ms

    //on keyup, start the countdown
    jQuery(document).on('keyup', '.click-tag-search-box', function () {
        clearTimeout(typingTimer);
            typingTimer = setTimeout(doneTyping, doneTypingInterval);
    });

    //user is "finished typing," do something
    function doneTyping() {
      load_click_tags(jQuery('.click-tag-search-box').val());
    }