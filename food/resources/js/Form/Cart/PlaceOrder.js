(function ($, Drupal, drupalSettings) {
  jQuery('#edit-tip-pct').change(function () {
    Drupal.ajax({
      url: drupalSettings.food.cart.setTipPctUrl.replace('10000',
        jQuery(this).val())
    }).execute();
  });

  jQuery('#edit-tip-pct').change(function () {
    var val = $(this).val();
    if (val !== '0') {
      $('.form-item-tip-manual-amount').hide();
    }
    else {
      $('#edit-tip-manual-amount').val(0);
      $('.form-item-tip-manual-amount').show();
    }
  });

  jQuery('input[id=edit-tip-manual-amount]').blur(function () {
    var tipValue = jQuery(this).val();
    if (tipValue >= 0) {
      var total = Math.round(parseFloat(tipValue.toString()) + parseFloat(jQuery("#order-sub-total").html().toString()));
      var percentageOfValue = Math.round((tipValue / parseFloat(jQuery("#order-sub-total").html().toString())) * 100);
      Drupal.ajax({
        url: drupalSettings.food.cart.setTipPctUrl.replace('10000',
          percentageOfValue)
      }).execute();
    }
  });

  jQuery('#edit-submit').click(function () {
    if (jQuery('div[data-drupal-selector="edit-card"]')
        .is(':visible') == false) {
      //Cash
      setTimeout(function () {
        //Disable after 100 ms to enable click to be processed.
        jQuery('#edit-submit').attr('disabled', 'disabled')
      }, 100);
    }
  });

})(jQuery, Drupal, drupalSettings);
