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

    // -------------------------------------------------------------
    //   Maximum number of related posts / Maximum related posts to display
    // -------------------------------------------------------------
    const inputMax = $('#input_max');
    const numberDisplay = $('#number');
    const helpText = $('#taxopress_maxposts_helptext');

    function validateInput() {
        const maxVal = parseInt(inputMax.val(), 10); //used 10 to specify the decimal
        const displayVal = parseInt(numberDisplay.val(), 10);

        if (displayVal > maxVal) {
            helpText.show();
            numberDisplay[0].setCustomValidity('This value cannot exceed the maximum number of related posts.');
        } else {
            helpText.hide();
            numberDisplay[0].setCustomValidity('');
        }
    }

    // Attach event listeners to both fields to validate on change
    $('#input_max, #number').on('input', validateInput);

    // Initial validation
    validateInput();
});