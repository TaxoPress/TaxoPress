function formatItem(row) {
	return row[1];
}

function formatResult(row) {
	return row[1].replace(/(<.+?>)/gi, '');
}

function initAutoComplete( p_target, p_url, p_min_chars ) {
	// Dynamic width ?
	p_width = jQuery( ""+p_target ).width();
	if ( p_width == 0 ) 
		p_width = 200;
	
	// Init autocomplete
	jQuery( ""+p_target ).autocomplete( p_url, {
		width: p_width,
		multiple: true,
		matchContains: true,
		selectFirst: false,
		formatItem: formatItem,
		formatResult: formatResult,
		minChars: p_min_chars
	});
}