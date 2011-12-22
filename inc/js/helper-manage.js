jQuery(document).ready(function() {
	jQuery("#term-list-inner a").click(function(event) {
		event.preventDefault();
		
		addTerm(this.innerHTML, "renameterm_old"); // Rename terms
		addTerm(this.innerHTML, "deleteterm_name"); // Delete terms
		addTerm(this.innerHTML, "addterm_match"); // Add terms
		//addTerm(this.innerHTML, "termname_match"); // Edit slug
		
		return false;
	});
});

// Add tag into input
function addTerm( tag, name_element ) {
	var input_element = document.getElementById( name_element );

	if ( input_element.value.length > 0 && !input_element.value.match(/,\s*$/) )
		input_element.value += ", ";

	var comma = new RegExp(tag + ",");
	if ( !input_element.value.match(comma) )
		input_element.value += tag + ", ";

	return true;
}