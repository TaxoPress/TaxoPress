function initProtoMultiSelect( input_id, div_id, ajax_url ) {
	document.observe('dom:loaded', function() {
		// init
		tlist2 = new FacebookList( input_id, div_id, {
			fetchFile: ajax_url
		});
		
		// fetch and feed
		new Ajax.Request( ajax_url, {
			onSuccess: function(transport) {
				transport.responseText.evalJSON(true).each(function(t) {
					tlist2.autoFeed(t)
				});
			}
		});
	});
}