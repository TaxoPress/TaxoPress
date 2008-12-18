function addTag(tag) {
	var tag_entry = document.getElementById("old_tags_input");
	if ( tag_entry.value.length > 0 && !tag_entry.value.match(/,\s*$/) ) {
		tag_entry.value += ", ";
	}
	var re = new RegExp(tag + ",");
	if ( !tag_entry.value.match(re) ) {
		tag_entry.value += tag + ", ";
	}
}