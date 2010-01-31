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
		
		var newtags = jQuery('#tags-input').val();
		newtags += ',' + tag;
		
		// massage
		newtags = newtags.replace( /\s+,+\s*/g, ',' ).replace( /,+/g, ',' ).replace( /,+\s+,+/g, ',' ).replace( /,+\s*$/g, '' ).replace( /^\s*,+/g, '' );
		jQuery('#tags-input').val( newtags );
		tag_update_quickclicks();
	
	}
}
