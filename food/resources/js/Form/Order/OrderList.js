jQuery(document).ready(function() {
      if(localStorage.getItem('popState') != 'shown'){
	if(drupalSettings.food.user.user_last_order_id){
		handlePreviousOrderCart();
	}
        localStorage.setItem('popState','shown');
       }
});


function handlePreviousOrderCart() {
	if(confirm('Want to repeat the last order.Click OK to repeat with the last order, or cancel to continue.')) {
		document.location.href = drupalSettings.food.user.user_previous_order_cart_link;
	} else {
		location.reload();
		// Drupal.ajax({
		// 	url: drupalSettings.food.restaurant.switchRestaurantUrl
		// }).execute();
	}
}
