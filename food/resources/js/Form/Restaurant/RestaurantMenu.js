
jQuery(".about_view").click(function() {
    jQuery('html, body').animate({
        scrollTop: jQuery("#about_view").offset().top
    }, 2000);
});

jQuery(".map_view").click(function() {
    jQuery('html, body').animate({
        scrollTop: jQuery("#map").offset().top
    }, 2000);
});

jQuery(".review_view").click(function() {
    jQuery('html, body').animate({
        scrollTop: jQuery("#review_view").offset().top
    }, 2000);
});

jQuery(window).scroll(function() {
   
    if (jQuery(this).scrollTop() > 50 ) {
        jQuery('.scrolltop:hidden').stop(true, true).fadeIn();
    } else {
        jQuery('.scrolltop').stop(true, true).fadeOut();
    }
});
jQuery(function(){jQuery(".scroll").click(function(){jQuery("html,body").animate({scrollTop:jQuery(".thetop").offset().top},"1000");return false})})

jQuery(document).ready(function() {

		jQuery( '#thumb-pro-slider' ).sliderPro({
	          width: 1100,
			height: 380,
			//orientation: 'vertical',
			loop: false,
			arrows:true,
			buttons: false,
			autoplay:false,
			thumbnailsPosition: 'right',
			thumbnailPointer: true,
			thumbnailWidth: 290,
			breakpoints: {
				1400: {
					thumbnailWidth:200,
					thumbnailHeight: 100
				},
				1100: {
					thumbnailsPosition: 'bottom',
					thumbnailWidth:150,
					thumbnailHeight: 100
				},
				500: {
					thumbnailsPosition: 'bottom',
					thumbnailWidth: 120,
					thumbnailHeight: 50
				}
			}
		});

	function handleCheckoutButton () {
		if(drupalSettings.food.cart.order_details.breakup.items_total_amount < drupalSettings.food.cart.restaurant.minimum_order_amount ||
				drupalSettings.food.cart.order_details.breakup.items_total_amount <= 0) {
			jQuery('.btn-checkout').hide();
		} else {
			jQuery('.btn-checkout').show();
		}
	}
	
	if(drupalSettings.food.restaurant.restaurant_id != drupalSettings.food.cart.restaurant.restaurant_id) {
		handleRestaurantMenuMismatch();
	}	

	jQuery('.btn-checkout').click(function(e) {
		if(drupalSettings.food.cart.delivery_mode != DeliveryMode.Delivery && drupalSettings.food.cart.delivery_mode != DeliveryMode.Pickup) {
			alert('Please select a delivery mode');
			e.stopImmediatePropagation();
			return (false);
		}
	});	
	//Need to make our handler the first for this event.
	jQuery._data(jQuery('.btn-checkout')[0], "events").click.reverse();
	
	jQuery(document).bind("ajaxComplete", function(e, xhr, settings){
		handleCheckoutButton();
	});

	jQuery('.restaurant-menu-item-main').click(function(e) {
		if(jQuery(e.target).attr('data-food-skip-add-item')) {
			return;
		}
		
		jQuery(this).find('a.food-add-cart-item-link').click();
	});
	
	jQuery(window).scroll(function(){
		var scrolled = jQuery(window).scrollTop();
		if(scrolled >= 150) {
			jQuery('.restaurant-menu-list-page-menu-header,#block-usercartblock').addClass('sticky-header');
		} 
		else {
			jQuery('.restaurant-menu-list-page-menu-header,#block-usercartblock').removeClass('sticky-header');
		}
	
		var anchor_top = jQuery(".restaurant-profile-map").offset().top;
		if(scrolled >= anchor_top) {
			jQuery('.check-my-cart-btn').hide();
		}
		else {
			jQuery('a.check-my-cart-btn').show();
		}
	});		
	


	
	jQuery('body').attr("data-spy","scroll");
	handleCheckoutButton();
});

function handleRestaurantMenuMismatch () {
	if(confirm('You have pending cart from another restaurant. Click OK to goto the other restaurant, or cancel to continue with current restaurant.')) {
		document.location.href = drupalSettings.food.cart.restaurant.restaurant_url;
	} else {
		Drupal.ajax({
			url: drupalSettings.food.restaurant.switchRestaurantUrl
		}).execute();
	}
}
