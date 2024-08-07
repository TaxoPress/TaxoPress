(function ($) {
  "use strict";

  /**
   * All of the code for your admin-facing JavaScript source
   * should reside in this file.
   *
   * Note: It has been assumed you will write jQuery code here, so the
   * $ function reference has been prepared for usage within the scope
   * of this function.
   *
   * This enables you to define handlers, for when the DOM is ready:
   *
   * $(function() {
   *
   * });
   *
   * When the window is loaded:
   *
   * $( window ).load(function() {
   *
   * });
   *
   * ...and/or other possibilities.
   *
   * Ideally, it is not considered best practise to attach more than a
   * single DOM-ready or window-load handler for a particular page.
   * Although scripts in the WordPress core, Plugins and Themes may be
   * practising this, we should strive to set a better example in our own work.
   */

  $(document).ready(function () {
    var removed_tax = taxoPressAIRequestAction.removed_tax;
    
    // Remove built in tax for block editor
    if (removed_tax.length > 0 && typeof wp.data !== 'undefined' && typeof wp.data.dispatch('core/edit-post') !== 'undefined') {
      for (let i = 0; i < removed_tax.length; i++) {
          wp.data.dispatch('core/edit-post').removeEditorPanel('taxonomy-panel-' + removed_tax[i]);
      }
    }

    if ($('#taxopress-ai-suggestedtags')) {
      //$('#taxopress-ai-suggestedtags').find('.handle-actions').prepend(html_entity_decode(taxoPressAIRequestAction.apiEditLink));
      if (typeof wp.data !== 'undefined' && typeof wp.data.select('core') !== 'undefined' && typeof wp.data.select('core/edit-post') !== 'undefined' && typeof wp.data.select('core/editor') !== 'undefined') {
        $('body').addClass('taxopress-block-editor');
      }
    }

    /**
     * Fetch post terms
     */
    $(document).on("click", ".taxopress-ai-fetch-button", function (event) {
      event.preventDefault();

      var button = $(this);
      var preview_wrapper = $(button).closest('.taxopress-ai-tab-content');
      var preview_ai = preview_wrapper.attr('data-ai-source');
      var preview_taxonomy = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').val();
      var taxonomy_rest = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').data("rest_base");
      var post_content = getContentFromEditor();
      var post_title = getTitleFromEditor();
      var preview_post = preview_wrapper.attr('data-post_id');
      var current_tags = (typeof wp.data !== 'undefined' && typeof wp.data.select('core/editor') !== 'undefined') ? wp.data.select('core/editor').getEditedPostAttribute(taxonomy_rest) : [];
      preview_wrapper.find('.taxopress-ai-fetch-result').html('');
      preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('').removeClass('updated error');

      button.prop('disabled', true);
      preview_wrapper.find('.spinner').addClass('is-active');

      var search_text = preview_wrapper.find('.taxopress-taxonomy-search').val();

      //prepare ajax data
      var data = {
        action: "taxopress_ai_preview_feature",
        preview_ai: preview_ai,
        preview_taxonomy: preview_taxonomy,
        search_text: search_text,
        preview_post: preview_post,
        post_content: post_content,
        post_title: post_title,
        current_tags: current_tags,
        nonce: taxoPressAIRequestAction.nonce,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status === 'error') {
          preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
        } else {
          preview_wrapper.find('.taxopress-ai-fetch-result').html(response.content);
        }

        button.prop('disabled', false);
        preview_wrapper.find('.spinner').removeClass('is-active');
      });

    });


    // -------------------------------------------------------------
    //   Show/hide search box for eligible tab
    // -------------------------------------------------------------
    $(document).on('click', 'ul.taxopress-tab.ai-integration-tab li', function () {
      var current_tab      = $(this).attr('data-content');
      if (current_tab === 'existing_terms') {
        $('.taxopress-taxonomy-search').show();
      } else {
        $('.taxopress-taxonomy-search').hide();
      }
    });

    // -------------------------------------------------------------
    //  Select/de-select all tags tags
    // -------------------------------------------------------------
    $(document).on('click', '.previewed-tag-fieldset .ai-select-all', function () {
      var button = $(this);

      if (button.hasClass('all-selected')) {
        button.removeClass('all-selected');
        button.html(button.attr('data-select-all'));
        button.closest('.previewed-tag-fieldset').find('.result-terms').addClass('used_term').trigger('click');
      } else {
        button.addClass('all-selected');
        button.html(button.attr('data-deselect-all'));
        button.closest('.previewed-tag-fieldset').find('.result-terms').removeClass('used_term').trigger('click');
      }
    });

    // -------------------------------------------------------------
    //  Select or de-select term (click tags)
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-ai-fetch-result .result-terms:not(.disabled)', function () {

      var term_button = $(this);

      var preview_wrapper = term_button.closest('.taxopress-ai-tab-content');
      var taxonomy = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').val();
      var taxonomy_rest = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').data("rest_base");
      var taxonomy_hierarchical = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').data("hierarchical");

      var this_result = term_button.find('.term-name');
      var this_selected = term_button.hasClass('used_term');
      var this_term_id = Number(this_result.attr('data-term_id'));
      var this_term_name = this_result.html();

      term_button.addClass('disabled');

      if (typeof wp.data !== 'undefined' && typeof wp.data.select('core') !== 'undefined' && typeof wp.data.select('core/edit-post') !== 'undefined' && typeof wp.data.select('core/editor') !== 'undefined') {
        if (!this_selected && this_term_id > 0) {
          selectGutenbergTerms([this_term_id], taxonomy_rest, taxonomy);
          term_button.toggleClass('used_term');
          term_button.removeClass('disabled');
        } else if (!this_selected && this_term_id === 0) {
          addNewGutenbergTerm(this_term_name, taxonomy)
            .then(term_id => {
              this_result.attr('data-term_id', term_id);
              selectGutenbergTerms([term_id], taxonomy_rest, taxonomy);
              term_button.toggleClass('used_term');
              term_button.removeClass('disabled');
            })
            .catch(error => {
              term_button.removeClass('disabled');
            });
        } else if (this_selected) {
          removeGutenbergTerms([this_term_id], taxonomy_rest, taxonomy);
          term_button.toggleClass('used_term');
          term_button.removeClass('disabled');
        }
      } else {

        if (taxonomy_hierarchical > 0) {
          if (this_selected) {
            $('#' + taxonomy + '-' + this_term_id).find('input:first').attr('checked', false);
            term_button.toggleClass('used_term');
            term_button.removeClass('disabled');
          } else if (!this_selected ) {
            if (this_term_id > 0 && document.getElementById('' + taxonomy + '-' + this_term_id)) {
              $('#' + taxonomy + '-' + this_term_id).find('input:first').attr('checked', true);
              term_button.toggleClass('used_term');
              term_button.removeClass('disabled');
            } else {
              addNewClassicCategory(this_term_name, taxonomy, function (term_data) {
                if (term_data) {
                  this_result.attr('data-term_id', term_data.term_id);
                  var checklistSelector = '#' + taxonomy + 'checklist';
                  var categoryList = jQuery(checklistSelector);
                  var taxonomy_field_name = '';

                  if (taxonomy == 'category') {
                    taxonomy_field_name = 'post_category';
                  } else {
                    taxonomy_field_name = 'tax_input[' + taxonomy + ']';
                  }

                  categoryList.prepend('<li id="' + taxonomy + '-' + term_data.term_id + '"><label class="selectit"><input value="' + term_data.term_id + '" type="checkbox" name="' + taxonomy_field_name + '[]" id="in-' + taxonomy + '-' + term_data.term_id + '" checked> ' + term_data.name + '</label></li>');

                  this_result.attr('data-term_id', term_data.term_id);
                  term_button.toggleClass('used_term');
                  term_button.removeClass('disabled');
                } else {
                  term_button.removeClass('disabled');
                }
              });
            }
          }
          
        } else {
          this_term_name = this_term_name.replace(/\s+,+\s*/g, ',').replace(/,+/g, ',').replace(/,+\s+,+/g, ',').replace(/,+\s*$/g, '').replace(/^\s*,+/g, '');

          if (this_selected) {
            jQuery('#tagsdiv-' + taxonomy + ' .tagchecklist').find('li').each(function () {
              var listItem = jQuery(this);
              var listItemText = listItem.contents().filter(function() {
                return this.nodeType === 3; // Filter out non-text nodes
              }).text().trim();

              if (listItemText == this_term_name) {
                var removeButton = listItem.find('.ntdelbutton');
                removeButton.trigger('click');
                term_button.toggleClass('used_term');
                term_button.removeClass('disabled');
              }
            });
          } else {
            if (jQuery('#new-tag-' + taxonomy).val() === '') {
              jQuery('#new-tag-' + taxonomy).val(this_term_name);
              var currentFocus = document.activeElement;
              jQuery('#new-tag-' + taxonomy).closest('div').find('input[type="button"]').click();
              jQuery(currentFocus).focus();
            } else {
              var current_tags = jQuery('#new-tag-' + taxonomy).val();
              var current_tags_array = current_tags.split(',');
              if (!current_tags_array.includes(this_term_name) && !current_tags_array.includes(' ' + this_term_name)) {
                jQuery('#new-tag-' + taxonomy).val(current_tags + ', ' + this_term_name);
                var currentFocus = document.activeElement;
                jQuery('#new-tag-' + taxonomy).closest('div').find('input[type="button"]').click();
                jQuery(currentFocus).focus();
              }
            }
            term_button.toggleClass('used_term');
            term_button.removeClass('disabled');
          }
        }
      }

    });

    /**
     * Reset preview on taxonomy change
     */
    $(document).on("change", ".taxopress-ai-tab-content .taxopress-ai-fetch-taxonomy-select", function (event) {
      var preview_wrapper = $(this).closest('.taxopress-ai-tab-content');
      preview_wrapper.find('.taxopress-ai-fetch-result').html('');
      preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('').removeClass('updated error');
    });

    function selectGutenbergTerms(tags, taxonomy_rest_base, taxonomy) {
      const currentTags = wp.data.select('core/editor').getEditedPostAttribute(taxonomy_rest_base);
      var updatedTags = [...currentTags, ...tags];
      updatedTags = updatedTags.filter(onlyUnique);

      wp.data.dispatch('core/editor').editPost({
        [taxonomy_rest_base]: updatedTags,
      });

      // close and open taxonomy panel to refresh
      if (wp.data.select('core/edit-post').isEditorPanelOpened('taxonomy-panel-' + taxonomy)) {
        wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('taxonomy-panel-' + taxonomy);
        setTimeout(function () {
          wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('taxonomy-panel-' + taxonomy);
        }, 100);
      }
    }

    function removeGutenbergTerms(tagsToRemove, taxonomy_rest_base, taxonomy) {
      const currentTags = wp.data.select('core/editor').getEditedPostAttribute(taxonomy_rest_base);
      const updatedTags = currentTags.filter((tag) => !tagsToRemove.includes(tag));

      wp.data.dispatch('core/editor').editPost({
        [taxonomy_rest_base]: updatedTags,
      });

      if (updatedTags.length === 0) {
        // close and open taxonomy panel to refresh
        if (wp.data.select('core/edit-post').isEditorPanelOpened('taxonomy-panel-' + taxonomy)) {
          wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('taxonomy-panel-' + taxonomy);
          setTimeout(function () {
            wp.data.dispatch('core/edit-post').toggleEditorPanelOpened('taxonomy-panel-' + taxonomy);
          }, 10);
        }
      }
    }

    function addNewGutenbergTerm(term_name, taxonomy) {
      return new Promise((resolve, reject) => {
        const termData = {
          name: term_name,
        };

        wp.data.dispatch('core').saveEntityRecord('taxonomy', taxonomy, termData, {
          isAutosave: false,
          throwOnError: true,
        })
          .then(newCategory => {
            if (typeof newCategory === 'object' && newCategory.hasOwnProperty('id')) {
              resolve(newCategory.id);
            } else {
              reject(newCategory);
            }
          })
          .catch(error => {
            if (typeof error === 'object' && error.hasOwnProperty('data')) {
              resolve(error.data.term_id);
            } else {
              reject(error);
            }
          });
      });
    }
    
    function addNewClassicCategory(term_name, taxonomy, callback) {
      var data = {
        action: "taxopress_ai_add_new_term",
        taxonomy: taxonomy,
        term_name: term_name,
        nonce: taxoPressAIRequestAction.nonce,
      };
    
      $.post(ajaxurl, data, function (response) {
        if (response.status === 'error') {
          callback(null);
        } else {
          callback(response.term);
        }
      });
    }


    function getTitleFromEditor() {
      var data = '';

      try {
        data = wp.data.select('core/editor').getEditedPostAttribute('title');
      } catch (error) {
        data = jQuery('#title').val();
      }

      //fix elementor issue
      if (typeof data == "undefined") {
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
          } catch (error) { }
        }
      }

      // Trim data
      data = data.replace(/^\s+/, '').replace(/\s+$/, '');
      if (data !== '') {
        data = strip_tags(data);
      }

      return data;
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
      var toReturn = '';
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
    

    function onlyUnique(value, index, self) {
      return self.indexOf(value) === index;
    }


  });
})(jQuery);
