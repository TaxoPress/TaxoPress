(function( $ ) {
	'use strict';

	/**
	 * All of the code for admin-facing JavaScript source
	 * should reside in this file.
	 */

  $(document).ready(function () {

    // -------------------------------------------------------------
    //   Show taxonomy option based on selected CPT
    // -------------------------------------------------------------
    $(document).on('change paste keyup', '.st-cpt-select', function (e) {
      //taxonomy
      var val = this.value;
      var options = document.getElementsByClassName('st-taxonomy-select')[0].options;
      var new_val = null;
      for (var i = 0 ; i < options.length; i++) {
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

  
  });

})( jQuery );
