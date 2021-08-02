Drupal.behaviors.opencloseform = {
  attach: function (context, settings) {
     jQuery(".weekday_name").on('change', function() {
            var index = jQuery('.weekday_name').index(this);
            if (jQuery(this).is(":checked")) {
                jQuery('.open_start_time:eq(' + index + ')').prop('disabled', false);
                jQuery('.open_end_time:eq(' + index + ')').prop('disabled', false);
                jQuery('.open_time_apply_btn:eq(' + index + ')').show();
                jQuery('.del_start_time:eq(' + index + ')').prop('disabled', false);
                jQuery('.del_end_time:eq(' + index + ')').prop('disabled', false);
                jQuery('.del_time_apply_btn:eq(' + index + ')').show();
            } else {
                jQuery('.open_start_time:eq(' + index + ')').val('');
                jQuery('.open_end_time:eq(' + index + ')').val('');
                jQuery('.del_start_time:eq(' + index + ')').val('');
                jQuery('.del_end_time:eq(' + index + ')').val('');
                jQuery('.open_start_time:eq(' + index + ')').prop('disabled', true);
                jQuery('.open_end_time:eq(' + index + ')').prop('disabled', true);
                jQuery('.open_time_apply_btn:eq(' + index + ')').hide();
                jQuery('.del_start_time:eq(' + index + ')').prop('disabled', true);
                jQuery('.del_end_time:eq(' + index + ')').prop('disabled', true);
                jQuery('.del_time_apply_btn:eq(' + index + ')').hide();
            }
        });

     //copy open and close time
    jQuery(".open_time_apply_btn").on('click', function() {
        var index = jQuery('.open_time_apply_btn').index(this);
        var openTime = jQuery('.open_start_time:eq(' + index + ')').val();
        var closeTime = jQuery('.open_end_time:eq(' + index + ')').val();

        if (jQuery.trim(openTime) != "") {
            jQuery('.open_start_time').not(":disabled").val(openTime);
        }

        if (jQuery.trim(closeTime) != "") {
            jQuery('.open_end_time').not(":disabled").val(closeTime);
        }
    });

     //copy delivery start and end time
    jQuery(".del_time_apply_btn").on('click', function() {
        var index = jQuery('.del_time_apply_btn').index(this);
        var delStartTime = jQuery('.del_start_time:eq(' + index + ')').val();
        var delEndTime = jQuery('.del_end_time:eq(' + index + ')').val();

        if (jQuery.trim(delStartTime) != "") {
            jQuery('.del_start_time').not(":disabled").val(delStartTime);
        }

        if (jQuery.trim(delEndTime) != "") {
            jQuery('.del_end_time').not(":disabled").val(delEndTime);
        }

    });

    jQuery('.timepicker').timepicker({
        timeFormat: 'HH:mm',
        interval: 30,
        //minTime: '00',
        maxTime: '23:59',
        startTime: '00:00',
        dynamic: false,
        dropdown: true,
        scrollbar: true
    });

    jQuery("#food-open-close-form button[type='submit']").on('click', function(e) {

        var weekdays = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        // check restaurant open & close time not empty for day checked
        var errorCounter = 0, checkedCounter = 0;
        jQuery(".weekday_name").each(function(index) {
            if (jQuery(this).is(":checked")) {
                checkedCounter++;
                if (jQuery('.open_start_time:eq(' + index + ')').val() == "") {
                    alert("Please select open time for " + weekdays[index]);
                    errorCounter++;
                }

                if (jQuery('.open_end_time:eq(' + index + ')').val() == "") {
                    alert("Please select close time for " + weekdays[index]);
                    errorCounter++;
                }

            }
        });

        if (parseInt(errorCounter) > 0) {
            return (false);
        }

        if (parseInt(checkedCounter) == 0) {
            alert('Please select restaurant open and close time.')
            return (false);
        }
    });
}
};