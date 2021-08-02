jQuery(document).ready(function () {  
  var top = jQuery('#block-food-theme2-tools').offset().top;
  jQuery(window).scroll(function (event) {
	var y = jQuery(this).scrollTop();
		if (y >= top) {
		  jQuery('#block-food-theme2-tools').addClass('fixedsticky');
		} else {
		  jQuery('#block-food-theme2-tools').removeClass('fixedsticky');
               }
		
  	});
}); 
jQuery(document).ready(function() {

	function refreshReport() {
        var order_statuses = [];	
		jQuery('.order_status:checked').each(function(){
			  order_statuses.push(jQuery(this).val());
		});
        
		var params = {
			restaurant_id: jQuery('#edit-restaurant-id').val(),
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
			url: drupalSettings.food.liveOrdersCallbackUrl + '?' + jQuery.param(params),
		}).execute();
	}
	
	jQuery('#edit-submit').click(function() {
        refreshReport();
		return (false);
	});
	
	jQuery('.order_status').change(function() {
		refreshReport();
	});
	
	refreshReport();
});


Drupal.behaviors.liveorders = {
  attach: function (context, settings) {

	jQuery('.food_partner_order_grid_container ul.pagination li a').once().click(function(event) {
		event.preventDefault();
        Drupal.ajax({
			url: drupalSettings.food.liveOrdersCallbackUrl + jQuery(this).attr('href'),
		}).execute();
	});
	
  }
};
