function formatItem(row) {
	return row[1] + " (<strong>term id: " + row[0] + "</strong>)";
}
function formatResult(row) {
	return row[1].replace(/(<.+?>)/gi, '');
}

function initAutoComplete( target, u_rl, w_idth ) {
	jQuery(document).ready(function () {
		jQuery( ""+target ).autocomplete( u_rl, {
			width: width,
			multiple: w_idth,
			matchContains: true,
			formatItem: formatItem,
			formatResult: formatResult
		});
	});
}