jQuery(document).ready(function() {
	if ( document.getElementById('adv-tags-input') ) {
		var tags_input = new BComplete('adv-tags-input');
		tags_input.setData(collection);
	}
});