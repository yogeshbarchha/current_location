Drupal.behaviors.pickupdeliveryform = {
  attach: function (context, settings) {
jQuery("#food-pickup-delivery-form button[type='submit']").on('click', function(e) {
	var checkedItem = jQuery("#food-pickup-delivery-form [name^='order_types']:checked").length;
	    if (checkedItem == 0) {
	        alert('Please select atleast one order type');
	        return (false);
	    }
	});

	jQuery("#food-menu-item-status-form select[name='restaurant']").once().on('change', function(e) {
		jQuery("#food-menu-item-status-form input[name='restaurant_menu']").val('');
		var count = jQuery("#food-menu-item-status-form input[name='restaurant_menu']").attr('count');
		var restaurant_id = jQuery(this).val();
		jQuery("#food-menu-item-status-form input[name='restaurant_menu']").attr('data-autocomplete-path','/menu-item-autocomplete/'+restaurant_id+'/'+ count);
	});
}
};