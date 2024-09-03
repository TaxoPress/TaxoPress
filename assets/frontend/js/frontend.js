jQuery(document).ready(function($) {
    // -------------------------------------------------------------
    //   Show More / Close Table Functions
    // -------------------------------------------------------------
    function showMore() {
        var rows = $(".taxopress-table-row");
        rows.each(function (index, row) {
            if (index >= 6) {
                $(row).show();
            }
        });
        $(".taxopress-see-more-link").hide();
        $(".taxopress-close-table-link").show();
    }
    
    function closeTable() {
        var rows = $(".taxopress-table-row");
        rows.each(function (index, row) {
            if (index >= 6) {
                $(row).hide();
            }
        });
        $(".taxopress-close-table-link").hide();
        $(".taxopress-see-more-link").show();
    }
    
    // Event listeners for Show More and Close Table
    $(document).on('click', '.taxopress-see-more-link', function (e) {
        e.preventDefault();
        showMore();
    });

    $(document).on('click', '.taxopress-close-table-link', function (e) {
        e.preventDefault();
        closeTable();
    });
});