jQuery(document).ready(function($) {
    // -------------------------------------------------------------
    //   Show More / Close Table Functions
    // -------------------------------------------------------------
    function showMore() {
        var rows = $(".table-row");
        rows.each(function (index, row) {
            if (index >= 6) {
                $(row).show();
            }
        });
        $(".see-more-link").hide();
        $(".close-table-link").show();
    }
    
    function closeTable() {
        var rows = $(".table-row");
        rows.each(function (index, row) {
            if (index >= 6) {
                $(row).hide();
            }
        });
        $(".close-table-link").hide();
        $(".see-more-link").show();
    }
    
    // Event listeners for Show More and Close Table
    $(document).on('click', '.see-more-link', function (e) {
        e.preventDefault();
        showMore();
    });

    $(document).on('click', '.close-table-link', function (e) {
        e.preventDefault();
        closeTable();
    });
});