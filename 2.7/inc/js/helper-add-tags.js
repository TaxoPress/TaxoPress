function addTag(tag) {
	if ( document.getElementById('adv-tags-input') ) {
		var tag_entry = document.getElementById("adv-tags-input");
		if ( tag_entry.value.length > 0 && !tag_entry.value.match(/,\s*$/) )
			tag_entry.value += ", ";

		var comma = new RegExp(tag + ",");
		if ( !tag_entry.value.match(comma) )
			tag_entry.value += tag + ", ";
	}
}