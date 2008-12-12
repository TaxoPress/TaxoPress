function addTag(tag) {
	var tag_entry = document.getElementById("tags-input");
	if ( tag_entry.value.length > 0 && !tag_entry.value.match(/,\s*$/) ) {
		tag_entry.value += ", ";
	}
	var re = new RegExp(tag + ",");
	if ( !tag_entry.value.match(re) ) {
		tag_entry.value += tag + ", ";
	}
}

Event.observe(window, 'load', function() {
	Event.observe('post', 'submit', trimTagsBeforeSend);
});
function trimTagsBeforeSend() {
	var tag_entry = document.getElementById("tags-input");
	var taille = tag_entry.value.length;

	if ( tag_entry.value.substr(taille - 2 , 2) == ', ' ) {
		tag_entry.value = tag_entry.value.substr( 0, taille - 2);
	}

	if ( tag_entry.value.substr(taille - 1, 1) == ',' ) {
		tag_entry.value = tag_entry.value.substr( 0, taille - 1);
	}
}