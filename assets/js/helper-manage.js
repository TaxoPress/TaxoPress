jQuery(document).ready(function() {
    jQuery("#term-list-inner a").click(function(event) {
        event.preventDefault();

        st_add_term(this.innerHTML, "renameterm_old"); // Rename terms
        st_add_term(this.innerHTML, "deleteterm_name"); // Delete terms
        st_add_term(this.innerHTML, "addterm_match"); // Add terms
        //st_add_term(this.innerHTML, "termname_match"); // Edit slug

        return false;
    });
});

// Add tag into input
function st_add_term(tag, name_element) {
    var input_element = document.getElementById(name_element);

    if (input_element.value.length > 0 && !input_element.value.match(/,\s*$/))
        input_element.value += ", ";

    var comma = new RegExp(tag + ",");
    if (!input_element.value.match(comma))
        input_element.value += tag + ", ";

    return true;
}