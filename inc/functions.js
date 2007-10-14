if(document.all && !document.getElementById) {
	document.getElementById = function(id) { 
		return document.all[id];
	}
}

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
	var tag_entry_value = document.getElementById("tags-input").value;

	if ( tag_entry_value.substr(tag_entry_value.length - 2 , 2) == ', ' ) {
		tag_entry_value = tag_entry_value.substr( 0, tag_entry_value.length - 2);
	}
	
	if ( tag_entry_value.substr(tag_entry_value.length - 1, 1) == ',' ) {
		tag_entry_value = tag_entry_value.substr( 0, tag_entry_value.length - 1);
	}
}

function ST_WindowOnload( f ) {
	var prev = window.onload;
	window.onload = function(){
		if( prev ) {
			prev();
		}
		f();
	}
}