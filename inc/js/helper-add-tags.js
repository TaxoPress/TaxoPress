function addTag(tag) {
	// Trim tag
	tag = tag.replace( /^\s+/, '' ).replace( /\s+$/, '' );
	
	if ( document.getElementById("adv-tags-input") ) { // Tags input from Simple Tags
		
		var tag_entry = document.getElementById("adv-tags-input");
		if ( tag_entry.value.length > 0 && !tag_entry.value.match(/,\s*$/) ) {
			tag_entry.value += ", ";
		}
		
		var re = new RegExp(tag + ",");
		if ( !tag_entry.value.match(re) ) {
			tag_entry.value += tag + ", ";
		}
	
	} else { // Default tags input from WordPress

		tag.replace( /\s+,+\s*/g, ',' ).replace( /,+/g, ',' ).replace( /,+\s+,+/g, ',' ).replace( /,+\s*$/g, '' ).replace( /^\s*,+/g, '' );
		if (jQuery('#new-tag-post_tag').val() == "") {
			jQuery('#new-tag-post_tag').val(tag);
		} else {
			jQuery('#new-tag-post_tag').val(jQuery('#new-tag-post_tag').val() + ", " + tag);
		}
		jQuery('.tagadd').click();
	
	}
}
