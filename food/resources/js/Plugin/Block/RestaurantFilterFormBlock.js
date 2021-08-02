jQuery(document).ready(function() {
	var search_params = getQueryParamValue('search_params');
	if(!search_params) {
		try {
			search_params = drupalSettings.food.cart.search_params;
		} catch(e) {
			search_params = {};
		}
	} else {
		search_params = JSON.parse(decodeURIComponent(search_params));
	}
	
	function performSearch() {
		if (history.pushState) {
			var newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?search_params=' + encodeURIComponent(JSON.stringify(search_params));
			window.history.pushState({path:newurl},'',newurl);
		}

		Drupal.ajax({
			url: drupalSettings.food.restaurantPerformSearchUrl + '?search_params=' + encodeURIComponent(JSON.stringify(search_params))
		}).execute();
	}
	
	jQuery('input[data-name=delivery_mode]').click(function() {
		var isChecked = jQuery(this).is(':checked');
		
		jQuery('input[data-name=delivery_mode]').prop('checked', false);
		
		if(isChecked) {
			jQuery(this).prop('checked', true);
			search_params.delivery_mode = jQuery(this).val();
		} else {
			search_params.delivery_mode = null;
		}
				
		performSearch();
	});

	jQuery('input[data-name=restaurant_open_status]').click(function() {
		var isChecked = jQuery(this).is(':checked');

		jQuery('input[data-name=restaurant_open_status]').prop('checked', false);

		if(isChecked) {
			jQuery(this).prop('checked', true);
			search_params.restaurant_open_status = jQuery(this).val();
		} else {
			search_params.restaurant_open_status = null;
		}
				
		performSearch();
	});

	jQuery('input[data-name=cuisine]').click(function() {
		var cuisine_ids = [];
		jQuery('input[data-name=cuisine]:checked').each(function(index, item) {
			cuisine_ids.push(jQuery(item).val());
		});
		
		search_params.cuisine_ids = cuisine_ids;
		
		performSearch();
	});

	jQuery('input[data-name=service_area]').click(function() {
		var service_area_ids = [];
		jQuery('input[data-name=service_area]:checked').each(function(index, item) {
			service_area_ids.push(jQuery(item).val());
		});
		
		search_params.service_area_ids = service_area_ids;
		
		performSearch();
	});

	jQuery('input[data-name=dish]').click(function() {
		var dish_ids = [];
		jQuery('input[data-name=dish]:checked').each(function(index, item) {
			dish_ids.push(jQuery(item).val());
		});
		
		search_params.dish_ids = dish_ids;
		
		performSearch();
	});

	jQuery('input[data-name=distance]').click(function() {
		var isChecked = jQuery(this).is(':checked');
		
		jQuery('input[data-name=distance]').prop('checked', false);

		if(isChecked) {
			jQuery(this).prop('checked', true);
			search_params.distance = jQuery(this).val();
		} else {
			search_params.distance = null;
		}
				
		performSearch();
	});

	jQuery('.food-restaurant-filter-clear').click(function() {
		var isChecked = jQuery(this).is(':checked');
		
		jQuery('input[data-name=delivery_mode]').prop('checked', false);
		jQuery('input[data-name=restaurant_open_status]').prop('checked', false);
		jQuery('input[data-name=cuisine]').prop('checked', false);
		jQuery('input[data-name=service_area]').prop('checked', false);
		jQuery('input[data-name=dish]').prop('checked', false);
		jQuery('input[data-name=distance]').prop('checked', false);

		search_params.delivery_mode = null;
		search_params.restaurant_open_status = null;
		search_params.cuisine_ids = null;
		search_params.service_area_ids = null;
		search_params.dish_ids = null;
		search_params.distance = null;
		
		performSearch();
		
		return (false);
	});
	
	jQuery('.food-restaurant-filter-toggle').click(function(e) {
		jQuery('#filter-sidebar').toggle();
		return (false);
	});

	jQuery('input[data-name=delivery_mode][value=' + search_params.delivery_mode + ']').prop('checked', true);
	jQuery('input[data-name=restaurant_open_status][value=' + search_params.restaurant_open_status + ']').prop('checked', true);
	jQuery('input[data-name=distance][value=' + search_params.distance + ']').prop('checked', true);
	if(search_params.cuisine_ids) {
		for(var i = 0; i < search_params.cuisine_ids.length; i++) {
			jQuery('#chk-cuisine-' + search_params.cuisine_ids[i]).prop('checked', true);
		}
	}
	if(search_params.service_area_ids) {
		for(var i = 0; i < search_params.service_area_ids.length; i++) {
			jQuery('#chk-service_area-' + search_params.service_area_ids[i]).prop('checked', true);
		}
	}
	if(search_params.dish_ids) {
		for(var i = 0; i < search_params.dish_ids.length; i++) {
			jQuery('#chk-dish-' + search_params.dish_ids[i]).prop('checked', true);
		}
	}
	
	performSearch();
});
