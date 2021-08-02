Drupal.behaviors.liveorders = {
  attach: function (context, settings) {

var geocompleteConfig = getDeliveryGeocompleteOptions();
    jQuery("input[name='address_line']")
	.geocomplete(geocompleteConfig)
	.on("geocode:result", function (event, result) {
		jQuery('input[name="address_line"]').val(result.formatted_address);
		jQuery('input[name=address_line_latitude]').val(result.geometry.location.lat());
		jQuery('input[name=address_line_longitude]').val(result.geometry.location.lng());
	});

 }
};
