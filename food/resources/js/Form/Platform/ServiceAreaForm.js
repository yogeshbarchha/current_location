function trackCircleChange(widget) {
    var position = widget.get('position');
    jQuery("input[name='latitude_val']").val(position.lat());
    jQuery("input[name='longitude_val']").val(position.lng());
    jQuery("input[name='delivery_radius_val']").val(widget.get('distance'));
}

function showCircleMap() {
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

function refreshMap() {
    showCircleMap();
}

jQuery(document).ready(function() {
    jQuery(".address")
            .geocomplete({
                details: ".details",
                location: "",
                detailsScope: '.location',
                types: ["geocode", "establishment"],
            })
            .on("geocode:result", function(event, result) {
                var address = result.formatted_address;
                var obj = parseAddress(address);

				var lats = result.geometry.location.lat();
				var longs = result.geometry.location.lng();

				jQuery('.latitude_val').val(lats);
				jQuery('.longitude_val').val(longs);
				var address = jQuery(".address").val();

				if (typeof lats != 'undefined' && lats != '') {
					jQuery('.address').val(address);
					jQuery('.state').val(obj.province);
					jQuery('.country').val(obj.country);
					jQuery('.city').val(obj.city);
					jQuery('.postal_code').val(obj.postalCode);

					refreshMap();
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
});

