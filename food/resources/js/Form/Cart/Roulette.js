(function ($, Drupal, drupalSettings) {
	if(drupalSettings.food.cart.platform_discount_pct > 0) {
		handleFinalDiscount();
	} else {
		jQuery('#step-roulette').show();

		var items = {};
		for(var i = 0; i < drupalSettings.food.rouletteDiscounts.length; i++) {
			var rouletteDiscount = drupalSettings.food.rouletteDiscounts[i];
			
			items[rouletteDiscount.discount_pct] = rouletteDiscount.text;
		}

		$('#roulettecanvas').rouletteWheel({
			items : items,
			selected : function(key, value){
				Drupal.ajax({
					url: drupalSettings.food.setPlatformDiscountUrl.replace('10000', key),
					success: function() {
						Drupal.Ajax.prototype.success.apply(this, arguments);
						handleFinalDiscount();
					}
				}).execute();
			},
			spinText : 'Play',
		});
	}

})(jQuery, Drupal, drupalSettings);

function handleFinalDiscount() {
	var allDealsHidden = true;
	jQuery('.display-roulette-discount li').each(function(index, item) {
		var discount_pct = jQuery(this).attr('data-getdiscount');
		if(drupalSettings.food.cart.platform_discount_pct >= discount_pct || drupalSettings.food.cart.restaurant_discount_pct >= discount_pct) {
			jQuery(this).hide();
		} else {
			allDealsHidden = false;
		}
	});

	var message;
	if(drupalSettings.food.cart.platform_discount_pct > drupalSettings.food.cart.restaurant_discount_pct) {
		message = 'Dear ' + drupalSettings.food.username + ', a game discount of ' + drupalSettings.food.cart.platform_discount_pct + '% has been applied to your cart successfully.';
		if(allDealsHidden == false) {
			message += 'To get higher discount, please add more items to your cart or checkout to continue.';
		}
	} else if(drupalSettings.food.cart.restaurant_discount_pct > 0) {
		message = 'Dear ' + drupalSettings.food.username + ', a deal discount of ' + drupalSettings.food.cart.restaurant_discount_pct + '% has been applied to your cart successfully.';
		if(allDealsHidden == false) {
			message += 'To get higher discount, please add more items to your cart or checkout to continue.';
		}
	} else {
		message = 'Dear ' + drupalSettings.food.username + ', your available Discounts are showing above, to get your highest discount add more items to your cart.';
	}
	
	jQuery('#step-roulette').hide();
	jQuery('#step-deals').show();
	jQuery('#step-deals-suffix-message').html(message);	
}
