(function ($, Drupal, drupalSettings) {
	function getMenuItemOptionPrice(option, base_price) {
		var price = parseFloat(option.price);
		if(option.is_price_pct) {
			price = base_price * price / 100;
		}
		
		return (price);
	}
	
	function updatePrice () {
		var currentItem = drupalSettings.food.restaurant.menu.currentItem;
		var base_price = parseFloat(currentItem.price);
		var unit_price = parseFloat(currentItem.price);
		
		if(currentItem && currentItem.variations && currentItem.variations.sizes) {
			for(var i=0; i < currentItem.variations.sizes.length; i++) {
				var currentSize = currentItem.variations.sizes[i];
				if(jQuery('.cart_item_size[value=' + i + ']').is(':checked')) {
					base_price = currentSize.price;
					unit_price = currentSize.price;
				}
			}
		}
		
		if(currentItem && currentItem.variations && currentItem.variations.categories) {
			for(var i=0; i < currentItem.variations.categories.length; i++) {
				var currentCategory = currentItem.variations.categories[i];
				jQuery('input.cart_item_category' + i + ', select.cart_item_category' + i).each(function() {
					var val = jQuery(this).val();
					if(this.type == 'checkbox' || this.type == 'radio') {
						if(jQuery(this).is(':checked')) {
							var option = currentCategory.options[parseInt(val)];
							if(option) {
								unit_price += getMenuItemOptionPrice(option, base_price);
							}
						}
					} else {
						var option = currentCategory.options[parseInt(val)];
						if(option) {
							unit_price += getMenuItemOptionPrice(option, base_price);
						}
					}
				});
			}
		}
		
		var total_price = roundToPlaces(parseInt(jQuery('.cart_item_quantity').val()) * unit_price, 2);
		jQuery('#drupal-modal h4.modal-title').html(currentItem.name + '<span class="restaurant-menu-item-price">' + drupalSettings.food.currencySymbol + total_price + '<span>');
	}
	
	Drupal.behaviors.addCartItemForm = {
		attach: function(context, settings) {
			jQuery('.cart_item_quantity_up').once().click(function() {
				jQuery('.cart_item_quantity').val((parseInt(jQuery('.cart_item_quantity').val()) || 0)+ 1);
				updatePrice();
				return(false);
			});
			jQuery('.cart_item_quantity_down').once().click(function() {
				var val = parseInt(jQuery('.cart_item_quantity').val());
				if(val > 1){
					jQuery('.cart_item_quantity').val(val - 1);
				}
				updatePrice();
				return(false);
			});
			
			var priceUpdateInterval = setInterval(function() {
				if(jQuery('#drupal-modal .food-add-cart-item-form').length > 0 && jQuery('#drupal-modal h4.modal-title').length > 0) {
					clearInterval(priceUpdateInterval);
					updatePrice();
					//Once update again after 200 ms because it dialog header has "Loading.." when it first loads that gets replaced subsequently.
					setTimeout(updatePrice, 200);						
				}
			}, 200);
		},
  };

  jQuery('body').on('change', '.food-add-cart-item-form input', updatePrice);
  jQuery('body').on('change', '.food-add-cart-item-form select', updatePrice);
  
})(jQuery, Drupal, drupalSettings);
