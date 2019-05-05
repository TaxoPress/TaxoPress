function addTag (tag) {
  // Trim tag
  tag = tag.replace(/^\s+/, '').replace(/\s+$/, '')

  if (document.getElementById('adv-tags-input')) { // Tags input from Simple Tags

    var tag_entry = document.getElementById('adv-tags-input')
    if (tag_entry.value.length > 0 && !tag_entry.value.match(/,\s*$/)) {
      tag_entry.value += ', '
    }

    var re = new RegExp(tag + ',')
    if (!tag_entry.value.match(re)) {
      tag_entry.value += tag + ', '
    }

  } else if (document.getElementById('new-tag-post_tag')) { // Default tags input from WordPress

    tag.replace(/\s+,+\s*/g, ',').replace(/,+/g, ',').replace(/,+\s+,+/g, ',').replace(/,+\s*$/g, '').replace(/^\s*,+/g, '')
    if (jQuery('#new-tag-post_tag').val() === '') {
      jQuery('#new-tag-post_tag').val(tag)
    } else {
      jQuery('#new-tag-post_tag').val(jQuery('#new-tag-post_tag').val() + ', ' + tag)
    }
    //jQuery('.tagadd').WithSelect()

  } else if (typeof wp.data != 'undefined'
    && typeof wp.data.select('core') != 'undefined'
    && typeof wp.data.select('core/edit-post') != 'undefined'
    && typeof wp.data.select('core/editor') != 'undefined') { // Gutenberg

    // Show the tags panel
    if (wp.data.select('core/edit-post').isEditorPanelOpened('taxonomy-panel-post_tag') === false) {
      wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('taxonomy-panel-post_tag')
    }

    // Get current post_tags
    var tags_taxonomy = wp.data.select('core').getTaxonomy('post_tag')
    var tag_rest_base = tags_taxonomy && tags_taxonomy.rest_base
    var tags = tag_rest_base && wp.data.select('core/editor').getEditedPostAttribute(tag_rest_base)

    var newTags = JSON.parse(JSON.stringify(tags))

    jQuery.ajax({
      url: ajaxurl + '?action=simpletags&stags_action=maybe_create_tag&tag=' + tag,
      cache: false,
      //async: false,
      dataType: 'json'
    }).done(function (result) {
      if (result.data.term_id > 0) {
        newTags.push(result.data.term_id)

        var new_tag = {}
        new_tag[tag_rest_base] = newTags

        wp.data.dispatch('core/editor').editPost(new_tag)
        // See : https://riad.blog/2018/06/07/efficient-client-data-management-for-wordpress-plugins/
      }
    }).fail(function () {
      console.log('error when trying to create tag')
    })

  } else {

    console.log('no tags input found...')

  }
}