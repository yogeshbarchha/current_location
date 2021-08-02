jQuery(document).ready(function() {
	var lastOAuthWindow = null;
	
	jQuery('body').on('click', '.food_facebook_signin_button', function() {
		lastOAuthWindow = window.open("/food/facebook/signin/oauth2", "facebook_sign_in", "width=900,height=700");
		return(false);
	});
});
