jQuery(document).ready(function() {
    jQuery("#suggestedtags .hndle span").html(html_entity_decode(stHelperSuggestedTagsL10n.title_bloc));
    jQuery("#suggestedtags .inside .container_clicktags").html(stHelperSuggestedTagsL10n.content_bloc);

    // Generica all for autocomplet API
    jQuery("a.suggest-action-link").click(function(event) {
        event.preventDefault();

        jQuery('#st_ajax_loading').show();

        jQuery("#suggestedtags .container_clicktags").load(ajaxurl + '?action=simpletags&stags_action=' + jQuery(this).data("ajaxaction"), {
            content: getContentFromEditor(),
            title: jQuery("#title").val(),
            tags: jQuery("#tags-input").val()
        }, function() {
            registerClickTags();
        });
        return false;
    });
});

function getContentFromEditor() {
    var data = '';

    if ((typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden()) { // Tiny MCE
        var ed = tinyMCE.activeEditor;
        if ('mce_fullscreen' == ed.id) {
            tinyMCE.get('content').setContent(ed.getContent({
                format: 'raw'
            }), {
                format: 'raw'
            });
        }
        tinyMCE.get('content').save();
        data = jQuery("#content").val();
    } else if (typeof FCKeditorAPI != "undefined") { // FCK Editor
        var oEditor = FCKeditorAPI.GetInstance('content');
        data = oEditor.GetHTML().stripTags();
    } else if (typeof WYM_INSTANCES != "undefined") { // Simple WYMeditor
        data = WYM_INSTANCES[0].xhtml();
    } else { // No editor, just quick tags
        data = jQuery("#content").val();
    }

    // Trim data
    data = data.replace(/^\s+/, '').replace(/\s+$/, '');
    if (data !== '') {
        data = strip_tags(data);
    }

    return data;
}

function registerClickTags() {
    jQuery("#suggestedtags .container_clicktags span").click(function(event) {
        event.preventDefault();

        addTag(this.innerHTML);
    });

    jQuery('#st_ajax_loading').hide();
    if (jQuery('#suggestedtags .inside').css('display') != 'block') {
        jQuery('#suggestedtags').toggleClass('closed');
    }
}

function html_entity_decode(str) {
    var ta = document.createElement("textarea");
    ta.innerHTML = str.replace(/</g, "&lt;").replace(/>/g, "&gt;");
    toReturn = ta.value;
    ta = null;
    return toReturn;
}

function strip_tags(str) {
    return str.replace(/&lt;\/?[^&gt;]+&gt;/gi, "");
}