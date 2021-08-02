jQuery(document).ready(function() {

	function refreshReport() {
        var order_statuses = [];	
		jQuery('.order_status:checked').each(function(){
			order_statuses.push(jQuery(this).val());
		});
        var restaurant_id = jQuery('#edit-restaurant-id').val();
        if (restaurant_id) {
			var params = {
				restaurant_id: restaurant_id,
				order_id: jQuery('#edit-order-id').val(),
	            user_name: jQuery('#edit-user-name').val(),
	            user_phone: jQuery('#edit-user-phone').val(),
	            user_address: jQuery('#edit-user-address').val(),
	            start_date: jQuery('#edit-start-date').val(),
	            end_date: jQuery('#edit-end-date').val(),
	            order_amount: jQuery('#edit-order-amount').val(),
	            delivery_mode: jQuery('#edit-delivery-mode').val(),
	            payment_mode: jQuery('#edit-payment-mode').val(),
	            order_statuses: order_statuses,            
			};
	        		
			Drupal.ajax({
				url: drupalSettings.food.currentBalanceCallbackUrl + '?' + jQuery.param(params),
			}).execute();
        }
	}
	
	jQuery('#edit-restaurant-id').change(function() {
        refreshReport();
		return (false);
	});
	
	jQuery('.order_status').change(function() {
		refreshReport();
	});
	
	refreshReport();
});


Drupal.behaviors.currentbalance = {
  attach: function (context, settings) {
	jQuery('.food_partner_current_balance_grid_container ul.pagination li a').once().click(function(event) {
		event.preventDefault();
        Drupal.ajax({
			url: drupalSettings.food.currentBalanceCallbackUrl + jQuery(this).attr('href'),
		}).execute();
	});
	
  }
};