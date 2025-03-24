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
    var current_screen = taxoPressAIRequestAction.current_screen;

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
      var existing_terms_order = preview_wrapper.find('#existing_terms_order :selected').val();
      var existing_terms_orderby = preview_wrapper.find('#existing_terms_orderby :selected').val();
      var existing_terms_maximum_terms = preview_wrapper.find('#existing_terms_maximum_terms').val();
      var selected_autoterms = preview_wrapper.find('.taxopress-autoterms-options').val();

      //prepare ajax data
      var data = {
        action: "taxopress_ai_preview_feature",
        preview_ai: preview_ai,
        preview_taxonomy: preview_taxonomy,
        search_text: search_text,
        existing_terms_order: existing_terms_order,
        existing_terms_orderby: existing_terms_orderby,
        existing_terms_maximum_terms: existing_terms_maximum_terms,
        selected_autoterms: selected_autoterms,
        preview_post: preview_post,
        post_content: post_content,
        post_title: post_title,
        current_tags: current_tags,
        screen_source: current_screen,
        nonce: taxoPressAIRequestAction.nonce,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status === 'error') {
          preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
        } else {
          preview_wrapper.find('.taxopress-ai-fetch-result').html(response.content);
          autoterm_option_select2();
        }

        button.prop('disabled', false);
        preview_wrapper.find('.spinner').removeClass('is-active');
      });

    });

    /**
     * Create post terms
     */
    $(document).on("click", ".taxopress-ai-create-button", function (event) {
      event.preventDefault();

      var button = $(this);
      var preview_wrapper = $(button).closest('.taxopress-ai-tab-content');
      var taxonomy = preview_wrapper.find('.taxopress-ai-fetch-create-taxonomy :selected').val();

      var term_name = preview_wrapper.find('.taxopress-taxonomy-term-input').val();
      if (!term_name || term_name == '') {
        return;
      }
      
      var preview_post = preview_wrapper.attr('data-post_id');
      preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('').removeClass('updated error');

      button.prop('disabled', true);
      preview_wrapper.find('.spinner').addClass('is-active');

      // get existing same taxonomy term on the page
      var existing_terms = [];
      var selected_terms = [];

      preview_wrapper.find('.result-terms .term-name').each(function () {
        var term = jQuery(this);
        if (term.attr('data-taxonomy') == taxonomy) {
          existing_terms.push(term.text().trim());
          if (term.closest('.result-terms').hasClass('used_term')) {
            selected_terms.push(term.attr('data-term_id'));
          }
        }
      });

      //prepare ajax data
      var data = {
        action: "taxopress_ai_add_new_term",
        taxonomy: taxonomy,
        term_name: term_name,
        post_id: preview_post,
        existing_terms: existing_terms,
        selected_terms: selected_terms,
        screen_source: current_screen,
        nonce: taxoPressAIRequestAction.nonce,
      };

      $.post(ajaxurl, data, function (response) {
        if (response.status === 'error') {
          preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
        } else {
          preview_wrapper.find('.taxopress-taxonomy-term-input').val('');
          preview_wrapper.find('.taxopress-ai-fetch-result').html(response.term_html);
          preview_wrapper.find('.result-terms .term-name[data-term_id="' + response.current_term + '"]').trigger('click');
        }

        button.prop('disabled', false);
        preview_wrapper.find('.spinner').removeClass('is-active');
      });

    });


    // -------------------------------------------------------------
    //   Show/hide search box for eligible tab
    // -------------------------------------------------------------
    $(document).on('click', 'ul.taxopress-tab.ai-integration-tab li', function () {
      var current_tab = $(this).attr('data-content');
      if (current_tab === 'existing_terms') {
        $('.existing-term-item').show();
      } else {
        $('.existing-term-item').hide();
      }
      if (current_tab === 'create_term') {
        $('.create-term-item').show();
        $('.taxopress-ai-fetch-taxonomy-select').hide();
        $('.taxopress-ai-fetch-button').hide();
      } else {
        $('.create-term-item').hide();
        $('.taxopress-ai-fetch-taxonomy-select').show();
        $('.taxopress-ai-fetch-button').show();
      }

      if (current_tab === 'suggest_local_terms') {
        $('.taxopress-autoterms-options').show();
      } else {
        $('.taxopress-autoterms-options').hide();
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

      if (current_screen == 'st_taxopress_ai') {
        term_button.toggleClass('used_term');
        return;
      }

      var preview_wrapper = term_button.closest('.taxopress-ai-tab-content');
      var taxonomy = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').val();
      var taxonomy_rest = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').data("rest_base");
      var taxonomy_hierarchical = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').data("hierarchical");
      var preview_post = preview_wrapper.find('.preview-post-select').val();
      
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
          } else if (!this_selected) {
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
              var listItemText = listItem.contents().filter(function () {
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



    /**
     * Add ai select 2 post search if the div is present o the page
     */
    if ($('.taxopress-ai-post-search').length > 0) {
      taxopressPostSelect2($('.taxopress-ai-post-search'));
      function taxopressPostSelect2(selector) {
        selector.each(function () {
          var postsSearch = $(this).ppma_select2({
            placeholder: $(this).data("placeholder"),
            allowClear: $(this).data("allow-clear"),
            ajax: {
              url:
                window.ajaxurl +
                "?action=taxopress_ai_post_search&nonce=" +
                $(this).data("nonce") + "&ai_source=" +
                $(this).closest(".taxopress-tab-content-item").data("ai-source"),
              dataType: "json",
              data: function (params) {
                return {
                  q: params.term
                };
              }
            }
          });
        });

        /**
         * Intercept ai select 2 post search to add post type
         */
        $(document).ajaxSend(function (e, xhr, options) {
          // Check if the AJAX request matches the specified action
          if (
            options.dataType === 'json' &&
            options.url.includes('taxopress_ai_post_search') &&
            options.url.includes('ai_source')
          ) {
            console.log(options.url);

            // Extract ai_source from the URL
            var urlParams = new URLSearchParams(options.url.split('?')[1]);
            var aiSource = urlParams.get('ai_source');

            if (aiSource) {
              var postType = $('.taxopress-tab-content-item.' + aiSource)
                .find('.preview-post-types-select')
                .val();

              options.url += '&post_type=' + encodeURIComponent(postType);
            }
          }
        });
      }


      /**
       * Reset selected post if post type changes
       */
      $(document).on("change", ".preview-post-types-select", function (event) {
        var post_select = $(this).closest('.taxopress-tab-content-item').find('.preview-post-select');
        post_select.val('').empty().ppma_select2('destroy');
        taxopressPostSelect2($('.taxopress-ai-post-search'));
      });

      /**
       * Update selected post when change
       */
      $(document).on("change", ".taxopress-ai-post-search", function (event) {
        var selected_post = $(this).val();

        $(this).closest('.taxopress-tab-content-item').attr('data-post_id', selected_post);
      });

      // -------------------------------------------------------------
      //  Update selected tags post
      // -------------------------------------------------------------
      $(document).on('click', '.taxopress-tab-content-item .taxopress-ai-addtag-button', function (e) {
        e.preventDefault();
        var button = $(this);
        var preview_wrapper = button.closest('.taxopress-tab-content-item');
        var this_result = '';
        var this_term_id = '';
        var this_term_name = '';
        var this_selected = '';
        var term_data = '';
        var preview_taxonomy = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').val();
        var preview_taxonomy_label = preview_wrapper.find('.taxopress-ai-fetch-taxonomy-select :selected').text().trim();
        var preview_post = preview_wrapper.find('.preview-post-select').val();
        var preview_post_type_label = preview_wrapper.find('.preview-post-types-select :selected').text().trim();
        var preview_ai = preview_wrapper.attr('data-ai-source');

        button.prop('disabled', true);
        button.find('.spinner').addClass('is-active');

        var added_tags = [];
        var removed_tags = [];

        preview_wrapper.find('.result-terms').each(function () {
          this_result = $(this).find('.term-name');
          this_selected = $(this).hasClass('used_term');
          this_term_id = Number(this_result.attr('data-term_id'));
          this_term_name = this_result.html();
          term_data = {
            'term_id': this_term_id,
            'name': this_term_name
          };

          if (this_selected) {
            added_tags.push(term_data);
          } else if (this_term_id > 0) {
            removed_tags.push(term_data);
          }
        });

        //prepare ajax data
        var data = {
          action: "taxopress_ai_add_post_term",
          taxonomy: preview_taxonomy,
          taxonomy_label: preview_taxonomy_label,
          post_id: preview_post,
          post_type_label: preview_post_type_label,
          added_tags: added_tags,
          removed_tags: removed_tags,
          screen_source: current_screen,
          nonce: taxoPressAIRequestAction.nonce,
        };

        $.post(ajaxurl, data, function (response) {
          if (response.status === 'error') {
            preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
          } else {
            preview_wrapper.find('.taxopress-ai-fetch-result-msg').html('<p>' + response.content + '</p>').addClass('updated').removeClass('error');
          }

          button.prop('disabled', false);
          button.find('.spinner').removeClass('is-active');
        });

      });
    }

    /**
     * Add ai select 2 if the div is present on the page
     */
    if ($('.taxopress-ai-select2').length > 0) {
      $('.taxopress-ai-select2').ppma_select2({
        placeholder: $(this).data("placeholder"),
        allowClear: $(this).data("allow-clear"),
      });
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

      if (current_screen == 'st_taxopress_ai') {
        return data;
      }

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

      if (current_screen == 'st_taxopress_ai') {
        return data;
      }

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

    function autoterm_option_select2() {
      $('.auto_term_terms_options.select').ppma_select2({
        placeholder: $(this).data("placeholder"),
      });
    }


  });
})(jQuery);
