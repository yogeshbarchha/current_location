jQuery(document).ready(function() {
	function refreshReport() {
		var params = {
			restaurant_id: jQuery('#edit-restaurant-id').val(),
		};
		
		Drupal.ajax({
			url: drupalSettings.food.chargebackCallbackUrl + '?' + jQuery.param(params),
		}).execute();
	}
	
	jQuery('#edit-submit').click(function() {
		refreshReport();
		return (false);
	});
	
	refreshReport();
});
