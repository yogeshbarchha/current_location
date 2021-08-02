jQuery(document).ready(function() {

	function refreshReport() {
        var params = {
			order_id: jQuery('#edit-order-id').val(),
            user_name: jQuery('#edit-user-name').val(),
            user_address: jQuery('#edit-user-address').val(),
            created_time: jQuery('#edit-created-time').val(),
            order_amount: jQuery('#edit-order-amount').val(),
            delivery_mode: jQuery('#edit-delivery-mode').val(),
            user_phone: jQuery('#edit-user-phone').val(),
            user_email: jQuery('#edit-user-email').val(),
            restaurant_id: jQuery('#edit-restaurant-id').val(),
            
            
            
        };
        		
		Drupal.ajax({
			url: drupalSettings.food.userListCallbackUrl + '?' + jQuery.param(params),
		}).execute();
	}
	
	jQuery('#edit-submit').click(function() {
		refreshReport();
		return (false);
	});
	
	refreshReport();
});


Drupal.behaviors.userlist = {
  attach: function (context, settings) {

	jQuery('.food_platform_user_grid_container ul.pagination li a').once().click(function(event) {
		event.preventDefault();
        Drupal.ajax({
			url: drupalSettings.food.userListCallbackUrl + jQuery(this).attr('href'),
		}).execute();
	});
	
  }
};