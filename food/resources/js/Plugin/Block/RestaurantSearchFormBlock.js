jQuery(document).ready(function() {
	var search_params = {};

	function showLocationSelection () {
		getCurrentLocation(function(info) {
			search_params.user_address = info.formatted_address;
			search_params.latitude = info.latitude;
			search_params.longitude = info.longitude;

			jQuery("#address-modal-value").val(info.formatted_address);
			jQuery('#address-modal').modal();
		});
	}
	
	jQuery('#edit-rest-search').autocomplete({
		lookup: drupalSettings.food.restaurants,
		onSelect: function (suggestion) {
			Drupal.ajax({
				url: drupalSettings.food.registerDirectRestaurantSearchUrl + '?restaurant_id=' + suggestion.data.restaurant_id,
				success: function() {
					document.location.href = suggestion.data.url;
				}
			}).execute();
		}
	});
	
	jQuery('#edit-dish-search').autocomplete({
		lookup: drupalSettings.food.dishes,
		onSelect: function (suggestion) {
			document.location.href = suggestion.data.url;
		}
	});

	var geocompleteConfig = getDeliveryGeocompleteOptions();
    jQuery("#edit-address-search, #address-modal-value")
	.geocomplete(geocompleteConfig)
	.on("geocode:result", function (event, result) {
		search_params.user_address = result.formatted_address;
		search_params.latitude = result.geometry.location.lat();
		search_params.longitude = result.geometry.location.lng();
		
		jQuery('#address-modal').modal('hide');
		jQuery('#order-type-modal').modal();
	});
	
	jQuery('input[name=delivery_mode]').click(function() {
		search_params.delivery_mode = jQuery(this).val();
		document.location.href = drupalSettings.food.restaurantSearchPageUrl + '?' +
										'search_params=' + encodeURIComponent(JSON.stringify(search_params));
	});
	
    jQuery("#edit-address-search, #address-modal-value").dblclick(function() {
		jQuery(this).val('');
	});
	
	jQuery('#address-modal .submit').click(function() {
		search_params.user_address = jQuery("#address-modal-value").val();

		jQuery('#address-modal').modal('hide');
		jQuery('#order-type-modal').modal();
	});
	
	jQuery('.fa-location-arrow').click(function() {
		showLocationSelection();
	});
	
	var cookie = getCookie('Food.LocationPopupShown');
	if(drupalSettings.food.isHomePage && cookie != 'yes') {
		showLocationSelection();
		setCookie('Food.LocationPopupShown', 'yes', 10);
	}
});
