(function ($) {
  'use strict';

  /**
   * All of the code for admin-facing JavaScript source
   * should reside in this file.
   */

  $(document).ready(function () {

    
    
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
    $(document).on('change paste keyup', '.st-post-type-select', function (e) {
      var val = $(this).val(),
        data_post,
        new_val = null,
        options = document.getElementsByClassName('st-post-taxonomy-select')[0].options;

      for (var i = 0; i < options.length; i++) {
        data_post = options[i].attributes["data-post_type"].value;
        data_post = data_post.split(',');

        if (data_post.includes(val) || val === 'st_all_posttype' || val === 'st_current_posttype' || val === '') {
          if (!new_val) {
            new_val = options[i].value;
          }
          options[i].classList.remove("st-hide-content");
        } else {
          options[i].classList.add("st-hide-content");
        }
      }
      if ($('.st-post-taxonomy-select').children(':selected').hasClass('st-hide-content')) {
        document.getElementsByClassName('st-post-taxonomy-select')[0].value = new_val;
      }
    });
    if ($('.st-post-type-select').length > 0) {
      $('.st-post-type-select').trigger('change');
    }

    // -------------------------------------------------------------
    //   Add auto tags suggestion tag
    // -------------------------------------------------------------
    $(document).on('click', '.st-add-suggestion-input', function (e) {
      e.preventDefault();
      $('.auto-terms-keyword-list').append('<input type="text" name="auto_list[]" /> <input class="st-delete-suggestion-input" type="button" value="Delete"/>');
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
      $('.taxopress-tab-content table.' + tab_content).show();

      $('.visbile-table').css('display', '');

      //set tab height
      if ($('.taxopress-tab-content').height() > $('.taxopress-tab').height()) {
        $('.taxopress-tab').css('height', $('.taxopress-tab-content').height());
      }
      //set active tab value
      $('.taxopress-active-subtab').val(tab_content);
    });

    if ($('.taxopress-tab-content').length > 0) {
      //set tab height
      if ($('.taxopress-tab-content').height() > $('.taxopress-tab').height()) {
        $('.taxopress-tab').css('height', $('.taxopress-tab-content').height());
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
      if (!$('.autoterm_post_status_publish').prop("checked") && !$('.autoterm_post_status_draft').prop("checked")) {
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
    //   Auto term all content
    // -------------------------------------------------------------
    $(document).on('click', '.taxopress-autoterm-all-content', function (e) {
        e.preventDefault();
        $('.auto-term-content-result').html('');
        $('.auto-term-content-result-title').html('');
        var auto_term_id = Number($('input[name="edited_autoterm"]').val());
        var button = $(this);
        auto_terms_all_content(0, auto_term_id, button);
      });

      function auto_terms_all_content(start_from, auto_term, button){

          $(".taxopress-spinner").addClass("is-active");
          button.attr('disabled', true);
          var data = {
            'action': 'taxopress_autoterms_content_by_ajax',
            'auto_term_id': auto_term,
            'start_from': start_from,
            'security': st_admin_localize.check_nonce,
          };
          $.post(st_admin_localize.ajaxurl, data, function (response) {
              if(response.status === 'error') {
                  $('.auto-term-content-result').append('<li><font color="red">'+response.message+'</font></li>');
                $(".taxopress-spinner").removeClass("is-active");
                button.attr('disabled', false);
              }else if(response.status === 'progress') {
                $('.auto-term-content-result-title').html(''+response.percentage+'');
                $('.auto-term-content-result').prepend(response.content);
                //send next batch
                auto_terms_all_content(response.done, auto_term, button);
              }else if(response.status === 'sucess') {
                $('.auto-term-content-result-title').html(''+response.percentage+'');
                $('.auto-term-content-result').prepend('<li><font color="green">'+response.message+'</font></li>');
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
      if ($('.suggest_term_use_dandelion').length > 0) {
        if ($('.suggest_term_use_dandelion').prop("checked")) {
          $('.terms_datatxt_access_token').closest('tr').removeClass('st-hide-content');
          $('.terms_datatxt_min_confidence').closest('tr').removeClass('st-hide-content');
        } else {
          $('.terms_datatxt_access_token').closest('tr').addClass('st-hide-content');
          $('.terms_datatxt_min_confidence').closest('tr').addClass('st-hide-content');
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
      if ($('.suggest_term_use_opencalais').length > 0) {
        if ($('.suggest_term_use_opencalais').prop("checked")) {
          $('.terms_opencalais_key').closest('tr').removeClass('st-hide-content');
        } else {
          $('.terms_opencalais_key').closest('tr').addClass('st-hide-content');
        }
      }
    }


    // -------------------------------------------------------------
    //   Auto term Existing taxonomy terms check
    // -------------------------------------------------------------
    $(document).on('click', '.autoterm_use_taxonomy', function (e) {
      autoterm_use_taxonomy_action();
    });
    autoterm_use_taxonomy_action();
    function autoterm_use_taxonomy_action() {
      if ($('.autoterm_use_taxonomy').length > 0) {
        if ($('.autoterm_use_taxonomy').prop("checked")) {
          $('.autoterm_useall').closest('tr').removeClass('st-hide-content');
          $('.autoterm_useonly').closest('tr').removeClass('st-hide-content');
          if(!$('.autoterm_useonly').prop('checked')){
            $('.autoterm_useall').prop('checked', true);
            $('.autoterm_useonly_options').addClass('st-hide-content');
          }
        } else {
          $('.autoterm_useall').closest('tr').addClass('st-hide-content');
          $('.autoterm_useonly').closest('tr').addClass('st-hide-content');
          $('.autoterm_useonly_options').addClass('st-hide-content');
          $('.autoterm_useall').prop('checked', false);
          $('.autoterm_useonly').prop('checked', false);
        }
      }
    }


    function isEmptyOrSpaces(str) {
      return str === null || str.match(/^ *$/) !== null;
    }

  });

})(jQuery);
