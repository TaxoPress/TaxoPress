jQuery(document).ready(function () {
  if (stHelperClickTagsL10n.state === 'hide') {
    // Display initial link
    jQuery('#st-clicks-tags .inside').html('<a href="#st_click_tags" id="open_clicktags">' + stHelperClickTagsL10n.show_txt + '</a><a href="#st_click_tags" id="close_clicktags">' + stHelperClickTagsL10n.hide_txt + '</a><div class="container_clicktags"></div>')
  } else {
    jQuery('#st-clicks-tags .inside').html('<div class="container_clicktags"></div>')
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

  // Hide click tags
  jQuery('#close_clicktags').click(function (event) {
    event.preventDefault()

    jQuery('#st-clicks-tags .container_clicktags').fadeOut('slow', function () {
      jQuery('#open_clicktags').show()
      jQuery('#close_clicktags').hide()
    })
    return false
  })
})

function load_click_tags () {
  jQuery('#st-clicks-tags .container_clicktags')
    .fadeIn('slow')
    .load(ajaxurl + '?action=simpletags&stags_action=click_tags&post_id=' + current_post_id, function () {
      jQuery('#st-clicks-tags .container_clicktags span').click(function (event) {
        event.preventDefault()
        addTag(this.innerHTML)
        jQuery(this).addClass('used_term')
      })

      jQuery('#open_clicktags').hide()
      jQuery('#close_clicktags').show()
    })
}