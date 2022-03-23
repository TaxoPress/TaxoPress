jQuery(document).ready(function () {
  jQuery('#suggestedtags .hndle').html(html_entity_decode(stHelperSuggestedTagsL10n.title_bloc));
  jQuery('#suggestedtags .inside .container_clicktags').html(stHelperSuggestedTagsL10n.content_bloc);
  jQuery('#suggestedtags .handle-actions').prepend(html_entity_decode(stHelperSuggestedTagsL10n.edit_metabox_link));

  // Generi call for autocomplete API
  jQuery('a.suggest-action-link').click(function (event) {
    event.preventDefault();

    jQuery('#st_ajax_loading').show();

    jQuery('#suggestedtags .container_clicktags').load(ajaxurl + '?action=simpletags&stags_action=' + jQuery(this).data('ajaxaction') + '&suggestterms=' + jQuery(this).data('suggestterms') + '', {
      content: getContentFromEditor(),
      title: getTitleFromEditor()
    }, function () {
      registerClickTags();
    });
    return false;
  });

  jQuery('select.term_suggestion_select').click(function (e) {
    e.stopPropagation();
    jQuery('#suggestedtags').removeClass('close');
  });

  jQuery('button.term_suggestion_refresh').click(function (e) {
    e.stopPropagation();
    e.preventDefault();
    jQuery('#suggestedtags').removeClass('close');
    jQuery('select.term_suggestion_select').trigger('change');
  });
  
  jQuery('select.term_suggestion_select').change(function () {
    jQuery('#suggestedtags').removeClass('close');
    var suggestterms = Number(jQuery('select.term_suggestion_select').attr('data-suggestterms'));
    var data_action = jQuery('select.term_suggestion_select :selected').val();
    var current_post_id = jQuery('#post_ID').val();
    
    if (data_action == '') {
      jQuery('#suggestedtags .inside .container_clicktags').html(stHelperSuggestedTagsL10n.content_bloc);
      return;
    }
    
    jQuery('#st_ajax_loading').show();

    jQuery('#suggestedtags .container_clicktags').load(ajaxurl + '?action=simpletags&stags_action=' + data_action + '&suggestterms=' + suggestterms + '', {
      content: getContentFromEditor(),
      post_id: current_post_id,
      title: getTitleFromEditor()
    }, function () {
      registerClickTags();
    });
    return false;
  });


  //set local tags as default
  if (jQuery('.term_suggestion_select').length > 0) {
    setTimeout(taxopress_set_default_suggested_term, 2000);
  }
  function taxopress_set_default_suggested_term(){
      jQuery('.term_suggestion_select').val(jQuery(".term_suggestion_select option:eq(1)").val()).trigger('change');
  }

});

function getTitleFromEditor() {
  var data = '';

  try {
    data = wp.data.select('core/editor').getEditedPostAttribute('title');
  } catch (error) {
    data = jQuery('#title').val();
  }

  // Trim data
  data = data.replace(/^\s+/, '').replace(/\s+$/, '');
  if (data !== '') {
    data = strip_tags(data);
  }

  return data;
}

function getContentFromEditor() {
  var data = '';

  try { // Gutenberg
    data = wp.data.select('core/editor').getEditedPostAttribute('content');
  } catch (error) {
    try { // TinyMCE
      var ed = tinyMCE.activeEditor;
      if ('mce_fullscreen' == ed.id) {
        tinyMCE.get('content').setContent(ed.getContent({
          format: 'raw'
        }), {
          format: 'raw'
        });
      }
      tinyMCE.get('content').save();
      data = jQuery('#content').val();
    } catch (error) {
      try { // Quick Tags
        data = jQuery('#content').val();
      } catch (error) {}
    }
  }

  // Trim data
  data = data.replace(/^\s+/, '').replace(/\s+$/, '');
  if (data !== '') {
    data = strip_tags(data);
  }

  return data;
}
function registerClickTags() {
  jQuery('#suggestedtags .container_clicktags span').click(function (event) {
    event.preventDefault();

    var taxonomy = jQuery(this).attr('data-taxonomy');
    var term_id = jQuery(this).attr('data-term_id');
    if (term_id > 0) {
      addTag(this.innerHTML, taxonomy, term_id);
    } else {
      addTag(this.innerHTML);
    }

    jQuery(this).addClass('used_term');
  });

  jQuery('#st_ajax_loading').hide();
  if (jQuery('#suggestedtags .inside').css('display') != 'block') {
    jQuery('#suggestedtags').toggleClass('closed');
  }
}

/**
 * The html_entity_decode() php function on JS :)
 *
 * See : https://github.com/hirak/phpjs
 *
 * @param str
 * @returns {string | *}
 */
function html_entity_decode(str) {
  var ta = document.createElement('textarea');
  ta.innerHTML = str.replace(/</g, '&lt;').replace(/>/g, '&gt;');
  toReturn = ta.value;
  ta = null;
  return toReturn;
}

/**
 * The strip_tags() php function on JS :)
 *
 * See : https://github.com/hirak/phpjs
 *
 * @param str
 * @returns {*}
 */
function strip_tags(str) {
  return str.replace(/&lt;\/?[^&gt;]+&gt;/gi, '');
}
