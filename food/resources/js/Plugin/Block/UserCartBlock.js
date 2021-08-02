jQuery(document).ready(function() {
	jQuery(window).scroll(function(){
		var scrolled = jQuery(window).scrollTop();
		var anchor_top = jQuery(".footer").offset().top;

		anchor_top = anchor_top - 600;
		// var scrolled = $(window).scrollBottom();
		if(scrolled >= 260 && scrolled <= anchor_top) {
			jQuery('.food-cart-column').addClass('sticky-header');
		}
				
		else {
			jQuery('.food-cart-column').removeClass('sticky-header');
		}
		
		
		
	});
});
