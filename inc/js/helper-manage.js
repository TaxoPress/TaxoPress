// Register onclick event
function registerClick() {
	jQuery('#taglist ul li span').bind("click", function(){
		addTag(this.innerHTML, "renametag_old");
		addTag(this.innerHTML, "deletetag_name");
		addTag(this.innerHTML, "addtag_match");
		addTag(this.innerHTML, "tagname_match");
	});
}
	
// Register ajax nav and reload event once ajax data loaded
function registerAjaxNav() {
	jQuery(".navigation a").click(function() {
		jQuery("#ajax_area_tagslist").load(this.href, function(){
  			registerClick();
  			registerAjaxNav();
		});
		return false;
	});
}

// Register initial event
jQuery(document).ready(function() {
	registerClick();
	registerAjaxNav();
});

// Add tag into input
function addTag( tag, name_element ) {
	var input_element = document.getElementById( name_element );

	if ( input_element.value.length > 0 && !input_element.value.match(/,\s*$/) )
		input_element.value += ", ";

	var comma = new RegExp(tag + ",");
	if ( !input_element.value.match(comma) )
		input_element.value += tag + ", ";

	return true;
}