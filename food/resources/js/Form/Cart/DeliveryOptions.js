(function ($, Drupal, drupalSettings) {
	var directionsService,
		directionsDisplay,
		geocoder,
		restaurantmarker,
		rendererOptions,
		mapCanvas,
		stepDisplay;
	var markerArray = [];
	var draw_circle = null;
	
	function initializeMap () {		
		// Instantiate a directions service.
		directionsService = new google.maps.DirectionsService();
		directionsDisplay = new google.maps.DirectionsRenderer();
		geocoder = new google.maps.Geocoder;
		// Instantiate an info window to hold step text.
		stepDisplay = new google.maps.InfoWindow();

		// Create a map and center it on restaurant.  
		var restaurantLoc = new google.maps.LatLng(drupalSettings.food.cart.restaurant.latitude, drupalSettings.food.cart.restaurant.longitude);
		var myOptions = {
			zoom: 13,
			mapTypeId: google.maps.MapTypeId.ROADMAP,
			center: restaurantLoc,
			zoomControl: true,
			mapTypeControl:true,
		}
		mapCanvas = new google.maps.Map(document.getElementById("map"), myOptions);

		restaurantmarker = new google.maps.Marker({
			position: new google.maps.LatLng(drupalSettings.food.cart.restaurant.latitude, drupalSettings.food.cart.restaurant.longitude),
			map: mapCanvas,
			icon: "https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=" + 'R' + "|FF0000|000000",
		});

		stepDisplay.setContent(drupalSettings.food.cart.restaurant.name);
		stepDisplay.open(mapCanvas, restaurantmarker);
         
		// Create a renderer for directions and bind it to the map.
		rendererOptions = {
			map: mapCanvas,
			suppressMarkers: true,
			preserveViewport: true
		}
		
		if(drupalSettings.food.cart.delivery_mode == DeliveryMode.Pickup) {
			showRestaurant();
		} else {
			if(jQuery('#edit-user-address').val()){
				calculateRoute(jQuery('#edit-user-address').val());
			}else{
				calculateRoute(drupalSettings.food.cart.search_params.user_address);
			}
		}

		if (drupalSettings.food.cart.restaurant.delivery_area_type == DeliveryAreaType.Polygon) {
            DrawPolygon();
		}else{
			DrawCircle();
		}
		
	}
	
	function showRestaurant () {
		if(!!navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(position) {       
	            var geolocate = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
	            var latlng = {lat: parseFloat(position.coords.latitude), lng: parseFloat(position.coords.longitude)};
	            geocoder.geocode({'location': latlng}, function(results, status) {
	                if (status === 'OK') {
						calculateRoute(results[0].formatted_address);
	                }else{
	                	alert('Location not found');
	                }
	            });
        	});
		}
	}

	function calculateRoute(destination) {
		// First, remove any existing markers from the map.
		for (i = 0; i < markerArray.length; i++) {
			markerArray[i].setMap(null);
		}

		// Now, clear the array itself.
		markerArray = [];
		
		var origin = drupalSettings.food.cart.restaurant.latitude + ','+ drupalSettings.food.cart.restaurant.longitude;

		var request = {
			origin: origin,
			destination: destination,
			travelMode: google.maps.DirectionsTravelMode.DRIVING
		};
		if (origin && destination) {
			directionsService.route(request, function (response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					var warnings = document.getElementById("warnings_panel");
					if(warnings) {
						warnings.innerHTML = "<b>" + response.routes[0].warnings + "</b>";
					}

					//var directionsDisplay = new google.maps.DirectionsRenderer(rendererOptions)
					directionsDisplay.setOptions(rendererOptions);
					directionsDisplay.setDirections(response);
					jQuery("#route-detail").html('<p>Distance: '+response.routes[0].legs[0].distance.text+'</p>');
					showSteps(response);
				}
			});
		}
	}

	function showSteps(directionResult) {
		restaurantmarker.setMap(null);
		// For each step, place a marker, and add the text to the marker's
		// info window. Also attach the marker to an array so we
		// can keep track of it and remove it when calculating new
		// routes.
		var myRoute = directionResult.routes[0].legs[0];

		var marker = new google.maps.Marker({
				position: myRoute.start_location,
				map: mapCanvas,
				icon: "https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=" + 'R' + "|FF0000|000000"
		});
		SourceDisplay = new google.maps.InfoWindow();
		SourceDisplay.setContent(myRoute.start_address);
		SourceDisplay.open(mapCanvas, marker);
		attachInstructionText(marker, myRoute.start_address);
		markerArray.push(marker);

		var marker = new google.maps.Marker({
				position: myRoute.end_location,
				map: mapCanvas,
				icon: "https://cdn2.iconfinder.com/data/icons/pins/1120/58-pin-32.png"
		});
		DestinationDisplay = new google.maps.InfoWindow();
		DestinationDisplay.setContent(myRoute.end_address);
		DestinationDisplay.open(mapCanvas, marker);
		attachInstructionText(marker, myRoute.end_address);
		markerArray.push(marker);

		// for (var i = 0; i < myRoute.steps.length; i++) {
		// 	var icon = "https://chart.googleapis.com/chart?chst=d_map_pin_letter&chld=" + i + "|FF0000|000000";
		// 	if (i == 0) {
		// 		icon = "https://chart.googleapis.com/chart?chst=d_map_xpin_icon&chld=pin_star|car-dealer|00FFFF|FF0000";
		// 	}
		// 	var marker = new google.maps.Marker({
		// 		position: myRoute.steps[i].start_point,
		// 		map: mapCanvas,
		// 		icon: icon
		// 	});
		// 	attachInstructionText(marker, myRoute.steps[i].instructions);
		// 	markerArray.push(marker);
		// }
		// var marker = new google.maps.Marker({
		// 	position: myRoute.steps[i - 1].end_point,
		// 	map: mapCanvas,
		// 	icon: "https://cdn2.iconfinder.com/data/icons/pins/1120/58-pin-32.png"
		// });
		// markerArray.push(marker);

		// google.maps.event.trigger(markerArray[0], "click");
	}

	function attachInstructionText(marker, text) {
		google.maps.event.addListener(marker, 'click', function () {
			// Open an info window when the marker is clicked on,
			// containing the text of the step.
			stepDisplay.setContent(text);
			stepDisplay.open(mapCanvas, marker);
		});
	}

	function DrawCircle() {

		var rad = k2me(drupalSettings.food.cart.restaurant.delivery_radius); // convert to meters if in miles
		if (draw_circle != null) {
		draw_circle.setMap(null);
		}
		draw_circle = new google.maps.Circle({
		center: new google.maps.LatLng(drupalSettings.food.cart.restaurant.latitude, drupalSettings.food.cart.restaurant.longitude),
		radius: rad,
		strokeColor: "#4444FF",
		strokeOpacity: 0.8,
		strokeWeight: 2,
		fillColor: "#5555FF",
		fillOpacity: 0.35,
		map: mapCanvas
		});
	}

	function DrawPolygon() {
	    var origin = new google.maps.LatLng(drupalSettings.food.cart.restaurant.latitude, drupalSettings.food.cart.restaurant.longitude);
	   
	    
	    var delivery_polygon = drupalSettings.food.cart.restaurant.delivery_polygon;
	    if (delivery_polygon) {
	        delivery_polygon = JSON.parse(delivery_polygon);
	    } else {
	        delivery_polygon = [];
	    }

	    var polygonCoords = [];
	    if (delivery_polygon.length > 0) {
	        for (i = 0; i < delivery_polygon.length; i++) {
	            var lat = parseFloat(delivery_polygon[i]['latitude']);
	            var lng = parseFloat(delivery_polygon[i]['longitude']);

	            polygonCoords.push({lat: lat, lng: lng});
	        }

	    } else {
	        var point1 = google.maps.geometry.spherical.computeOffset(origin, 1609, 45);
	        var point2 = google.maps.geometry.spherical.computeOffset(origin, 1609, 90);
	        var point3 = google.maps.geometry.spherical.computeOffset(origin, 1609, 135);
	        var point4 = google.maps.geometry.spherical.computeOffset(origin, 1609, 180);
	        var point5 = google.maps.geometry.spherical.computeOffset(origin, 1609, 225);
	        var point6 = google.maps.geometry.spherical.computeOffset(origin, 1609, 270);
	        var point7 = google.maps.geometry.spherical.computeOffset(origin, 1609, 315);
	        var point8 = google.maps.geometry.spherical.computeOffset(origin, 1609, 360);

	        var polygonCoords = [
	            {lat: point1.lat(), lng: point1.lng()},
	            {lat: point2.lat(), lng: point2.lng()},
	            {lat: point3.lat(), lng: point3.lng()},
	            {lat: point4.lat(), lng: point4.lng()},
	            {lat: point5.lat(), lng: point5.lng()},
	            {lat: point6.lat(), lng: point6.lng()},
	            {lat: point7.lat(), lng: point7.lng()},
	            {lat: point8.lat(), lng: point8.lng()}
	        ];
	    }

	    var polyOptions = new google.maps.Polygon({
	        paths: polygonCoords,
	        draggable: false, // turn off if it gets annoying
	        editable: false,
	        strokeColor: '#4444FF',
	        strokeOpacity: 1,
	        strokeWeight: 2,
	        fillColor: '#5555FF',
	    });
	    polyOptions.setMap(mapCanvas);

	    // var marker = new google.maps.Marker({
	    //     position: origin,
	    //     map: mapCanvas,
	    //     draggable: true,
	    //     title: "Drag me!"
	    // });
	    trackPolygonChange(polyOptions);

	    //This does not fire reliably as lines are stretched, so need to use setInterval.
	    /*google.maps.event.addListener(polyOptions.getPath(), "set_at", function() {
	     trackPolygonChange(polyOptions)
	     });*/
	    setInterval(function() {
	        trackPolygonChange(polyOptions)
	    }, 200);
	}

	function trackPolygonChange(polyOptions) {
		var len = polyOptions.getPath().getLength();
		var tot = len - 1;

		var points = [];
		for (var i = 0; i < len; i++) {
			var point = polyOptions.getPath().getAt(i);
			points.push({
				latitude: point.lat(),
				longitude: point.lng(),
			});
		}
	}


	function k2me(kilometers) {
    	var km = parseFloat(kilometers);
    	var mi = "";
    	if (!isNaN(km)) mi = km * 1000;
         return mi;
	}
	
	jQuery('.user-address').click(function() {
		var address_id = jQuery(this).attr('data-address-id');
		for(var i=0; i < drupalSettings.food.address_count; i++) {
			var user_address = drupalSettings.food.user_addresses[i];
			if(user_address.address_id == address_id) {
				jQuery('#edit-user-name').val(user_address.contact_name);
				jQuery('#edit-user-phone').val(user_address.phone_number);
				var concatenatedAddress = concatenateAddressParts(user_address);
				jQuery('#edit-user-address').val(user_address.address_line1);
				
				jQuery('input[name=user_address_id]').val(address_id);
				jQuery('input[name=user_address_latitude]').val(user_address.latitude);
				jQuery('input[name=user_address_longitude]').val(user_address.longitude);

                                 jQuery('input[name=user_apartment_number]').val(user_address.address_line2);
				
				jQuery("input[name=type][value=" + user_address.type + "]").prop('checked', true);
				calculateRoute(concatenatedAddress);

				
			}
		}
	});

	var geocompleteConfig = getDeliveryGeocompleteOptions();
        jQuery("#edit-user-address")
	.geocomplete(geocompleteConfig)
	.on("geocode:result", function (event, result) {
		jQuery('#edit-user-address').val(result.formatted_address);
		jQuery('input[name=user_address_latitude]').val(result.geometry.location.lat());
		jQuery('input[name=user_address_longitude]').val(result.geometry.location.lng());
		calculateRoute(result.formatted_address);
	});

	jQuery('#food-user-cart-delivery-options-form')
        .formValidation({
            framework: 'bootstrap',
            icon: {
                valid: 'glyphicon glyphicon-ok',
                invalid: 'glyphicon glyphicon-remove',
                validating: 'glyphicon glyphicon-refresh'
            },
            fields: {
                user_address: {
                    trigger: 'blur',
                    validators: {
                        notEmpty: {
                            message: 'The address is required'
                        },
                        callback: {
                            message: 'The address is not found',
                            callback: function(value, validator, $field) {
                                var lat = jQuery('#food-user-cart-delivery-options-form').find('[name="user_address_latitude"]').val(),
                                    lng = jQuery('#food-user-cart-delivery-options-form').find('[name="user_address_longitude"]').val();
                                return $.isNumeric(lat) && $.isNumeric(lng)
                                        && (-90 <= lat) && (lat <= 90)
                                        && (-180 <= lng) && (lng <= 180);
                            }
                        }
                    }
                }
            }
        })
        .find('[name="user_address"]')
            .on('input keyup', function(e) {
                /* Reset lat, lng */
                jQuery('#food-user-cart-delivery-options-form')
                    .formValidation('updateStatus', 'user_address', 'NOT_VALIDATED')
                    .find('[name="user_address_latitude"], [name="user_address_longitude"]').val('').end();
            })
            .on('geocode:result', function(e, result) {
                jQuery('#food-user-cart-delivery-options-form').formValidation('revalidateField', 'user_address');
            })
            .on('geocode:error', function(e, result) {
                jQuery('#food-user-cart-delivery-options-form').formValidation('revalidateField', 'user_address');
            })
            .end();

	jQuery('#get-current-user-address').click(function() {
		if(!!navigator.geolocation) {
			navigator.geolocation.getCurrentPosition(function(position) {       
	            var geolocate = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);
	            var latlng = {lat: parseFloat(position.coords.latitude), lng: parseFloat(position.coords.longitude)};
	            geocoder.geocode({'location': latlng}, function(results, status) {
	                if (status === 'OK') {
	                	jQuery('#edit-user-address').val(results[0].formatted_address);
	                	jQuery('input[name=user_address_latitude]').val(position.coords.latitude);
						jQuery('input[name=user_address_longitude]').val(position.coords.longitude);
						calculateRoute(results[0].formatted_address);
	                }else{
	                	alert('Location not found');
	                }
	            });
        	});
		}
	});
	
	jQuery('#edit-modify-cart').click(function() {
		document.location.href = drupalSettings.food.restaurantMenuUrl;
		return(false);
	});

	jQuery('#add-user-address').click(function(){
		jQuery(".food-user-cart-delivery-options-form .more-address-wrapper").toggle();
		if(jQuery(".food-user-cart-delivery-options-form .more-address-wrapper").is(':visible')){
			jQuery(".food-user-cart-delivery-options-form .more-address-wrapper input[name='address_save']").val(1);
		}else{
			jQuery(".food-user-cart-delivery-options-form .more-address-wrapper input[name='address_save']").val(0);
		}
	});

	initializeMap();
	jQuery(".food-user-cart-delivery-options-form .more-address-wrapper").hide();

	jQuery('#map .gmnoprint').css('z-index', '1000001');
})(jQuery, Drupal, drupalSettings);
