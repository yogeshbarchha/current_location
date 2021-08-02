jQuery(document).ready(function() {
	jQuery(document).ajaxSend(function(event, XMLHttpRequest, ajaxOptions) {
		if (typeof ajaxOptions.url != "undefined" && ajaxOptions.url.indexOf("restaurant/performsearch") > 0) {
			jQuery('.loading-indicator').show();
		}
	});
	jQuery(document).ajaxComplete(function() {
		jQuery('.loading-indicator').hide();
	});
	
	jQuery('#restaurant-search-result').on('click', '.restaurant-list-cards', function(e) {
		if(jQuery(e.target).attr('data-food-skip-nav')) {
			return;
		}

		document.location.href = jQuery(this).attr('data-url');
	});
});
