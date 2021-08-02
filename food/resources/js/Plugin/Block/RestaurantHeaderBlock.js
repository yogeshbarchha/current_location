jQuery(document).ready(function() {
	jQuery('.view_deal_link img').click(function() {
		jQuery(this).parent('.view_deal_link').find('a').click();
	});
});
