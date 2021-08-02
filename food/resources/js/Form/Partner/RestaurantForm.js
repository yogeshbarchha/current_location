function trackCircleChange(widget) {
    var position = widget.get('position');
    jQuery("input[name='latitude_val']").val(position.lat());
    jQuery("input[name='longitude_val']").val(position.lng());
    jQuery("input[name='delivery_radius_val']").val(widget.get('distance'));
}

function showCircleMap() {
    jQuery('#edit-delivery-area-type-1').parent().parent().css({'background-color': '#E24425', 'color': 'white'});
    jQuery('#edit-delivery-area-type-2').parent().parent().css({'background-color': 'white', 'color': '#333'});

    jQuery('.form-item-delivery-radius').css('display', 'inherit');

    var mapDiv = document.getElementById('google_map');
    var map = new google.maps.Map(mapDiv, {
        center: new google.maps.LatLng(jQuery("input[name='latitude_val']").val(), jQuery("input[name='longitude_val']").val()),
        zoom: 13,
        mapTypeId: google.maps.MapTypeId.ROADMAP
    });

    var distanceWidget = new DistanceWidget(map, jQuery('#edit-delivery-radius').val());
    google.maps.event.addListener(distanceWidget, 'distance_changed', function() {
        var distance = Math.round(distanceWidget.get('distance') * 1000) / 1000;
        //var distance_value_miles = distance / 1.609344;
        //jQuery('#edit-delivery-radius').val(Math.round(distance_value_miles * 100) / 100);
        jQuery('#edit-delivery-radius').val(distance);
        trackCircleChange(distanceWidget);
    });

    google.maps.event.addListener(distanceWidget, 'position_changed', function() {
        var distance = distanceWidget.get('distance');
        trackCircleChange(distanceWidget);
    });

    trackCircleChange(distanceWidget);
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

    jQuery("input[name='delivery_polygon_val']").val(JSON.stringify(points));
}

function showPolygonMap() {
    jQuery('#edit-delivery-area-type-1').parent().parent().css({'background-color': 'white', 'color': '#333'});
    jQuery('#edit-delivery-area-type-2').parent().parent().css({'background-color': '#E24425', 'color': 'white'});

    jQuery('.form-item-delivery-radius').css('display', 'none');

    var origin = new google.maps.LatLng(jQuery("input[name='latitude_val']").val(), jQuery("input[name='longitude_val']").val());
    var map = new google.maps.Map(document.getElementById('google_map'), {
        zoom: 13,
        center: origin,
        mapTypeId: google.maps.MapTypeId.ROADMAP,
        disableDefaultUI: true,
        zoomControl: true,
    });

    var delivery_polygon = jQuery("input[name='delivery_polygon_val']").val();
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
        editable: true,
        strokeColor: '#4444FF',
        strokeOpacity: 1,
        strokeWeight: 2,
        fillColor: '#5555FF',
    });
    polyOptions.setMap(map);

    var marker = new google.maps.Marker({
        position: origin,
        map: map,
        draggable: true,
        title: "Drag me!"
    });
    trackPolygonChange(polyOptions);

    //This does not fire reliably as lines are stretched, so need to use setInterval.
    /*google.maps.event.addListener(polyOptions.getPath(), "set_at", function() {
     trackPolygonChange(polyOptions)
     });*/
    setInterval(function() {
        trackPolygonChange(polyOptions)
    }, 200);
}

function refreshMap() {
    var areaType = jQuery("input[name='delivery_area_type']:checked").val();

    if (areaType == DeliveryAreaType.Polygon) {
        showPolygonMap();
    } else {
        showCircleMap();
    }
}

jQuery(document).ready(function() {
    jQuery('.gmnoprint').css('z-index', 10);
    jQuery(".address_line1")
	.geocomplete({
		details: ".details",
		location: "",
		detailsScope: '.location',
		types: ["geocode", "establishment"],
	})
	.on("geocode:result", function (event, result) {
		var address = result.formatted_address;
		var obj = parseAddress(address);
		if (obj.address != "" && obj.postalCode != "") {
			var lats = result.geometry.location.lat();
			var longs = result.geometry.location.lng();
			 
            jQuery('.latitude_val').val(lats);
            jQuery('.longitude_val').val(longs);
			var address = jQuery(".address_line1").val();

			if (typeof lats != 'undefined' && lats != '') {
				jQuery('.address_line1').val(obj.address); 
				jQuery('.state').val(obj.province);
				jQuery('.country').val(obj.country);
				jQuery('.city').val(obj.city);
				jQuery('.postal_code').val(obj.postalCode);

				refreshMap();
			}
		}
	});
	
	refreshMap();
    
    jQuery("#edit-delivery-radius").on("keypress", function(e) {
        if (e.keyCode == 13) {
            showCircleMap();
        }
    });

    jQuery("#edit-delivery-radius").on("change", function(e) {
        showCircleMap();
    });

    jQuery('#edit-delivery-area-type-1').click(function() {
        showCircleMap();
    });

    jQuery('#edit-delivery-area-type-2').click(function() {
        showPolygonMap();
    });


    //select weekday for restaurant timing 
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
            jQuery('.open_start_time:eq(' + index + ')').prop('disabled', true);
            jQuery('.open_end_time:eq(' + index + ')').prop('disabled', true);
            jQuery('.open_time_apply_btn:eq(' + index + ')').hide();
            jQuery('.del_start_time:eq(' + index + ')').prop('disabled', true);
            jQuery('.del_end_time:eq(' + index + ')').prop('disabled', true);
            jQuery('.del_time_apply_btn:eq(' + index + ')').hide();
        }
    });

    jQuery(".deal_row_toggle").on('change', function() {
        var index = jQuery('.deal_row_toggle').index(this);
        if (jQuery(this).is(":checked")) {
            jQuery('.deal_row_amount:eq(' + index + ')').prop('disabled', false);
            jQuery('.deal_row_pct:eq(' + index + ')').prop('disabled', false);
        } else {
            jQuery('.deal_row_amount:eq(' + index + ')').prop('disabled', true);
            jQuery('.deal_row_pct:eq(' + index + ')').prop('disabled', true);
        }
    });

    jQuery(".platform_deal_row_toggle").on('change', function() {
        var index = jQuery('.platform_deal_row_toggle').index(this);
        if (jQuery(this).is(":checked")) {
            jQuery('.platform_deal_row_pct:eq(' + index + ')').prop('disabled', false);
        } else {
            jQuery('.platform_deal_row_pct:eq(' + index + ')').prop('disabled', true);
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

    jQuery("#edit-submit").on('click', function(e) {
        var checkedItem = jQuery('[name^="order_types"]:checked').length;
        if (checkedItem == 0) {
            alert('Please select atleast one order type');
            return (false);
        }

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

    jQuery(".food-restaurant-form #edit-user-reference").on('change', function() {
      var val = jQuery(this).val();
      var uid = val.match(/\((.*)\)/);
      jQuery.ajax({
        method: "POST",
        url: "/user_reference/info",
        data: { uid: uid[1], type: 'user_reference' },
        success: function (data) {
        if(data.name === ''){
          jQuery('.food-restaurant-form input[name=name]').val(data.name).prop("readonly", false);
        }else{
          jQuery('.food-restaurant-form input[name=name]').val(data.name).prop("readonly", true);            
        }
        if(data.email === ''){
          jQuery('.food-restaurant-form input[name=email]').val(data.email).prop("readonly", false);
        }else{
          jQuery('.food-restaurant-form input[name=email]').val(data.email).prop("readonly", true);
        }
        if(data.phone === ''){
          jQuery('.food-restaurant-form input[name=phone_number]').val(data.phone).prop("readonly", false);
        }else{
          jQuery('.food-restaurant-form input[name=phone_number]').val(data.phone).prop("readonly", true);
        }
       }

      });
    });
});

