function st_split (val) {
  return val.split(/,\s*/)
}

function st_extract_last (term) {
  return st_split(term).pop()
}

const TAXOPRESS_NONCE = (typeof window.taxopressNonce !== 'undefined') ? window.taxopressNonce
                      : (typeof st_autocomplete_data !== 'undefined' ? st_autocomplete_data.check_nonce : '');


// Universal nonce attach
function tpEnsureNonce(url){
  if (!TAXOPRESS_NONCE) return url;
  if (url.indexOf('action=simpletags_autocomplete') !== -1 && url.indexOf('nonce=') === -1){
    url += (url.indexOf('?') > -1 ? '&' : '?') + 'nonce=' + encodeURIComponent(TAXOPRESS_NONCE);
  }
  return url;
}

// Patch getJSON (legacy calls)
(function($){
  const _getJSON = $.getJSON;
  $.getJSON = function(url, data, success){
    if (typeof url === 'string') {
      url = tpEnsureNonce(url);
    }
    return _getJSON.call(this, url, data, success);
  };
  $.ajaxPrefilter(function(opts){
    if (!TAXOPRESS_NONCE) return;
    if (opts.url && opts.url.indexOf('action=simpletags_autocomplete') !== -1 && opts.url.indexOf('nonce=') === -1){
      opts.url = tpEnsureNonce(opts.url);
    }
    if (typeof opts.data === 'string' &&
        opts.data.indexOf('action=simpletags_autocomplete') !== -1 &&
        opts.data.indexOf('nonce=') === -1){
      opts.data += '&nonce=' + encodeURIComponent(TAXOPRESS_NONCE);
    }
  });
})(jQuery);

function st_init_autocomplete(p_target, p_url, p_min_chars) {
  // Add security nonce to URL if not present
   p_url = tpEnsureNonce(p_url);

  // Dynamic width
  let p_width = jQuery(p_target).width();
  if (p_width === 0) {
    p_width = 200;
  }

  // Init jQuery UI autocomplete
  jQuery(p_target).each(function () {
    const $input = jQuery(this);

    $input.on('keydown', function (event) {
      if (event.keyCode === jQuery.ui.keyCode.TAB &&
        jQuery(this).data('ui-autocomplete')?.menu.active) {
        event.preventDefault();
      }
    }).autocomplete({
      minLength: p_min_chars,
      source: function (request, response) {
        const $tab = $input.closest('.inside, .auto-terms-content, .taxopress-tab');
        let selectedTaxonomy = $tab.find('.st-taxonomy-select').val()
        || $tab.find('.st-post-taxonomy-select').val()
        || jQuery('.st-taxonomy-select').first().val()
        || jQuery('.st-post-taxonomy-select').first().val()
        || 'post_tag';

        // Use updated URL with taxonomy
        let dynamic_url = replaceUrlParam(p_url, 'taxonomy', selectedTaxonomy);
        dynamic_url = tpEnsureNonce(dynamic_url);
        jQuery.getJSON(dynamic_url, { term: st_extract_last(request.term) }, response);
      },
      focus: function () {
        return false;
      },
      select: function (event, ui) {
        const terms = st_split(this.value);
        terms.pop();
        terms.push(ui.item.value);
        terms.push('');
        this.value = terms.join(', ');
        return false;
      }
    });
  });
}

function replaceUrlParam(url, paramName, paramValue)
{
    if (paramValue == null) {
        paramValue = '';
    }
    var pattern = new RegExp('\\b('+paramName+'=).*?(&|#|$)');
    if (url.search(pattern)>=0) {
        return url.replace(pattern,'$1' + paramValue + '$2');
    }
    url = url.replace(/[?#]$/,'');
    return url + (url.indexOf('?')>0 ? '&' : '?') + paramName + '=' + paramValue;
}

jQuery(document).ready(function($) {

  $('.simple-tags-dismiss-rating').on('click', function(e) {
    e.preventDefault();

    localStorage.setItem('simple-tags-dismiss-rating', true);
    $('.simple-tags-review-box').hide();

    return false;
  });

  if (localStorage.getItem('simple-tags-dismiss-rating')) {
    $('.simple-tags-review-box').hide();
  }

  // -------------------------------------------------------------
  //   Auto terms term auto complete
  // -------------------------------------------------------------
  var p_url = ajaxurl+'?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy='
      + $('.st-post-taxonomy-select option:selected').val();
  p_url = tpEnsureNonce(p_url);
  autoterms_init_autocomplete('.specific_terms_input', p_url, 1);
  autoterms_init_autocomplete('.auto-terms-stopwords', p_url, 1);

  $(document).on('click', '.autoterm_terms_tab', function () {
      var p2 = ajaxurl+'?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy='
          + $('.st-post-taxonomy-select option:selected').val();
      p2 = tpEnsureNonce(p2);
      autoterms_init_autocomplete('.specific_terms_input', p2, 1);
      autoterms_init_autocomplete('.auto-terms-stopwords', p2, 1);
  });

  $(document).on('change', '.st-post-taxonomy-select', function(){
      var p3 = ajaxurl+'?action=simpletags_autocomplete&stags_action=helper_js_collection&taxonomy='
          + $(this).val();
      p3 = tpEnsureNonce(p3);
      autoterms_init_autocomplete('.specific_terms_input', p3, 1);
      autoterms_init_autocomplete('.auto-terms-stopwords', p3, 1);
  });

  function autoterms_init_autocomplete (p_target, p_url, p_min_chars) {

      // Add security nonce to URL if not present
       p_url = tpEnsureNonce(p_url);

      // Dynamic width ?
      var p_width = jQuery('' + p_target).width()
      if (p_width === 0) {
        p_width = 200
      }
      // Init jQuery UI autocomplete
      jQuery(p_target).autocomplete({
        minLength: p_min_chars,
        source: function (request, response) {
          // inside autoterms_init_autocomplete source:
        var secured = tpEnsureNonce(p_url);
        jQuery.getJSON(secured, { term: st_extract_last(request.term) }, response);
        },
        focus: function () {
          // prevent value inserted on focus
          return false
        },
        select: function (event, ui) {
          var terms = st_split(this.value)
          // remove the current input
          terms.pop()
          // add the selected item
          terms.push(ui.item.value)
          // add placeholder to get the comma-and-space at the end
          terms.push('')
          this.value = terms.join(', ')
          $('.specific_terms_input').trigger('change')
          return false
        }
      })
  }

});
