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


    /**
     * Add ai select 2 if the div is present on the page
     */
    if ($('.taxopress-ai-select2').length > 0) {
      $('.taxopress-ai-select2').ppma_select2({
        placeholder: $(this).data("placeholder"),
        allowClear: $(this).data("allow-clear"),
      });
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
                        $(this).closest(".taxopress-ai-preview-sidebar").data("ai-source"),
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
        $(document).ajaxSend(function(e, xhr, options) {
          // Check if the AJAX request matches the specified action
          if (options.dataType === 'json'
            && options.url.indexOf('taxopress_ai_post_search') !== -1 
            && options.url.indexOf('ai_source') !== -1
          ) {
            var postType = $('.taxopress-ai-preview-sidebar').find('.preview-post-types-select').val();
            options.url += '&post_type=' + postType;
            }
          });
        }

        /**
         * Reset selected post if post type changes
         */
        $(document).on("change", ".preview-post-types-select", function (event) {
          var post_select = $(this).closest('.taxopress-ai-preview-sidebar').find('.preview-post-select');
          post_select.val('').empty().ppma_select2('destroy');
          taxopressPostSelect2($('.taxopress-ai-post-search'));
          $('.sidebar-response-wrap').html('').removeClass('updated').removeClass('error');
        });

        /**
         * Reset preview on taxonomy change
         */
        $(document).on("change", ".preview-taxonomy-select", function (event) {
          $('.sidebar-response-preview').html('').removeClass('has-content');
        });

        /**
         * Preview selected AI integration action
         */
        $(document).on("click", ".taxopress-ai-preview-button", function (event) {
          event.preventDefault();
          
          var button = $(this);
          var preview_wrapper  = $(button).closest('.taxopress-ai-preview-sidebar');
          var preview_ai       = preview_wrapper.attr('data-ai-source');
          var preview_taxonomy = preview_wrapper.find('.preview-taxonomy-select :selected').val();
          var preview_post     = preview_wrapper.find('.preview-post-select').val();
          var preview_post_type_label = preview_wrapper.find('.preview-post-types-select :selected').text().trim();
          var preview_post_type_single_label = preview_wrapper.find('.preview-post-types-select :selected').attr('data-singular_label');

          if (!preview_post || preview_post == '') {
            $('.sidebar-response-wrap').html('<p>' + taxoPressAIRequestAction.requiredSuffix + ' </p>').removeClass('updated').addClass('error');
            return;
          }
          
          $('.sidebar-response-preview.' + preview_ai).html('').removeClass('has-content');
          $('.sidebar-response-wrap').html('').removeClass('updated').removeClass('error');

          button.prop('disabled', true);
          preview_wrapper.find('.spinner').addClass('is-active');


        //prepare ajax data
        var data = {
            action: "taxopress_ai_preview_feature",
            preview_ai: preview_ai,
            preview_taxonomy: preview_taxonomy,
            preview_post: preview_post,
            nonce: taxoPressAIRequestAction.nonce,
        };

        $.post(ajaxurl, data, function (response) {
            if (response.status === 'error') {
              $('.sidebar-response-wrap').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
            } else {
              $('.sidebar-response-preview.' + preview_ai).html(response.content).addClass('has-content');
            }
            
            button.prop('disabled', false);
            preview_wrapper.find('.spinner').removeClass('is-active');
        });

        });

        
        // -------------------------------------------------------------
        //   Set preview title, class and wrapper for selected AI
        // -------------------------------------------------------------
        $(document).on('click', 'ul.taxopress-tab.ai-integration-tab li', function () {
          var current_tab      = $(this).attr('data-content');
          var current_tab_text = $(this).find('a span').html();
          var preview_wrapper  = $('.taxopress-ai-preview-sidebar');
          var preview_title    = $('.taxopress-ai-preview-sidebar').find('.preview-title');
          var ai_groups        = taxoPressAIRequestAction.aiGroups;
          var button_label     = taxoPressAIRequestAction.fieldTabs[current_tab].button_label;
          var ai_group_icons   = ai_groups.map(item => `${item}_icon`);

          preview_wrapper.find('.taxopress-ai-preview-button .btn-text').html(button_label);

          preview_wrapper.removeClass(ai_groups.join(' '));
          preview_wrapper
            .addClass(current_tab)
            .attr('data-ai-source', current_tab);

          preview_title.removeClass(ai_group_icons.join(' '));
          preview_title
            .addClass(current_tab + '_icon')
            .html(current_tab_text + ' ' + preview_wrapper.attr('data-preview'));

          $('.sidebar-response-wrap').html('').removeClass('updated').removeClass('error');
          $('.sidebar-response-preview').hide().removeClass('has-content');
          $('.sidebar-response-preview.' + current_tab).css('display', '').addClass('has-content');

        });

        // -------------------------------------------------------------
        //  Select and de-select tags
        // -------------------------------------------------------------
        $(document).on('click', '.previewed-tag-content .result-terms', function () {
          $(this).toggleClass('used_term');
        });

        // -------------------------------------------------------------
        //  Select/de-select all tags tags
        // -------------------------------------------------------------
        $(document).on('click', '.previewed-tag-fieldset .ai-select-all', function () {
          var button = $(this);

          if (button.hasClass('all-selected')) {
            button.removeClass('all-selected');
            button.html(button.attr('data-select-all'));
            button.closest('.previewed-tag-fieldset').find('.result-terms').removeClass('used_term');
          } else {
            button.addClass('all-selected');
            button.html(button.attr('data-deselect-all'));
            button.closest('.previewed-tag-fieldset').find('.result-terms').addClass('used_term');
          }
        });

        // -------------------------------------------------------------
        //  Update selected tags
        // -------------------------------------------------------------
        $(document).on('click', '.sidebar-response-preview .taxopress-ai-addtag-button', function (e) {
          e.preventDefault();
          var button = $(this);
          var preview_wrapper = button.closest('.taxopress-ai-preview-sidebar'); 
          var this_result = ''; 
          var this_term_id = ''; 
          var this_term_name = ''; 
          var this_selected = '';
          var term_data = '';
          var preview_taxonomy = preview_wrapper.find('.preview-taxonomy-select :selected').val();
          var preview_taxonomy_label = preview_wrapper.find('.preview-taxonomy-select :selected').text().trim();
          var preview_post     = preview_wrapper.find('.preview-post-select').val();
          var preview_post_type_label = preview_wrapper.find('.preview-post-types-select :selected').text().trim();
          var preview_ai       = preview_wrapper.attr('data-ai-source');

          button.prop('disabled', true);
          button.find('.spinner').addClass('is-active');

          var added_tags    = [];
          var removed_tags  = [];

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
              nonce: taxoPressAIRequestAction.nonce,
          };

          $.post(ajaxurl, data, function (response) {
              if (response.status === 'error') {
                $('.sidebar-response-wrap').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
              } else {
                $('.sidebar-response-preview.' + preview_ai).html('').removeClass('has-content');
                $('.sidebar-response-wrap').html('<p>' + response.content + '</p>').removeClass('error').addClass('updated');
              }
              
            button.prop('disabled', false);
            button.find('.spinner').removeClass('is-active');
          });

        });
      }

  });
})(jQuery);
