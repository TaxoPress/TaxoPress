function getContentFromEditor() {
	var data = '';
	if ( (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden() ) { // Tiny MCE
		tinyMCE.triggerSave();
		data = jQuery("#content").val().stripTags();
	} else if ( typeof FCKeditorAPI != "undefined" ) { // FCK Editor
		var oEditor = FCKeditorAPI.GetInstance('content') ;
		data = oEditor.GetHTML().stripTags();
	} else if ( typeof WYM_INSTANCES != "undefined" ) { // Simple WYMeditor
		data = WYM_INSTANCES[0].xhtml().stripTags();
	} else { // No editor, just quick tags
		data = jQuery("#content").val().stripTags();
	}
	return data;
}

function registerClickTags() {
	jQuery("#suggestedtags .inside span").click(function() { addTag(this.innerHTML); });
	jQuery('#st_ajax_loading').hide();
	if ( jQuery('#suggestedtags .inside').css('display') != 'block' ) {
		jQuery('#suggestedtags').toggleClass('closed');
	}
}

jQuery(document).ready(function() {
	// Yahoo API
	jQuery("a.yahoo_api").click(function() {
		jQuery('#st_ajax_loading').show();
		jQuery("#suggestedtags .inside").load( site_url + '/wp-admin/admin.php?st_ajax_action=tags_from_yahoo', {content:getContentFromEditor(),title:jQuery("#title").val(),tags:jQuery("#tags-input").val()}, function(){
			registerClickTags();
		});
		return false;
	});	
	// Local Tags Database
	jQuery("a.local_db").click(function() {
		jQuery('#st_ajax_loading').show();
		jQuery("#suggestedtags .inside").load( site_url + '/wp-admin/admin.php?st_ajax_action=tags_from_local_db', {content:getContentFromEditor(),title:jQuery("#title").val()}, function(){
			registerClickTags();
		});
		return false;
	});
	// Tag The Net API
	jQuery("a.ttn_api").click(function() {
		jQuery('#st_ajax_loading').show();
		jQuery("#suggestedtags .inside").load( site_url + '/wp-admin/admin.php?st_ajax_action=tags_from_tagthenet', {content:getContentFromEditor(),title:jQuery("#title").val()}, function(){
			registerClickTags();
		});
		return false;
	});
});