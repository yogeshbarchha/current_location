jQuery(document).ready(function() {
    jQuery(".deal_row_toggle").on('change', function() {
        var index = jQuery('.deal_row_toggle').index(this);
        if (jQuery(this).is(":checked")) {
            jQuery('.deal_row_pct:eq(' + index + ')').prop('disabled', false);
        } else {
            jQuery('.deal_row_pct:eq(' + index + ')').prop('disabled', true);
        }
    });
});

