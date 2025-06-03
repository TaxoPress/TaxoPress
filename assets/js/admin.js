(function ($) {
  'use strict';

  /**
   * All of the code for admin-facing JavaScript source
   * should reside in this file.
   */

  $(document).ready(function () {
    
    var autoTermProcessingPaused = false;
    
    // -------------------------------------------------------------
    //   Expand textarea height based on lines
    // -------------------------------------------------------------
    $(document).on('change keyup input', '.taxopress-expandable-textarea', function (e) {
      var textArea = $(this);
      textArea.css('height', 'auto');
      var newHeight = textArea[0].scrollHeight;
      textArea.height(newHeight);
    });
    
    // -------------------------------------------------------------
    //   TaxoPress term quick edit save
    // -------------------------------------------------------------
    $('.taxopress-save', $('#inline-edit') ).on( 'click', function() {
			return save_taxopress_qe_term(this);
    });

    function get_taxopress_term_id(o){
      var id = o.tagName === 'TR' ? o.id : $(o).parents('tr').attr('id'), parts = id.split('-');
  
      return parts[parts.length - 1];
    }
    
      function save_taxopress_qe_term(id) {
        var params, fields, tax = $('input[name="taxonomy"]').val() || '';
  
        // Makes sure we can pass an HTMLElement as the ID.
        if( typeof(id) === 'object' ) {
          id = get_taxopress_term_id(id);
        }
    
        $('table.widefat .spinner').addClass('is-active');
        
        var rowData = $('#inline_' + id);
        var original_tax = $('.taxonomy', rowData).text();
        
        params = {
          action: 'taxopress_terms_inline_save_term',
          tax_ID: id,
          taxonomy: tax
        };
    
        fields = $('#edit-'+id).find(':input').serialize();
        params = fields + '&original_tax=' + original_tax + '&' + $.param(params);
    
        // Do the Ajax request to save the data to the server.
        $.post( ajaxurl, params,
          /**
           * Handles the response from the server
           *
           * Handles the response from the server, replaces the table row with the response
           * from the server.
           *
           * @param {string} r The string with which to replace the table row.
           */
          function(r) {
            var row, new_id, option_value,
              $errorNotice = $( '#edit-' + id + ' .inline-edit-save .notice-error' ),
              $error = $errorNotice.find( '.error' );
    
            $( 'table.widefat .spinner' ).removeClass( 'is-active' );
    
            if (r) {
              if ( -1 !== r.indexOf( '<tr' ) ) {
                $(inlineEditTax.what+id).siblings('tr.hidden').addBack().remove();
                new_id = $(r).attr('id');
    
                $('#edit-'+id).before(r).remove();
    
                if ( new_id ) {
                  option_value = new_id.replace( inlineEditTax.type + '-', '' );
                  row = $( '#' + new_id );
                } else {
                  option_value = id;
                  row = $( inlineEditTax.what + id );
                }
    
                // Update the value in the Parent dropdown.
                $( '#parent' ).find( 'option[value=' + option_value + ']' ).text( row.find( '.row-title' ).text() );
    
                row.hide().fadeIn( 400, function() {
                  // Move focus back to the Quick Edit button.
                  row.find( '.editinline' )
                    .attr( 'aria-expanded', 'false' )
                    .trigger( 'focus' );
                  wp.a11y.speak( wp.i18n.__( 'Changes saved.' ) );
                });
    
              } else {
                $errorNotice.removeClass( 'hidden' );
                $error.html( r );
                /*
                 * Some error strings may contain HTML entities (e.g. `&#8220`), let's use
                 * the HTML element's text.
                 */
                wp.a11y.speak( $error.text() );
              }
            } else {
              $errorNotice.removeClass( 'hidden' );
              $error.text( wp.i18n.__( 'Error while saving the changes.' ) );
              wp.a11y.speak( wp.i18n.__( 'Error while saving the changes.' ) );
            }
          }
        );
    
        // Prevent submitting the form when pressing Enter on a focused field.
        return false;
      }
      
      // -------------------------------------------------------------
      //   Settings sub tab click
      // -------------------------------------------------------------
      $(document).on('click', '.st-legacy-subtab span', function (e) {
        e.preventDefault();
        var current_content = $(this).attr('data-content');
        $('.st-legacy-subtab span').removeClass('active');
        $('.legacy-tab-content').addClass('st-hide-content');
        $(this).addClass('active');
        $(current_content).removeClass('st-hide-content');
      });
      
      // -------------------------------------------------------------
      //   Settings TaxoPress AI sub tab click
      // -------------------------------------------------------------
      $(document).on('click', '.st-taxopress-ai-subtab span', function (e) {
        e.preventDefault();
        var current_content = $(this).attr('data-content');
        $('.st-taxopress-ai-subtab span').removeClass('active');
        $('.taxopress-ai-tab-content').addClass('st-hide-content');
        $('.taxopress-ai-tab-content-sub').addClass('st-subhide-content');
        $(this).addClass('active');
        $(current_content).removeClass('st-hide-content');
        if ($(current_content).find('input').prop("checked")) {
          $(current_content + '-sub').removeClass('st-subhide-content');
        }
      });
      
      // -------------------------------------------------------------
      //   Settings TaxoPress AI checkbox changed
      // -------------------------------------------------------------
      $(document).on('change', '.taxopress-ai-tab-content input', function (e) {
        var checked_field = $(this).prop("checked");
        var field_id      = $(this).attr('id');
        if (checked_field) {
            $('.' + field_id + '_field').removeClass('st-subhide-content');
        } else {
          $('.' + field_id + '_field').addClass('st-subhide-content');
        }
      });
      // Show taxopress ai settings sub fields for enabled settings
      if ($('.taxopress-ai-post-content').length > 0) {
        if ($('.taxopress-ai-post-content').find('input').prop("checked")) {
          $('.taxopress-ai-post-content-sub').removeClass('st-subhide-content');
        }
      }
      
      // -------------------------------------------------------------
      //   Settings metabox sub tab click
      // -------------------------------------------------------------
      $(document).on('click', '.st-metabox-subtab span', function (e) {
        e.preventDefault();
        var current_content = $(this).attr('data-content');
        $('.st-metabox-subtab span').removeClass('active');
        $('.metabox-tab-content').addClass('st-hide-content');
        $('.metabox-tab-content-sub').addClass('st-subhide-content');
        $(this).addClass('active');
        $(current_content).removeClass('st-hide-content');
        if ($(current_content).find('input').prop("checked")) {
          $(current_content + '-sub').removeClass('st-subhide-content');
        }
      });
      
      // -------------------------------------------------------------
      //   Settings metabox checkbox changed
      // -------------------------------------------------------------
      /*
      $(document).on('change', '.metabox-tab-content input', function (e) {
        var checked_field = $(this).prop("checked");
        var field_id      = $(this).attr('id');

        if (checked_field) {
            $('.' + field_id + '_field').removeClass('st-subhide-content');
        } else {
          $('.' + field_id + '_field').addClass('st-subhide-content');
        }
      });
      // Show metabox settings sub fields for enabled settings
      if ($('.metabox-post-content').length > 0) {
        if ($('.metabox-post-content').find('input').prop("checked")) {
          $('.metabox-post-content-sub').removeClass('st-subhide-content');
        }
      }*/

    // -------------------------------------------------------------
    //   Show taxonomy option based on selected CPT
    // -------------------------------------------------------------
    $(document).on('change paste keyup', '.st-cpt-select', function (e) {
      //taxonomy
      var val = this.value;
      var options = document.getElementsByClassName('st-taxonomy-select')[0].options;
      var new_val = null;
      for (var i = 0; i < options.length; i++) {
        if (options[i].attributes["data-post"].value === val) {
          if (!new_val) {
            new_val = options[i].value;
          }
          options[i].classList.remove("st-hide-content");
        } else {
          options[i].classList.add("st-hide-content");
        }
      }
      document.getElementsByClassName('st-taxonomy-select')[0].value = new_val;

    });

    // -------------------------------------------------------------
    //   Prevent non number from number type input
    // -------------------------------------------------------------
    $(document).on('change paste keyup keydown', '.taxopress-section input[type="number"]', function (e) {
      if (e.which === 69) {
        e.preventDefault();
      }
    });

    // -------------------------------------------------------------
    //   Show taxonomy option based on selected CPT for other screen
    // -------------------------------------------------------------
    $(document).on('change paste keyup', '.st-cpt-select, .st-post-type-select', function (e) {
      var val = this.value;
      var $el = $(this);
      var new_val = null;
    
      // Handle tabbed selectors like .st-cpt-select-xyz
      var tabClass = $el.attr('class').split(' ').find(cls => cls.startsWith('st-cpt-select-'));
      if (tabClass) {
        var suffix = tabClass.replace('st-cpt-select-', '');
        var $taxonomySelect = $('.st-taxonomy-select-' + suffix);
        $taxonomySelect.find('option').each(function () {
          var postType = $(this).data('post');
          if (postType === val) {
            if (!new_val) new_val = $(this).val();
            $(this).removeClass('st-hide-content');
          } else {
            $(this).addClass('st-hide-content');
          }
        });
        $taxonomySelect.val(new_val);
      }
    
      // Handle generic post type selectors
      if ($el.hasClass('st-post-type-select')) {
        var options = document.getElementsByClassName('st-post-taxonomy-select')[0].options;
        for (var i = 0; i < options.length; i++) {
          var data_post = options[i].getAttribute('data-post_type');
          if (!data_post) continue;
    
          var types = data_post.split(',');
          if (types.includes(val) || val === 'st_all_posttype' || val === 'st_current_posttype' || val === '') {
            if (!new_val) new_val = options[i].value;
            options[i].classList.remove('st-hide-content');
          } else {
            options[i].classList.add('st-hide-content');
          }
        }
    
        var $taxonomySelect = $('.st-post-taxonomy-select');
        if ($taxonomySelect.children(':selected').hasClass('st-hide-content')) {
          document.getElementsByClassName('st-post-taxonomy-select')[0].value = new_val;
        }
      }
    });
    if ($('.st-post-type-select').length > 0) {
      $('.st-post-type-select').trigger('change');
    }

  // Activate correct tab on load
  const urlParams = new URLSearchParams(window.location.search);
  const activeTab = urlParams.get('tab');
  if (activeTab) {
    $('.simple-tags-nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
    $('.auto-terms-content').hide();
    $('.nav-tab[data-page=".st-' + activeTab + '"]').addClass('nav-tab-active');
    $('.st-' + activeTab).show();
  }

    // -------------------------------------------------------------
    //   Add auto tags suggestion tag
    // -------------------------------------------------------------
    $(document).on('click', '.st-add-suggestion-input', function (e) {
      e.preventDefault();
      $('.auto-terms-keyword-list').append('<input type="text" name="auto_list[]" /> <input class="st-delete-suggestion-input" type="button" value="' + st_admin_localize.delete_label+ '"/>');
    });

    // -------------------------------------------------------------
    //   Delete auto tags suggestion input field
    // -------------------------------------------------------------
    $(document).on('click', '.st-delete-suggestion-input', function (e) {
      e.preventDefault();
      $(this).prev('input').remove();
      $(this).remove();
    });

    // -------------------------------------------------------------
    //   Auto terms tab action
    // -------------------------------------------------------------
    $(document).on('click', '.simple-tags-nav-tab-wrapper li', function (e) {
      e.preventDefault();
      //change active tab
      $('.nav-tab').removeClass('nav-tab-active');
      $(this).addClass('nav-tab-active');
      //change active content
      $('.auto-terms-content').hide();
      $($(this).attr('data-page')).show();
    });

    // -------------------------------------------------------------
    //   Default tab click trigger
    // -------------------------------------------------------------
    if ($('.load-st-default-tab').length > 0) {
      $(".simple-tags-nav-tab-wrapper").find("[data-page='" + $('.load-st-default-tab').attr('data-page') + "']").trigger('click');
    }

    // -------------------------------------------------------------
    //   Term to use source check
    // -------------------------------------------------------------
    $(document).on('click', '.update_auto_list', function (e) {
      $('.auto-terms-error-red').hide();
      var prevent_default = false;
      if (!$('#at_all').prop("checked") && !$('#at_all_no').prop("checked")) {
        prevent_default = true;
      } else if ($('#at_all').prop("checked")) {
        prevent_default = false;
      } else if ($('#at_all_no').prop("checked")) {
        prevent_default = false;
      } else {
        prevent_default = false;
      }

      if (prevent_default) {
        $('.auto-terms-error-red').show();
        $('html, body').animate({
          scrollTop: $(".auto-terms-error-red").offset().top - 200
        }, 'fast');
        e.preventDefault();
      }

    });

    // -------------------------------------------------------------
    //   Restrict terms to use to only one checkbox
    // -------------------------------------------------------------
    $(document).on('click', '#at_all', function (e) {
      $('#at_all_no').prop("checked", false);
    });
    $(document).on('click', '#at_all_no', function (e) {
      $('#at_all').prop("checked", false);
    });

    // -------------------------------------------------------------
    //   Manage terms add terms option check
    // -------------------------------------------------------------
    $(document).on('click', '.addterm_type_matched_only', function (e) {
      $('#addterm_match').val($('#addterm_match').attr('data-prev'));
      $('.terms-to-maatch-input').show();
    });
    $(document).on('click', '.addterm_type_all_posts', function (e) {
      $('#addterm_match').attr('data-prev', $('#addterm_match').val());
      $('#addterm_match').val('');
      $('.terms-to-maatch-input').hide();
    });

    // -------------------------------------------------------------
    //   Manage terms remove terms option check
    // -------------------------------------------------------------
    $(document).on('click', '.removeterm_type_matched_only', function (e) {
      $('#removeterm_match').val($('#removeterm_match').attr('data-prev'));
      $('.removeterms-to-match-input').show();
    });
    $(document).on('click', '.removeterm_type_all_posts', function (e) {
      $('#removeterm_match').attr('data-prev', $('#removeterm_match').val());
      $('#removeterm_match').val('');
      $('.removeterms-to-match-input').hide();
    });

        // -------------------------------------------------------------
    //   Manage terms merge terms option check
    // -------------------------------------------------------------
    $(document).on('click', '.mergeterm_type_different_name', function (e) {
      $('#mergeterm_new').val($('#mergeterm_new').attr('data-prev'));
      $('.new_name_input').show();
    });
    $(document).on('click', '.mergeterm_type_same_name', function (e) {
      $('#mergeterm_new').attr('data-prev', $('#mergeterm_new').val());
      $('#mergeterm_new').val('');
      $('.new_name_input').hide();
    });

    // -------------------------------------------------------------
    //   Terms display submit validation
    // -------------------------------------------------------------
    $('.taxopress-tag-cloud-submit').on('click', function (e) {


      var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
        field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<ul>';

      $.each(fields, function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if (field_object.attr('required')) {
          if (!field.value) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          } else if (isEmptyOrSpaces(field.value)) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          }
        }
      });

      if (!$('.tag-cloud-min').val()) {
        field_error_count = 1;
        field_error_message += '<li>' + st_admin_localize.select_valid + ' ' + $('.tag-cloud-min').closest('tr').find('th label').text() + '<span class="required">*</span></li>';
      }
      if (!$('.tag-cloud-max').val()) {
        field_error_count = 1;
        field_error_message += '<li>' + st_admin_localize.select_valid + ' ' + $('.tag-cloud-max').closest('tr').find('th label').text() + '<span class="required">*</span></li>';
      }
      
      if (Number($('input[name="taxopress_tag_cloud[smallest]"]').val()) > Number($('input[name="taxopress_tag_cloud[largest]"]').val())) {
        field_error_count = 1;
        field_error_message += '<li>' + $('.pp-terms-display-fontsize-warning').val() + '<span class="required">*</span></li>';
      }

      field_error_message += '</ul>';

      if (field_error_count > 0) {
        e.preventDefault();
        // Display the alert
        $('#taxopress-modal-alert-content').html(field_error_message);
        $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
      }


    })

    // -------------------------------------------------------------
    //   Clear previous notification message on submit button
    // -------------------------------------------------------------
    $('.taxopress-right-sidebar input[type="submit"], .taxonomiesui input[type="submit"]').on('click', function (e) {
      $('.taxopress-edit #message.updated').remove();
    });


    // -------------------------------------------------------------
    //   Taxopress tab
    // -------------------------------------------------------------
    $('ul.taxopress-tab li').on('click', function (e) {
      e.preventDefault();
      var tab_content = $(this).attr('data-content');

      $('.taxopress-tab li').removeClass('active');
      $('.taxopress-tab li').attr('aria-current', 'false');
      $(this).addClass('active');
      $(this).attr('aria-current', 'true');

      $('.taxopress-tab-content table').hide();
      $('.tab-table-content').hide();
      $('.taxopress-tab-content table.' + tab_content).show();
      $('.tab-table-content.' + tab_content + '-tab-table-content').show();

      $('.visbile-table').css('display', '');

      //set tab height
      if ($('.taxopress-tab-content').height() > $('.taxopress-tab').height() || $('.taxopress-tab').height() > $('.taxopress-tab-content').height()) {
        $('.taxopress-tab').css('height', $('.taxopress-tab-content').height());
      }
      //set active tab value
      $('.taxopress-active-subtab').val(tab_content);
    });

    if ($('.taxopress-tab-content').length > 0) {
      //set tab height
      var tab_height = $('.taxopress-tab-content').height();

      if (tab_height > 0) {
        if (tab_height > $('.taxopress-tab').height()) {
          $('.taxopress-tab').css('height', tab_height);
        }
      } else {
      $('.taxopress-tab').css('min-height', 300);
      }
    }


    // -------------------------------------------------------------
    //   Auto link submit error
    // -------------------------------------------------------------
    $('.taxopress-autolink-submit').on('click', function (e) {


      var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
        field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<ul>';

      $.each(fields, function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if (field_object.attr('required')) {
          if (!field.value) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          } else if (isEmptyOrSpaces(field.value)) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          }
        }
      });

      field_error_message += '</ul>';



      if (field_error_count > 0) {
        e.preventDefault();
        // Display the alert
        $('#taxopress-modal-alert-content').html(field_error_message);
        $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
      }


    });


    // -------------------------------------------------------------
    //   Related posts submit error
    // -------------------------------------------------------------
    $('.taxopress-relatedposts-submit').on('click', function (e) {


      var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
        field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<ul>';

      $.each(fields, function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if (field_object.attr('required')) {
          if (!field.value) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          } else if (isEmptyOrSpaces(field.value)) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          }
        }
      });

      field_error_message += '</ul>';

      if (field_error_count > 0) {
        e.preventDefault();
        // Display the alert
        $('#taxopress-modal-alert-content').html(field_error_message);
        $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
      }

    });

       // Update term link format when format is changed to 'box'
       if ($('body').hasClass('taxopress_page_st_related_posts')) {
        $(document).on('change', 'select[name="taxopress_related_post[format]"]', function () {
            var xformatField = $('textarea[name="taxopress_related_post[xformat]"]');
            
            if ($(this).val() === 'box') {
                xformatField.val(
                    '<a href="' + st_admin_localize.post_permalink + '" title="' + st_admin_localize.post_title + ' (' + st_admin_localize.post_date + ')">' +
                    '<img src="' + st_admin_localize.post_thumb_url + '" height="200" width="200" class="custom-image-class"/>' + 
                    '<br>' + st_admin_localize.post_title + '<br>'
                     + st_admin_localize.post_date + '<br>'
                     + st_admin_localize.post_category +
                    '</a>'
                );
            } else {
                xformatField.val(
                    '<a href="' + st_admin_localize.post_permalink + '" title="' + st_admin_localize.post_title + ' (' + st_admin_localize.post_date + ')">' +
                    st_admin_localize.post_title +
                    '</a>'
                );
            }
        });
    }


    // -------------------------------------------------------------
    //   Post tags submit error
    // -------------------------------------------------------------
    $('.taxopress-posttags-submit').on('click', function (e) {


      var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
        field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<ul>';

      $.each(fields, function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if (field_object.attr('required')) {
          if (!field.value) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          } else if (isEmptyOrSpaces(field.value)) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          }
        }
      });

      field_error_message += '</ul>';

      if (field_error_count > 0) {
        e.preventDefault();
        // Display the alert
        $('#taxopress-modal-alert-content').html(field_error_message);
        $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
      }

    });


    // -------------------------------------------------------------
    //   Restrict terms to use to only one checkbox
    // -------------------------------------------------------------
    $(document).on('click', '.autoterm_useall', function (e) {
      $('.autoterm_useonly').prop("checked", false);
      $('.autoterm_useonly_options').addClass('st-hide-content');
    });
    $(document).on('click', '.autoterm_useonly', function (e) {
      $('.autoterm_useall').prop("checked", false);
      $('.autoterm_useonly_options').removeClass('st-hide-content');
    });


    // -------------------------------------------------------------
    //   Remove specific term row
    // -------------------------------------------------------------
    $(document).on('click', '.remove-specific-term', function (e) {
      e.preventDefault();
      $(this).closest('.st-autoterms-single-specific-term').remove();
    });

    // -------------------------------------------------------------
    //   Specific term input change
    // -------------------------------------------------------------
    $(document).on('keydown', '.specific_terms_input', function (e) {
      if (e.type === 'keydown' && e.key === 'Enter') {
        $(this).trigger('change');
        return;
      }
    });
    $(document).on('change', '.specific_terms_input', function (e) {
      var new_term = $(this).val();

      if (new_term.endsWith(", ")) {
        var term_name = new_term.replace(/,\s$/, '');
      } else {
        term_name = new_term;
      }

      var existing_values = [];
      if ($('.taxopress-terms-names').length > 0) {
        existing_values = $('.taxopress-terms-names').map(function() {
          return $(this).val();
        }).get();
      }
      if (!existing_values.includes(term_name) && !isEmptyOrSpaces(term_name)) {
        var linked_term_list = $(".taxopress-term-list-style");
        var new_linked_term = "";
        new_linked_term += '<li class="taxopress-term-li">';
        new_linked_term +=
          '<span class="display-text">' + term_name + '</span>';
        new_linked_term +=
          '<span class="remove-term-row"><span class="dashicons dashicons-no-alt"></span></span>';
        new_linked_term +=
          '<input type="hidden" class="taxopress-terms-names" name="specific_terms[]" value="' +
          term_name +
          '">';
        new_linked_term += "</li>";
        linked_term_list.append(new_linked_term);
      }
      $(this).val(' ');
    });

    // -------------------------------------------------------------
    //  Remove specific term
    // -------------------------------------------------------------
    $(document).on("click", ".taxopress-term-list-style .remove-term-row", function () {
        $(this).closest("li").remove();
    });



        // -------------------------------------------------------------
        //   Add specific term row
        // -------------------------------------------------------------
        $(document).on('click', '.add-specific-term', function (e) {
          e.preventDefault();
          var new_row = '';

          new_row +='<div class="st-autoterms-single-specific-term">';
          new_row +='<input type="text" class="specific_terms_input" name="specific_terms[]" value="" maxlength="32" placeholder="'+ $(this).attr('data-placeholder') +'">';
          new_row +=' &nbsp; ';
          new_row +='<span class="remove-specific-term" title="'+ $(this).attr('data-text') +'">X</span>';
          new_row +='</div>';

          $(this).closest('.st-autoterms-single-specific-term').before(new_row);

        });


    // -------------------------------------------------------------
    //   Auto terms submit error
    // -------------------------------------------------------------
    $('.taxopress-autoterm-submit').on('click', function (e) {


      var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
        field_label,
        field_object,
        field_error_count = 0,
        field_error_message = '<ul>';

      //required field check
      $.each(fields, function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if (field_object.attr('required')) {
          if (!field.value) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          } else if (isEmptyOrSpaces(field.value)) {
            field_label = field_object.closest('tr').find('label').html();
            field_error_count = 1;
            field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
          }
        }
      });
      //terms to user check
      var term_to_use_error = true;
      $.each($(".autoterm-terms-to-use-field"), function (i, field) {
        field_object = $('input[name="' + field.name + '"]');
        if(field_object.prop("checked")){
          term_to_use_error = false;
        }
      });
      if (term_to_use_error) {
        field_error_count = 1;
        field_error_message += '<li>' + $('.auto-terms-to-use-error').html() + ' <span class="required">*</span></li>';
      }
      //post status check
      if ($('input[name="post_status[]"]:checked').length === 0) {
        field_error_count = 1;
        field_error_message += '<li>' + $('.auto-terms-post-status-error').html() + ' <span class="required">*</span></li>';
      }

      field_error_message += '</ul>';

      if (field_error_count > 0) {
        e.preventDefault();
        // Display the alert
        $('#taxopress-modal-alert-content').html(field_error_message);
        $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
      }

    });

    // -------------------------------------------------------------
    //   Show or hide the copy terms selection box
    // -------------------------------------------------------------
    if ($('body').hasClass('taxopress_page_st_terms')) {
      $(document).on('change', '#bulk-action-selector-top', function (e) {
          e.preventDefault();
          if (this.value === 'taxopress-terms-copy-terms') {
              $('#taxopress-copy-selection-boxes').show();
          } else {
              $('#taxopress-copy-selection-boxes').hide();
          }
      });
  }

    // -------------------------------------------------------------
    //   Auto term close button
    // -------------------------------------------------------------
    $(document).on('click', '.auto-term-content-result-title .notice-dismiss', function (e) {
        e.preventDefault();
        $('.auto-term-content-result-title').html('');
    });

    if ($('.taxopress-autoterm-content #autoterm_id').length > 0) {
      auto_terms_content_settings_edit();
      $(document).on('change', '.taxopress-autoterm-content #autoterm_id', function (e) {
        auto_terms_content_settings_edit();
      });
    }
    function auto_terms_content_settings_edit() {
      $('.autoterm-content-settings-link').remove();
      var current_settings_id = $('.taxopress-autoterm-content #autoterm_id').val();
      $('.taxopress-autoterm-content #autoterm_id').next('p').append('<a target="_blank" class="autoterm-content-settings-link" href="' + st_admin_localize.autoterm_admin_url + '&add=new_item&action=edit&taxopress_autoterms=' + current_settings_id + '">' + st_admin_localize.existing_content_admin_label + '</a>');
    }

    if ($('.settings-metabox_auto_term-wrap #metabox_auto_term').length > 0) {
      auto_terms_metabox_settings_edit();
      $(document).on('change', '.settings-metabox_auto_term-wrap #metabox_auto_term', function (e) {
        auto_terms_metabox_settings_edit();
      });
    }
    function auto_terms_metabox_settings_edit() {
      $('.autoterm-settings-link').remove();
      var current_settings_id = $('.settings-metabox_auto_term-wrap #metabox_auto_term').val();
      $('.settings-metabox_auto_term-wrap #metabox_auto_term').closest('td').find('p.description').append('<a target="_blank" class="autoterm-settings-link" href="' + st_admin_localize.autoterm_admin_url + '&add=new_item&action=edit&taxopress_autoterms=' + current_settings_id + '">' + st_admin_localize.existing_content_admin_label + '</a>');
    }

    // -------------------------------------------------------------
    //   Auto term all content
    // -------------------------------------------------------------
    var existingContentAjaxRequest; 
    $(document).on('click', '.taxopress-autoterm-all-content', function (e) {
        e.preventDefault();
        $('.auto-term-content-result').html('');
        $('.auto-term-content-result-title').html('');
        var button = $(this);
        auto_terms_all_content(0, button);
    });

    // -------------------------------------------------------------
    //   Auto term existing content log button
    // -------------------------------------------------------------
    $(document).on('click', '.log-message-show-button a, .log-message-hide-button a', function (e) {
        e.preventDefault();
        $(this).hide();
        if ($(this).hasClass('msg-show')) {
          $(this).closest('.result-item').find('.log-message-hide-button a').show();
        } else {
          $(this).closest('.result-item').find('.log-message-show-button a').show();
        }
        $(this).closest('.result-item').find('.autoterm-log-message').slideToggle(400);
    });

    // Terminate the AJAX request when "Stop" button is clicked
    $(document).on('click', '.terminate-autoterm-scan', function (e) {
      e.preventDefault();
      if (existingContentAjaxRequest) {
          existingContentAjaxRequest.abort(); // Abort the ongoing AJAX request
      }
      $(".taxopress-spinner").removeClass("is-active");
      $('.taxopress-autoterm-all-content').attr('disabled', false);
      $('.auto-term-content-result-title').html('');
    });

    // Pause or Resume Auto Term Scan
    $(document).on('click', '.pause-autoterm-scan', function (e) {
        e.preventDefault();
        var pause_text = $(this).data('pause-text');
        var resume_text = $(this).data('resume-text');
        var button = $('.taxopress-autoterm-all-content');
        
        if (!autoTermProcessingPaused) {
            // Pause processing
            autoTermProcessingPaused = true;
            $(this).text(resume_text);
            if (existingContentAjaxRequest) {
                existingContentAjaxRequest.abort();
                $(".taxopress-spinner").removeClass("is-active");
            }
        } else {
            // Resume processing
            autoTermProcessingPaused = false;
            $(this).text(pause_text);
            // Resume from last stored point
            var resumeFrom = button.data('resume-from') || 0;
            auto_terms_all_content(resumeFrom, button);
        }
    });

    function auto_terms_all_content(start_from, button) {
        // Don't start new request if paused
        if (autoTermProcessingPaused) {
            return;
        }

        $(".taxopress-spinner").addClass("is-active");
        button.attr('disabled', true);

        var data = $('#auto_term_content_form').serializeArray();
        data.push({ name: 'action', value: 'taxopress_autoterms_content_by_ajax' });
        data.push({ name: 'start_from', value: start_from });
        data.push({ name: 'security', value: st_admin_localize.check_nonce });

        // Store the next start point for later resume
        button.data('resume-from', start_from);
        existingContentAjaxRequest = $.post(st_admin_localize.ajaxurl, data, function (response) {

            if(response.status === 'error') {
                $('.auto-term-content-result-title').html(''+response.message+'');
                $(".taxopress-spinner").removeClass("is-active");
                button.attr('disabled', false);
            } else if(response.status === 'progress') {
                $('.auto-term-content-result-title').html(response.percentage + response.notice);
                $('.auto-term-content-result').prepend(response.content);
                //send next batch
                auto_terms_all_content(response.done, button);
            } else if(response.status === 'sucess') {
                $('.auto-term-content-result-title').html(''+response.percentage+'');
                $(".taxopress-spinner").removeClass("is-active");
                button.attr('disabled', false);
            }
        });
    }

        // -------------------------------------------------------------
        //   Suggest terms submit error
        // -------------------------------------------------------------
            $('.taxopress-suggestterm-submit').on('click', function (e) {


              var fields = $(".taxopress-section").find("select, textarea, input").serializeArray(),
                field_label,
                field_object,
                field_error_count = 0,
                field_error_message = '<ul>';

              //required field check
              $.each(fields, function (i, field) {
                field_object = $('input[name="' + field.name + '"]');
                if (field_object.attr('required')) {
                  if (!field.value) {
                    field_label = field_object.closest('tr').find('label').html();
                    field_error_count = 1;
                    field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
                  } else if (isEmptyOrSpaces(field.value)) {
                    field_label = field_object.closest('tr').find('label').html();
                    field_error_count = 1;
                    field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
                  }
                }
              });

              if ($('.taxopress_suggestterm_taxonomies option:selected').length === 0) {
                field_label = $('.taxopress_suggestterm_taxonomies').closest('tr').find('label').html();
                field_error_count = 1;
                field_error_message += '<li>' + field_label + ' is required <span class="required">*</span></li>';
              }

              field_error_message += '</ul>';

              if (field_error_count > 0) {
                e.preventDefault();
                // Display the alert
                $('#taxopress-modal-alert-content').html(field_error_message);
                $('[data-remodal-id=taxopress-modal-alert]').remodal().open();
              }

            });


    if ($('.autoterms-log-table-settings').length > 0) {
      var props_label = '<label class="taxopress-props-label">&nbsp;</label>';
      $('.tablenav.top .alignleft.actions.bulkactions').prepend(props_label);
      $('.tablenav.top .tablenav-pages').prepend(props_label);
    }

    // -------------------------------------------------------------
    //   Log filter
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-logs-tablenav-filter', function (e) {
      e.preventDefault();
      $('input[name="log_source_filter"]').val($('#log_source_filter_select :selected').val());
      $('input[name="log_filter_post_type"]').val($('#log_filter_select_post_type :selected').val());
      $('input[name="log_filter_taxonomy"]').val($('#log_filter_select_taxonomy :selected').val());
      $('input[name="log_filter_status_message"]').val($('#log_filter_select_status_message :selected').val());
      $('input[name="log_filter_settings"]').val($('#log_filter_select_settings :selected').val());
      $('#taxopress-log-search-submit').trigger('click');
    });

    $(document).on('change', '.auto-terms-log-filter-select', function (e) {
      $('.taxopress-logs-tablenav-filter').trigger('click');
    });

    // -------------------------------------------------------------
    //   terms filter
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-terms-tablenav-filter', function (e) {
      e.preventDefault();
      $('input[name="taxonomy_type"]').val($('#terms_filter_select_taxonomy_type :selected').val());
      $('input[name="terms_filter_post_type"]').val($('#terms_filter_select_post_type :selected').val());
      $('input[name="terms_filter_taxonomy"]').val($('#terms_filter_select_taxonomy :selected').val());
      $('input[name="terms_filter_status_message"]').val($('#terms_filter_select_status_message :selected').val());
      $('input[name="terms_filter_settings"]').val($('#terms_filter_select_settings :selected').val());
      $('#taxopress-terms-search-submit').trigger('click');
    });

    $(document).on('change', '.auto-terms-terms-filter-select', function (e) {
      $('.taxopress-terms-tablenav-filter').trigger('click');
    });

    // -------------------------------------------------------------
    //   post filter
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-posts-tablenav-filter', function (e) {
      e.preventDefault();
      $('input[name="posts_term_filter"]').val($('.posts-term-filter-select :selected').val());
      $('input[name="posts_post_type_filter"]').val($('.posts-post-type-filter-select :selected').val());
      $('#taxopress-posts-search-submit').trigger('click');
    });

    $(document).on('change', '.posts-term-filter-select, .posts-post-type-filter-select', function (e) {
      $('.taxopress-posts-tablenav-filter').trigger('click');
    });

    // -------------------------------------------------------------
    //   Auto Term find in type change
    // -------------------------------------------------------------
    $(document).on('change', '.autoterm-area-custom-type', function (e) {
      e.preventDefault();
      if ($(this).val() == 'custom_fields') {
        $('.autoterm-area-custom-taxonomy').addClass('st-hide-content');
        $('.autoterm-field-area').removeClass('st-hide-content');
      } else {
        $('.autoterm-area-custom-taxonomy').removeClass('st-hide-content');
        $('.autoterm-field-area').addClass('st-hide-content');
      }
    });

    /**
     * Delete find in custom item
     */
    $(document).on('click', 'table.st-autoterm-area-table .delete', function (e) {
        e.preventDefault();
        $(this).closest('tr').remove();
    });

    // -------------------------------------------------------------
    //   Auto Term find in taxonomy change
    // -------------------------------------------------------------
    $(document).on('change', '.autoterm-area-custom-taxonomy', function (e) {
      addNewFindInItem('taxonomies', $(this).val());
      $(this).val('');
    });

    function addNewFindInItem(find_in_type, find_in_value) {
      var button = $('.add-new-autoterm-area');
      if (!isEmptyOrSpaces(find_in_value)) {
        $('tr.' + find_in_type + '.' + find_in_value).remove();
        var new_element_html = '';
        new_element_html += '<tr valign="top" class="find-in-customs-row ' + find_in_type + ' ' + find_in_value + '"><td colspan="2" class="item-header"><div><span class="action-checkbox"><input type="hidden" name="find_in_customs_entries[' + find_in_type + '][]" value="' + find_in_value + '" /><input type="checkbox" id="' + find_in_value + '" name="find_in_' + find_in_type + '_custom_items[]" value="' + find_in_value + '" checked /></span><label for="' + find_in_value + '">' + find_in_value + '</label></div></td>';
        
        new_element_html += '<td><span class="delete">' + st_admin_localize.delete_label+ '</span></td></tr>';
        $('.autoterm-custom-findin-row.fields').after(new_element_html);  

      }
    }

        // -------------------------------------------------------------
    //   Select2 Search Box for Posts and Taxonomies on wordpress post screen
    // -------------------------------------------------------------
    if ($('.taxopress-select2-term-filter').length > 0) {
      taxopressTaxSelect2($('.taxopress-select2-term-filter'));
        
            function taxopressTaxSelect2(selector) {
              selector.each(function() {
                  $(this).ppma_select2({
                      placeholder: $(this).data('placeholder') || $(this).find('option:first').text(),
                      allowClear: true,
                      ajax: {
                          url: st_admin_localize.ajaxurl,
                          dataType: 'json',
                          data: function(params) {
                              return {
                                  action: 'taxopress_select2_term_filter',
                                  taxonomy: $(this).attr('id'),
                                  s: params.term || '',
                                  page: params.page || 1,
                                  nonce: st_admin_localize.check_nonce
                              };
                          },
                          processResults: function(data, params) {
                              params.page = params.page || 1;
                              return {
                                  results: data.items,
                                  pagination: {
                                      more: data.more
                                  }
                              };
                          },
                          cache: true
                      }
                  });
              });
          }
    }

    /**
     * TaxoPress posts select2
     */
    if ($('.taxopress-custom-fields-search').length > 0) {
      
      taxopressFieldSelect2($('.taxopress-custom-fields-search'));
      function taxopressFieldSelect2(selector) {
        selector.each(function () {
            var fieldSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: true,
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=taxopress_custom_fields_search&nonce=" +
                        $(this).data("nonce"),
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    }
                }
            }).on('ppma_select2:select', function (e) {
              
              var data = e.params.data;
              var selected_name = data.id;
              addNewFindInItem('custom_fields', selected_name);
              $(this).val(null).trigger('change'); 
          });
        });
    }
  }

    /**
     * TaxoPress term select2
     */
    if ($('.taxopress-term-search').length > 0) {
        taxopressTermSelect2($('.taxopress-term-search'));
        $('.taxopress-simple-select2').ppma_select2({
          placeholder: $(this).data("placeholder"),
          allowClear: true,
        });
        function taxopressTermSelect2(selector) {
          selector.each(function () {
              var termsSearch = $(this).ppma_select2({
                  placeholder: $(this).data("placeholder"),
                  allowClear: true,
                  ajax: {
                      url:
                          window.ajaxurl +
                          "?action=taxopress_filter_term_search&field=term_id&nonce=" +
                          $(this).data("nonce"),
                      dataType: "json",
                      data: function (params) {
                          return {
                              q: params.term
                          };
                      }
                  }
              });
          });
      }
    }

    
    if ($('.taxopress-post-search').length > 0) {
      taxopressPostSelect2($('.taxopress-post-search'));
      function taxopressPostSelect2(selector) {
        selector.each(function () {

            var checkedPostTypes = [];
            $('input[name="post_types[]"]:checked').each(function () {
                checkedPostTypes.push($(this).val());
            });

            // Build the post_types parameter as a query string (e.g., post_types=post&post_types=page)
            var postTypesParam = '';
            if (checkedPostTypes.length > 0) {
                postTypesParam = checkedPostTypes.map(function(postType) {
                    return '&post_types[]=' + encodeURIComponent(postType);
                }).join('');
            }

            var postsSearch = $(this).ppma_select2({
                placeholder: $(this).data("placeholder"),
                allowClear: $(this).data("allow-clear"),
                ajax: {
                    url:
                        window.ajaxurl +
                        "?action=taxopress_post_search&nonce=" + $(this).data("nonce") + postTypesParam,
                    dataType: "json",
                    data: function (params) {
                        return {
                            q: params.term
                        };
                    }
                }
            });
        });
      }
    }

    if ($('.taxopress-multi-select2').length > 0) {
        $('.taxopress-multi-select2').ppma_select2({
          placeholder: $(this).data("placeholder"),
        });
    }

    if ($('.taxopress-single-select2').length > 0) {
        $('.taxopress-single-select2').ppma_select2({
          placeholder: $(this).data("placeholder"),
        });
    }

    if ($('.auto_term_terms_options.select').length > 0) {
      autoterm_option_select2();
    }

    /**
     * Auto Term preview
     */
    $(document).on("click", ".taxopress-autoterm-fetch-wrap .preview-button", function (event) {
      event.preventDefault();
      
      var button = $(this);
      var preview_wrapper  = $(button).closest('.taxopress-autoterm-fetch-wrap');
      var preview_ai       = 'autoterms';
      var preview_taxonomy = $('.st-post-taxonomy-select').val();
      var preview_post     = preview_wrapper.find('.preview-post-select').val();
      var selected_autoterms  = $('input[name="taxopress_autoterm[ID]"]').val();

      if (!preview_post || preview_post == '') {
        $('.taxopress-autoterm-result .response').html('<p>' + st_admin_localize.post_required + ' </p>').removeClass('updated').addClass('error');
        return;
      }
      
      if (!selected_autoterms || selected_autoterms == '') {
        $('.taxopress-autoterm-result .response').html('<p>' + st_admin_localize.save_settings + ' </p>').removeClass('updated').addClass('error');
        return;
      }
      
      $('.taxopress-autoterm-result .response').html('').removeClass('updated').removeClass('error');
      $('.taxopress-autoterm-result .output').html('');

      button.prop('disabled', true);
      preview_wrapper.find('.spinner').addClass('is-active');

    //prepare ajax data
    var data = {
        action: "taxopress_ai_preview_feature",
        preview_ai: preview_ai,
        preview_taxonomy: preview_taxonomy,
        preview_post: preview_post,
        selected_autoterms: selected_autoterms,
        screen_source: 'st_autoterms',
        nonce: st_admin_localize.ai_nonce,
    };

    $.post(ajaxurl, data, function (response) {
        if (response.status === 'error') {
          $('.taxopress-autoterm-result .response').html('<p>' + response.content + '</p>').removeClass('updated').addClass('error');
        } else {
          $('.taxopress-autoterm-result .output').html(response.content);
          autoterm_option_select2();
        }
        
        button.prop('disabled', false);
        preview_wrapper.find('.spinner').removeClass('is-active');
    });

    });

    // -------------------------------------------------------------
    //  Auto Term term select, checkbox, or radio synch with default
    // -------------------------------------------------------------
    $(document).on('change', '.auto-terms-options-wrap .auto_term_terms_options', function () {
      var $field = $(this);
      var $selectedOptionsAttr = [];

      // Check if the field is a <select> field
      if ($field.is('select')) {
        // Loop through all selected options
        $field.find('option:selected').each(function () {
          $selectedOptionsAttr.push($(this).attr('data-term_link_id'));
        });
      }

      // Check if the field is a checkbox
      if ($field.is('input[type="checkbox"]')) {
        // Loop through all checked checkboxes within the container
        $field.closest('.auto-terms-options-wrap').find('input[type="checkbox"]:checked').each(function () {
          $selectedOptionsAttr.push($(this).attr('data-term_link_id'));
        });
      }

      // Check if the field is a radio button
      if ($field.is('input[type="radio"]')) {
        $selectedOptionsAttr.push($field.attr('data-term_link_id'));
      }

      // loop through all result terms to mark as selected or not
      $field.closest('fieldset').find('.result-terms').each(function () {
          if ($(this).hasClass('used_term') && !$selectedOptionsAttr.includes($(this).attr('data-term_link_id'))) {
            // trigger click to unselect term if previously selected but missing in the list
            $(this).trigger('click');
          } else if (!$(this).hasClass('used_term') && $selectedOptionsAttr.includes($(this).attr('data-term_link_id'))) {
            // trigger click to select term if not previously selected but in the list
            $(this).trigger('click');
          }
        });
    });

    // -------------------------------------------------------------
    //  Select and de-select tags
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-autoterm-result .output .previewed-tag-content .result-terms', function () {
      $(this).toggleClass('used_term');
    });

    // -------------------------------------------------------------
    //  Select/de-select all tags tags
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-autoterm-result .output .previewed-tag-fieldset .ai-select-all', function () {
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
    $(document).on('click', '.taxopress-autoterm-result .output .taxopress-ai-addtag-button', function (e) {
      e.preventDefault();
      var button = $(this);
      var preview_wrapper = button.closest('.taxopress-autoterm-result .output'); 
      var this_result = ''; 
      var this_term_id = ''; 
      var this_term_name = ''; 
      var this_selected = '';
      var term_data = '';
      var preview_taxonomy = $('.st-post-taxonomy-select :selected').val();
      var preview_taxonomy_label = $('.st-post-taxonomy-select :selected').text().trim();
      var preview_post     = $('.preview-post-select').val();
      var preview_post_type_label = '';
      var preview_ai       = 'autoterms';

      button.prop('disabled', true);
      button.find('.spinner').addClass('is-active');
      
      $('.taxopress-autoterm-result .response').html('').removeClass('updated').removeClass('error');

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
          nonce: st_admin_localize.ai_nonce,
      };

      $.post(ajaxurl, data, function (response) {
          if (response.status === 'error') {
            $('.taxopress-autoterm-result .response').html('<p>' + response.content + ' </p>').removeClass('updated').addClass('error');
          } else {
            $('.taxopress-autoterm-result .response').html('<p>' + response.content + ' </p>').removeClass('error').addClass('updated');
          }
          
        button.prop('disabled', false);
        button.find('.spinner').removeClass('is-active');
      });

    });

    

    // -------------------------------------------------------------
    //   Auto term limit update filter
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-logs-limit-update', function (e) {
      e.preventDefault();
      var limit = $('#taxopress_auto_terms_logs_limit').val();
      var link = $('#taxopress_auto_terms_logs_limit').attr('data-link');
      var url = link + '&limit=' + limit;
      window.location.href = url;
    });

    // -------------------------------------------------------------
    //   Terms display enable color
    // -------------------------------------------------------------
    $(document).on('click', '.tag-cloud-color-option', function (e) {
      tag_cloud_color_option_action();
    });
    tag_cloud_color_option_action();
    function tag_cloud_color_option_action() {
      if ($('.tag-cloud-color-option').length > 0) {
        if ($('.tag-cloud-color-option').prop("checked")) {
          $('.tag-cloud-min').closest('tr').removeClass('st-hide-content');
          $('.tag-cloud-max').closest('tr').removeClass('st-hide-content');
        } else {
          $('.tag-cloud-min').closest('tr').addClass('st-hide-content');
          $('.tag-cloud-max').closest('tr').addClass('st-hide-content');
        }
      }
    }

    // -------------------------------------------------------------
    //   Suggest term use Dandelion check
    // -------------------------------------------------------------
    $(document).on('click', '.suggest_term_use_dandelion', function (e) {
      suggest_term_use_dandelion_action();
    });
    suggest_term_use_dandelion_action();
    function suggest_term_use_dandelion_action() {
      if (st_admin_localize.enable_dandelion_ai_source !== '1')
      {
        $('.suggest_term_use_dandelion').closest('tr').addClass('st-hide-content');
        $('.suggest_term_use_dandelion_children').closest('tr').addClass('st-hide-content');
        return;
      }
      if ($('.suggest_term_use_dandelion').length > 0) {
        if ($('.suggest_term_use_dandelion').prop("checked")) {
          $('.suggest_term_use_dandelion_children').closest('tr').removeClass('st-hide-content');
        } else {
          $('.suggest_term_use_dandelion_children').closest('tr').addClass('st-hide-content');
        }
      }
    }

    // -------------------------------------------------------------
    //   Suggest term use OpenCalais check
    // -------------------------------------------------------------
    $(document).on('click', '.suggest_term_use_opencalais', function (e) {
      suggest_term_use_opencalais_action();
    });
    suggest_term_use_opencalais_action();
    function suggest_term_use_opencalais_action() {
      if (st_admin_localize.enable_lseg_ai_source !== '1')
      {
        $('.suggest_term_use_opencalais').closest('tr').addClass('st-hide-content');
        $('.suggest_term_use_opencalais_children').closest('tr').addClass('st-hide-content');
        return;
      }
      if ($('.suggest_term_use_opencalais').length > 0) {
        if ($('.suggest_term_use_opencalais').prop("checked")) {
          $('.suggest_term_use_opencalais_children').closest('tr').removeClass('st-hide-content');
        } else {
          $('.suggest_term_use_opencalais_children').closest('tr').addClass('st-hide-content');
        }
      }
    }

    // Add related posts uploaded
    if ($('.select-default-featured-media-field').length > 0) {
      
      var frame;
      // Select Media
      $('.select-default-featured-media-field').on('click', function(e){
          e.preventDefault();
          
          // If the media frame already exists, reopen it.
          if (frame) {
              frame.open();
              return;
          }
          
          // Create a new media frame
          frame = wp.media({
              title: st_admin_localize.select_default_label,
              button: {
                  text: st_admin_localize.use_media_label
              },
              multiple: false
          });

          // When an image is selected in the media frame...
          frame.on('select', function(){
              var attachment = frame.state().get('selection').first().toJSON();
              $('#default_featured_media').val(attachment.url);
              $('.default-featured-media-field-container').html('<img src="' + attachment.url + '" style="max-width: 300px;" />');
              $('.select-default-featured-media-field').addClass('hidden');
              $('.delete-default-featured-media-field').removeClass('hidden');
          });

          // Finally, open the modal on click
          frame.open();
      });

      // Remove Media
      $('.delete-default-featured-media-field').on('click', function(e){
          e.preventDefault();
          $('#default_featured_media').val('');
          $('.default-featured-media-field-container').html('');
          $('.select-default-featured-media-field').removeClass('hidden');
          $('.delete-default-featured-media-field').addClass('hidden');
      });
    }

    // -------------------------------------------------------------
    //   Auto term source to only change
    // -------------------------------------------------------------
    $(document).on('change', '.autoterm-terms-to-use-field', function (e) {
      hide_show_source_tab_group_fields();
    })

    // -------------------------------------------------------------
    //   Button group(source tab) click
    // -------------------------------------------------------------
    if ($('.taxopress-group-wrap.autoterm-tab-group.source').length > 0) {
      hide_show_source_tab_group_fields();
    }
    $(document).on("click", ".taxopress-group-wrap.autoterm-tab-group.source label", function () {
      var current_button = $(this);
      var button_group   = current_button.closest('.taxopress-group-wrap.autoterm-tab-group.source');
      //remove active class
      button_group.find('label').removeClass('current');
      //add active class to current select
      current_button.addClass('current');
      current_button.addClass('selected');
      // show/hide group based on selected fields
      hide_show_source_tab_group_fields();
    });

    function hide_show_source_tab_group_fields() {
      var tabs = [
        'existing',
        'openai',
        'ibm-watson',
        'dandelion',
        'lseg-refinitiv'
      ];

      tabs.forEach(function(tab) {
        if (
        (tab === 'ibm-watson' && st_admin_localize.enable_ibm_watson_ai_source !== '1') ||
        (tab === 'dandelion' && st_admin_localize.enable_dandelion_ai_source !== '1') ||
        (tab === 'lseg-refinitiv' && st_admin_localize.enable_lseg_ai_source !== '1')
        ) {
          // Hide all UI for this tab if not enabled
          $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).addClass('st-hide-content');
          $('.autoterm-terms-use-' + tab).closest('tr').addClass('st-hide-content');
          $('.autoterm-terms-use-' + tab + '-notice').closest('tr').addClass('st-hide-content');
          return;
        }
        if ($('.fields-control.autoterm-terms-use-' + tab + ':checked').length > 0) {
          $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).addClass('selected');
          $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab + ' input').prop('checked', true);
          $('.autoterm-terms-use-' + tab + ':not(.fields-control)').closest('tr').removeClass('st-hide-content');
        } else {
          $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).removeClass('selected');
          $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab + ' input').prop('checked', false);
          $('.autoterm-terms-use-' + tab + ':not(.fields-control)').closest('tr').addClass('st-hide-content');
        }

        // show/hide all current tab fields
        if ($('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).hasClass("current")) {
          $('.autoterm-terms-use-' + tab).closest('tr').removeClass('st-hide-content');
          // conditional show/hide tab fields if main field is checked
          if ($('.fields-control.autoterm-terms-use-' + tab + ':checked').length > 0) {
            $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).addClass('selected');
            $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab + ' input').prop('checked', true);
            $('.autoterm-terms-use-' + tab + ':not(.fields-control)').closest('tr').removeClass('st-hide-content');
            // show or hide autoterm_use_taxonomy sub field
            if ($('.fields-control.autoterm-terms-use-' + tab + ':checked').hasClass('autoterm_use_taxonomy')) {
              $('.autoterm_useall').closest('tr').removeClass('st-hide-content');
                $('.autoterm_useonly').closest('tr').removeClass('st-hide-content');
                if(!$('.autoterm_useonly').prop('checked')){
                  $('.autoterm_useall').prop('checked', true);
                  $('.autoterm_useonly_options').addClass('st-hide-content');
                }
            }
          } else {
            $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab).removeClass('selected');
            $('.taxopress-group-wrap.autoterm-tab-group.source label.' + tab + ' input').prop('checked', false);
            $('.autoterm-terms-use-' + tab + ':not(.fields-control)').closest('tr').addClass('st-hide-content');
          }
          // make sure notice/description always show even if control is not checked
          $('.autoterm-terms-use-' + tab + '-notice').closest('tr').removeClass('st-hide-content');
        } else {
          $('.autoterm-terms-use-' + tab).closest('tr').addClass('st-hide-content');
          $('.autoterm-terms-use-' + tab + '-notice').closest('tr').addClass('st-hide-content');
        }
      });
      // re-adjust the height
      $('ul.taxopress-tab li.autoterm_terms_tab.active').trigger('click');
    }

    // -------------------------------------------------------------
    //   Auto term when to only change
    // -------------------------------------------------------------
    $(document).on('change', '.autoterm-terms-when-to-field', function (e) {
      hide_show_when_tab_group_fields();
    })

    // -------------------------------------------------------------
    //   Button group(when tab) click
    // -------------------------------------------------------------
    if ($('.taxopress-group-wrap.autoterm-tab-group.when').length > 0) {
      hide_show_when_tab_group_fields();
    }
    $(document).on("click", ".taxopress-group-wrap.autoterm-tab-group.when label", function () {
      var current_button = $(this);
      var button_group   = current_button.closest('.taxopress-group-wrap.autoterm-tab-group.when');
      //remove active class
      button_group.find('label').removeClass('current');
      //add active class to current select
      current_button.addClass('current');
      current_button.addClass('selected');
      // show/hide group based on selected fields
      hide_show_when_tab_group_fields();
    });

    function hide_show_when_tab_group_fields() {
      var tabs = [
        'post',
        'schedule',
        'existing-content',
        'metaboxes'
      ];

      tabs.forEach(function(tab) {
        if ($('.fields-control.autoterm-terms-when-' + tab + ':checked').length > 0) {
          $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab).addClass('selected');
          $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab + ' input').prop('checked', true);
          $('.autoterm-terms-when-' + tab + ':not(.fields-control)').closest('tr').removeClass('st-hide-content');
        } else {
          $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab).removeClass('selected');
          $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab + ' input').prop('checked', false);
          $('.autoterm-terms-when-' + tab + ':not(.fields-control)').closest('tr').addClass('st-hide-content');
        }

        // show/hide all current tab fields
        if ($('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab).hasClass("current")) {
          $('.autoterm-terms-when-' + tab).closest('tr').removeClass('st-hide-content');
          // conditional show/hide tab fields if main field is checked
          if ($('.fields-control.autoterm-terms-when-' + tab + ':checked').length > 0) {
            $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab).addClass('selected');
            $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab + ' input').prop('checked', true);
            $('.autoterm-terms-when-' + tab + ':not(.fields-control)').closest('tr').removeClass('st-hide-content');
            // show or hide autoterm_use_taxonomy sub field
            if ($('.fields-control.autoterm-terms-when-' + tab + ':checked').hasClass('autoterm_use_taxonomy')) {
              $('.autoterm_useall').closest('tr').removeClass('st-hide-content');
                $('.autoterm_useonly').closest('tr').removeClass('st-hide-content');
                if(!$('.autoterm_useonly').prop('checked')){
                  $('.autoterm_useall').prop('checked', true);
                  $('.autoterm_useonly_options').addClass('st-hide-content');
                }
            }
          } else {
            $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab).removeClass('selected');
            $('.taxopress-group-wrap.autoterm-tab-group.when label.' + tab + ' input').prop('checked', false);
            $('.autoterm-terms-when-' + tab + ':not(.fields-control)').closest('tr').addClass('st-hide-content');
          }
          // make sure notice/description always show even if control is not checked
          $('.autoterm-terms-when-' + tab + '-notice').closest('tr').removeClass('st-hide-content');
        } else {
          $('.autoterm-terms-when-' + tab).closest('tr').addClass('st-hide-content');
          $('.autoterm-terms-when-' + tab + '-notice').closest('tr').addClass('st-hide-content');
        }
      });
      // re-adjust the height
      $('ul.taxopress-tab li.autoterm_terms_tab.active').trigger('click');
    }

    function isEmptyOrSpaces(str) {
      return str === null || str.match(/^ *$/) !== null;
    }

        // Event listener for the 'Check Terms to be deleted' button click
        $(document).on('click', '.auto-terms-content.st-delete-unuused-terms #check-terms-btn', function(e) {
          e.preventDefault();
        
          const $tab = $('.auto-terms-content.st-delete-unuused-terms');
          var numberRarely = $tab.find('#number-delete').val();
          var taxonomy = $tab.find('.st-taxonomy-select').val();

          $('.taxopress-response-css').remove();
        
          $('.auto-terms-content.st-delete-unuused-terms #terms-feedback').html('<div class="taxopress-response-css yellow"><p>' + st_admin_localize.checking_terms_message + '</p></div>'); 
        
          $.ajax({
            url: st_admin_localize.ajaxurl,
            method: "POST",
            data: {
                action: 'taxopress_check_delete_terms',
                nonce: st_admin_localize.check_nonce,
                number: numberRarely,
                taxonomy: taxonomy
            },
            success: function(response) {
                if (response.success) {
                    $('.auto-terms-content.st-delete-unuused-terms #terms-feedback').html('<div class="taxopress-response-css yellow"><p>' + response.data.message + '</p></div>');
                } else {
                    $('.auto-terms-content.st-delete-unuused-terms #terms-feedback').html('<div class="taxopress-response-css red"><p>' +response.data.message || st_admin_localize.no_terms_message + '</p></div>');
                }
            },
            error: function(xhr, status, error) {
              $('.auto-terms-content.st-delete-unuused-terms #terms-feedback').html('<div class="taxopress-response-css red"><p>' +st_admin_localize.terms_error + '</p></div>');
            }
        });
    });

    /* Start COPIED FROM PP BLOCKS */
      $(".taxopress-dashboard-settings-control .slider").bind("click", function (e) {
        try {
            e.preventDefault();
            if ($(this).hasClass("slider--disabled")) {
                return false;
            }
            var checkbox = $(this).parent().find("input");
            var isChecked = checkbox.is(":checked") ? 1 : 0;
            var newState = isChecked == 1 ? 0 : 1;
            var feature = checkbox.data("feature");
            var option_key = checkbox.data("option_key");
            var slider = checkbox.parent().find(".slider");
            $.ajax({
                url: st_admin_localize.ajaxurl,
                method: "POST",
                data: { action: "save_taxopress_dashboard_feature_by_ajax", feature: option_key, new_state: newState, nonce: st_admin_localize.check_nonce },
                beforeSend: function () {
                    slider.css("opacity", 0.5);
                },
                success: function () {
                    newState == 1 ? checkbox.prop("checked", true) : checkbox.prop("checked", false);
                    slider.css("opacity", 1);
                    taxopressDynamicSubmenu(feature, newState)
                    taxopressTimerStatus();
                },
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error(jqXHR.responseText);
                    taxopressTimerStatus("error");
                },
            });
        } catch (e) {
            console.error(e);
        }
    });
    function taxopressTimerStatus(type = "success") {
        setTimeout(function () {
            var uniqueClass = "taxopress-floating-msg-" + Math.round(new Date().getTime() + Math.random() * 100);
            var message = type === "success" ? wp.i18n.__("Changes saved!", "capsman-enhanced") : wp.i18n.__(" Error: changes can't be saved.", "capsman-enhanced");
            var instances = $(".taxopress-floating-status").length;
            $("#wpbody-content").after('<span class="taxopress-floating-status taxopress-floating-status--' + type + " " + uniqueClass + '">' + message + "</span>");
            $("." + uniqueClass)
                .css("bottom", instances * 45)
                .fadeIn(1e3)
                .delay(1e4)
                .fadeOut(1e3, function () {
                    $(this).remove();
                });
        }, 500);
    }
    function taxopressDynamicSubmenu(slug, newState) {
        var pMenu = $("#toplevel_page_st_options");
        var cSubmenu = $(pMenu).find("li." + slug + "-menu-item");
        if (cSubmenu.length) {
            newState == 1 ? cSubmenu.removeClass("taxopress-hide-menu-item").find("a").removeClass("taxopress-hide-menu-item") : cSubmenu.addClass("taxopress-hide-menu-item").find("a").addClass("taxopress-hide-menu-item");
        }
    }
    /* end COPIED FROM PP BLOCKS */
    

    function autoterm_option_select2() {
      $('.auto_term_terms_options.select').ppma_select2({
        placeholder: $(this).data("placeholder"),
      });
    }

    // Autocomplete for the manage terms feature
    if ($('.merge-feature-autocomplete, .add-terms-autocomplete, .remove-terms-autocomplete, .rename-terms-autocomplete').length > 0) {
        $('.merge-feature-autocomplete, .add-terms-autocomplete, .remove-terms-autocomplete, .rename-terms-autocomplete').each(function () {

            const taxonomy = $(this).closest('.auto-terms-content').find('.st-taxonomy-select').val();
            const inputField = $(this);
            let showSlug = false;

            // Determine which slug display setting to use based on the input class
            if (inputField.hasClass('merge-feature-autocomplete')) {
                showSlug = st_admin_localize.enable_merge_terms_slug === '1';
            } else if (inputField.hasClass('add-terms-autocomplete')) {
                showSlug = st_admin_localize.enable_add_terms_slug === '1';
            } else if (inputField.hasClass('remove-terms-autocomplete')) {
                showSlug = st_admin_localize.enable_remove_terms_slug === '1';
            } else if (inputField.hasClass('rename-terms-autocomplete')) {
                showSlug = st_admin_localize.enable_rename_terms_slug === '1';
            }

            inputField.autocomplete({
                source: function (request, response) {
                    const lastTerm = request.term.split(',').pop().trim(); // Get the last term after the comma
                    $.ajax({
                        url: st_admin_localize.ajaxurl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'taxopress_autocomplete_terms',
                            term: lastTerm,
                            taxonomy: taxonomy,
                            nonce: st_admin_localize.check_nonce
                        },
                        success: function (data) {
                            response($.map(data, function (item) {
                                // Only include slug in label/value if enabled
                                const displayText = showSlug ? item.name + ' (' + item.slug + ')' : item.name;
                                return {
                                    label: displayText,
                                    value: displayText
                                };
                            }));
                        }
                    });
                },
                minLength: 1,
                focus: function () {
                    // Prevent value insertion on focus
                    return false;
                },
                select: function (event, ui) {
                    const currentValue = inputField.val();
                    const terms = currentValue.split(',').map(term => term.trim());

                    terms[terms.length - 1] = ui.item.value;

                    inputField.val(terms.join(', ') + ', ');

                    return false;
                }
            }).on('keydown', function (event) {
                // Allow autocomplete to trigger after typing a comma
                if (event.key === ',') {
                    $(this).autocomplete('search', '');
                }
            });
        });
    }

    // Merge Terms Bacth Processing
    $(document).on('submit', '.merge-terms-form', function (e) {
      const $form = $(this);
      const mergeType = $form.find('input[name="mergeterm_type"]:checked').val();
      function stripTermName(term) {
        return term.replace(/\s*\(.*?\)$/, '').trim(); // Strips "(slug)" from end
      }
      
      const oldTermsRaw = $form.find('#mergeterm_old').val().split(',').map(function(term) {
        return term.trim();
      }).filter(Boolean);  
      const oldTerms = oldTermsRaw.map(stripTermName);
      const batchSize = 20;

      if ((mergeType === 'different_name' && oldTerms.length > batchSize) || 
          (mergeType === 'same_name' && oldTerms.length > batchSize)) {
        e.preventDefault();

        const newTerm = mergeType === 'different_name' ? $form.find('#mergeterm_new').val().trim() : '';
        const taxonomy = $form.find('input[name="current_taxo"]').val();
        const batches = [];
        let isPaused = false;
        let isCancelled = false;
        let currentIndex = 0;
        let completedBatches = 0;
        let retainedSlugs = [];
        let totalTermsModified = 0;
        let totalPostsAffected = 0;
        const uniquePosts = new Set();
        let lastErrorMessage = '';
        let firstBatchSucceeded = false;

        for (var i = 0; i < oldTerms.length; i += batchSize) {
          batches.push(oldTerms.slice(i, i + batchSize));
        }

        $('.taxopress-response-css').remove();
      

      function init_merge_progress() {
        $('#merge-progress').html(
          '<div class="taxopress-response-css red">' +
            '<p>' + st_admin_localize.merge_large_data + 
              '<span class="taxopress-spinner spinner is-active" style="margin-bottom: 10px;"></span>' + 
              '<button type="button" class="taxopress-dismiss-merge-message notice-dismiss" style="float: right;"></button>' +
              '<span class="merge-controls" style="float: right;">' +
                '<button type="button" id="merge-cancel">' + st_admin_localize.cancel_label + '</button> ' +
                '<button type="button" id="merge-pause">' + st_admin_localize.paused_label + '</button>' +
                '<button type="button" id="merge-continue" style="display:none;">' + st_admin_localize.continue_label + '</button>' +
              '</span>' +
            '</p>' +
          '</div>' +
          '<div class="merge-progress-messages"></div>'
        );

        bind_merge_controls();
      }

      function bind_merge_controls() {
        $(document).off('click', '#merge-cancel, #merge-pause, #merge-continue, .taxopress-dismiss-merge-message');

        $(document).on('click', '#merge-cancel', function () {
          isCancelled = true;
          var stats = '(' + totalTermsModified + ' ' + st_admin_localize.terms_merged_text + ', ' 
          + uniquePosts.size + ' ' + st_admin_localize.posts_updated_text + ')';
          
          var cancelMessage = st_admin_localize.merge_cancelled + (completedBatches > 0 ? ' (' + stats + ')' : '');
          
          $('#merge-progress').html(
            '<div class="taxopress-response-css red"><p>' + cancelMessage + '</p>' +
            '<button type="button" class="taxopress-dismiss-merge-message notice-dismiss" style="float: right;"></button></div>'
          );
        });

        $(document).on('click', '#merge-pause', function() {
          isPaused = true;
          $('.taxopress-spinner').removeClass('is-active');
          $('#merge-pause').hide();
          $('#merge-continue').show();
        });

        $(document).on('click', '#merge-continue', function() {
          isPaused = false;
          $('.taxopress-spinner').addClass('is-active');
          $('#merge-continue').hide();
          $('#merge-pause').show();
          process_merge_batch(currentIndex);
        });

        $(document).on('click', '.taxopress-dismiss-merge-message', function () {
          $(this).closest('.taxopress-response-css').remove();
        });
      }

      function process_merge_batch(index) {
        if (isCancelled || isPaused) {
            currentIndex = index;
            return;
        }
    
        if (index >= batches.length) {
            handle_merge_completion();
            return;
        }
    
        var postData = {
            action: 'taxopress_merge_terms_batch',
            taxonomy: taxonomy,
            old_terms: batches[index],
            merge_type: mergeType,
            nonce: st_admin_localize.check_nonce
        };
    
        if (mergeType === 'same_name') {
            postData.new_term = ''; 
        } else if (mergeType === 'different_name') {
            postData.new_term = newTerm;
        }
    
        $.post(st_admin_localize.ajaxurl, postData, function(response) {
            handle_batch_response(response, index);
        }).fail(function() {
            show_merge_message('red', '<strong>' + st_admin_localize.ajax_merge_terms_error + ' ' + (index + 1) + '</strong>');
        });
    }
    

      function handle_batch_response(response, index) {
        if (response.success) {
          if (mergeType === 'same_name') {
            if (index === 0) {
              firstBatchSucceeded = true;
            }
            if (response.data.retained_slug) {
              retainedSlugs.push(response.data.retained_slug);
            }
          }          
      
          if (response.data.terms_merged) {
            totalTermsModified += parseInt(response.data.terms_merged);
          }
      
          // Track unique post IDs instead of counting blindly
          if (response.data.post_ids) {
            response.data.post_ids.forEach(function(id) {
              uniquePosts.add(id);
            });
          } else if (!response.data.post_ids && response.data.posts_updated) {
            totalPostsAffected += parseInt(response.data.posts_updated);
          }
        
      
          completedBatches++;
          show_merge_message('yellow', st_admin_localize.batch_merge_progress.replace('%1$s', (index + 1)).replace('%2$s', batches.length));
      
          if (index + 1 >= batches.length) {
            handle_merge_completion();
          } else {
            process_merge_batch(index + 1);
          }
        } else {
          if (mergeType === 'same_name' && firstBatchSucceeded) {
            completedBatches++;
            show_merge_message('yellow', st_admin_localize.batch_merge_progress.replace('%1$s', (index + 1)).replace('%2$s', batches.length));
          } else {
            if (typeof response.data === 'object' && response.data.message) {
              var errorMsg = response.data.message;
              lastErrorMessage = errorMsg;
              show_merge_message('red', '<strong>' + st_admin_localize.batch_error_text.replace('%1$s', (index + 1)) + '</strong> ' + errorMsg);
            }
          }

          // Continue to next batch
          if (index + 1 < batches.length) {
            process_merge_batch(index + 1);
          } else {
            handle_merge_completion();
          }
        }
      }


      // Handle merge completion    
      function handle_merge_completion() {
        if (mergeType === 'same_name' && retainedSlugs.length > 1) {
          var finalMergeData = {
            action: 'taxopress_merge_terms_batch',
            taxonomy: taxonomy,
            old_terms: retainedSlugs,
            merge_type: mergeType,
            nonce: st_admin_localize.check_nonce
          };
        
          // Use the first slug as the final retained term name
          finalMergeData.new_term = retainedSlugs[0];
        
          $.ajax({
            url: st_admin_localize.ajaxurl,
            method: 'POST',
            data: finalMergeData,
            async: false,
            success: function(finalResponse) {
              if (finalResponse.success && finalResponse.data.retained_slug) {
                retainedSlugs = [finalResponse.data.retained_slug];
              } else if (finalResponse.data && finalResponse.data.message) {
                lastErrorMessage = finalResponse.data.message;
              }
            }
          });
        }
        
        
        if ((totalTermsModified === 0 && uniquePosts.size === 0) && !(mergeType === 'same_name' && firstBatchSucceeded)) {
          var finalMsg = '<strong>' + st_admin_localize.merge_none_merged + '</strong>';
          if (lastErrorMessage) {
            finalMsg += '<br><em>' + lastErrorMessage + '</em>';
          }
          show_merge_message('red', finalMsg);
        } else {
          var finalTermName = (mergeType === 'same_name') ? retainedSlugs.join(', ') : newTerm;
          var finalMsg = st_admin_localize.merge_success_update.replace('%s', finalTermName) + ', ' + uniquePosts.size + ' ' + st_admin_localize.posts_updated_text;
          show_merge_message('final', '<strong>' + finalMsg + '</strong>');
        }
      
        $form.find('#mergeterm_old').val('');
        if (mergeType === 'different_name') {
          $form.find('#mergeterm_new').val('');
        }
      }
      

      // Show merge message
      function show_merge_message(type, message) {
        if (type === 'final') {
          $('#merge-progress').html(
            '<div class="taxopress-response-css green"><p>' + message + '</p>' +
            '<button type="button" class="taxopress-dismiss-merge-message notice-dismiss" style="float: right;"></button></div>'
          );
        } else {
          $('.merge-progress-messages').html(
            '<div class="taxopress-response-css ' + type + '"><p>' + message + '</p>' +
            '<button type="button" class="taxopress-dismiss-merge-message notice-dismiss" style="float: right;"></button></div>'
          );
        }
      }

      init_merge_progress();

      process_merge_batch(currentIndex);
}
    });

   // Create a reusuable preview panel
    function createPreviewPanel(config) {
    const {
        selectors,
        stateKey,
        getFormData,
        loadOnInit = false,
        formInputSelector = '.taxopress-section input, .taxopress-section select, .taxopress-section textarea'
    } = config;

    const elements = {
        container: $(selectors.container),
        editPanel: $(selectors.editPanel),
        sidebar: $(selectors.sidebar),
        button: $(selectors.button),
        postSelect: selectors.postSelect ? $(selectors.postSelect) : null,
        spinner: $(selectors.spinner),
        results: $(selectors.results),
        handleDiv: $(selectors.handleDiv || `${selectors.container} .handlediv`),
        toggleIndicator: $(selectors.toggleIndicator || `${selectors.container} .toggle-indicator`),
        moveUp: $(selectors.moveUp),
        moveDown: $(selectors.moveDown)
    };

    let isLoading = false;

    function bindEvents() {
        elements.handleDiv.on('click', () => {
            const closed = elements.container.toggleClass('closed').hasClass('closed');
            elements.toggleIndicator.toggleClass('dashicons-arrow-down dashicons-arrow-left');
            localStorage.setItem(`${stateKey}_collapsed`, closed);
        });

        elements.moveUp.on('click', () => handleMove('up'));
        elements.moveDown.on('click', () => handleMove('down'));

        if (elements.button && elements.postSelect) {
            elements.button.on('click', (e) => {
                e.preventDefault();
                loadPreview();
            });
            elements.postSelect.on('change', () => loadPreview());
        }
    }

    function restoreState() {
        const collapsed = localStorage.getItem(`${stateKey}_collapsed`) === 'true';
        const position = localStorage.getItem(`${stateKey}_position`);

        if (collapsed) {
            elements.container.addClass('closed');
            elements.toggleIndicator
                .removeClass('dashicons-arrow-down')
                .addClass('dashicons-arrow-left');
        }

        switch (position) {
            case 'sidebar': moveToSidebar(); break;
            case 'top': moveToMain('top'); break;
            case 'bottom': moveToMain('bottom'); break;
        }
    }

    function handleMove(direction) {
        const pos = getCurrentPosition();
        if (pos === 'sidebar' && direction === 'up') moveToMain('bottom');
        else if (pos === 'top') direction === 'up' ? moveToSidebar() : moveToMain('bottom');
        else if (pos === 'bottom') direction === 'up' ? moveToMain('top') : moveToSidebar();
    }

    function getCurrentPosition() {
        if (elements.container.closest(elements.sidebar).length > 0) return 'sidebar';
        return elements.container.index() < elements.editPanel.index() ? 'top' : 'bottom';
    }

    function moveToMain(position) {
        const method = position === 'top' ? 'insertBefore' : 'insertAfter';
        elements.container.detach()[method](elements.editPanel).removeClass('in-sidebar');
        localStorage.setItem(`${stateKey}_position`, position);
    }

    function moveToSidebar() {
        elements.container.detach().prependTo(elements.sidebar).addClass('in-sidebar');
        localStorage.setItem(`${stateKey}_position`, 'sidebar');
    }

    function displayMessage(type, message) {
        const role = type === 'error' ? 'alert' : 'status';
        const cssClass = type === 'error' ? 'error' : '';
        elements.results.html(`<p class="taxopress-preview-message ${cssClass}" role="${role}" aria-live="polite">${message}</p>`);
    }

    function loadPreview(customId = null) {
        if (isLoading) return;

        const inputId = customId ?? elements.postSelect?.val?.();

        // If a postSelect exists and no value is present, show error
        if (!inputId && elements.postSelect) {
            displayMessage('error', st_admin_localize.post_required);
            return;
        }

        isLoading = true;
        elements.spinner.addClass('is-active');
        elements.button?.prop('disabled', true);
        displayMessage('info', st_admin_localize.loading);

        const formData = getFormData(inputId, formInputSelector);

        $.ajax({
            url: st_admin_localize.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: (response) => {
                if (response.success && response.data.html) {
                    elements.results.html(response.data.html);
                } else {
                    displayMessage('error', response?.data?.message || st_admin_localize.preview_error);
                }
            },
            error: (jqXHR) => {
                displayMessage('error', jqXHR?.responseJSON?.data?.message || st_admin_localize.preview_error);
            },
            complete: () => {
                isLoading = false;
                elements.spinner.removeClass('is-active');
                elements.button?.prop('disabled', false);
            }
        });
    }

    function init() {
        bindEvents();
        restoreState();
        if (loadOnInit && elements.postSelect?.val?.()) loadPreview();
    }

    return { init, loadPreview };
    }

    //related post preview
    if ($('.preview-related-posts').length) {
        createPreviewPanel({
            stateKey: 'taxopress_relatedposts',
            selectors: {
                container: '.relatedposts-preview-container',
                editPanel: '.relatedposts-postbox-container',
                sidebar: '.taxopress-right-sidebar',
                button: '.preview-related-posts',
                postSelect: '#preview-post-select',
                spinner: '.preview-related-posts + .spinner',
                results: '.taxopress-preview-results',
                moveUp: '.taxopress-move-up',
                moveDown: '.taxopress-move-down'
            },
            formInputSelector: '.taxopress-section input, .taxopress-section select, .taxopress-section textarea',
            loadOnInit: true,
            getFormData(postId, selector) {
                const formData = new FormData();
                formData.append('action', 'taxopress_preview_related_posts');
                formData.append('preview_post_id', postId);
                formData.append('nonce', st_admin_localize.check_nonce);
                $(selector).each((_, el) => {
                    const $el = $(el);
                    const name = $el.attr('name');
                    if (!name) return;
                    if ($el.is(':checkbox, :radio') && !$el.is(':checked')) return;
                    formData.append(name, $el.val());
                });
                return formData;
            }
        }).init();
    }

    //terms display preview
    if ($('.terms-display-preview').length) {
    const preview = createPreviewPanel({
        stateKey: 'taxopress_termsdisplay',
        selectors: {
            container: '.terms-display-preview',
            editPanel: '.tagclouds-postbox-container:not(.terms-display-preview)',
            sidebar: '.taxopress-right-sidebar',
            spinner: '.terms-display-preview .spinner',
            results: '#term-display-preview',
            moveUp: '.terms-display-preview .term-panel-move.up',
            moveDown: '.terms-display-preview .term-panel-move.down'
        },
        loadOnInit: false,
        getFormData(displayId) {
            const formData = new FormData();
            formData.append('action', 'taxopress_terms_display_preview');
            formData.append('taxopress_termsdisplay', displayId);
            formData.append('nonce', st_admin_localize.check_nonce);
            return formData;
        }
    });

    preview.init();

    setTimeout(() => {
        const displayId = $('input[name="edited_tagcloud"]').val();
        if (displayId) {
            preview.loadPreview(displayId);
        }
    }, 300);
    }

    // post tags preview
    if ($('.posttags-preview-container').length) {
        createPreviewPanel({
            stateKey: 'taxopress_posttags',
            selectors: {
                container: '.posttags-preview-container',
                editPanel: '.posttags-postbox-container',
                sidebar: '.taxopress-right-sidebar',
                button: '.preview-post-tags',
                postSelect: '#posttags-preview-select',
                spinner: '.preview-post-tags + .spinner',
                results: '.taxopress-preview-results-content',
                moveUp: '.taxopress-move-up',
                moveDown: '.taxopress-move-down'
            },
            formInputSelector: '.taxopress-section input, .taxopress-section select, .taxopress-section textarea',
            loadOnInit: true,
            getFormData(postId, selector) {
                const formData = new FormData();
                formData.append('action', 'taxopress_posttags_preview');
                formData.append('preview_post_id', postId);
                formData.append('nonce', st_admin_localize.check_nonce);
                $(selector).each((_, el) => {
                    const $el = $(el);
                    const name = $el.attr('name');
                    if (name && (!$el.is(':checkbox, :radio') || $el.is(':checked'))) {
                        formData.append(name, $el.val());
                    }
                });
                return formData;
            }
        }).init();
    }

    if ($('.taxopress-post-preview-select').length > 0) {
        taxopressPostPreviewSelect2($('.taxopress-post-preview-select'));
        
        function taxopressPostPreviewSelect2(selector) {
        selector.each(function () {
            const $select = $(this);

            $select.ppma_select2({
                placeholder: $select.data('placeholder') || st_admin_localize.select_post_label,
                allowClear: true,
                minimumInputLength: 0,
                ajax: {
                    url: st_admin_localize.ajaxurl,
                    dataType: 'json',
                    delay: 250,
                    data: function (params) {
                        return {
                            action: 'taxopress_search_posts',
                            search: params.term || '',
                            page: params.page || 1,
                            nonce: st_admin_localize.check_nonce
                        };
                    },
                    processResults: function (data, params) {
                        params.page = params.page || 1;
                        return {
                            results: data.results,
                            pagination: {
                                more: data.more
                            }
                        };
                    },
                    cache: true
                },
                templateSelection: function (data) {
                    return data.text || data.id;
                }
            });

            $select.on('select2:open', function () {
                if (!$select.data('fetched-initial')) {
                    $select.data('fetched-initial', true);
                    $select.select2('open');
                }
            });
        });
        }

    }


  });

})(jQuery);
