jQuery(document).ready(function() {
	var lastOAuthWindow = null;
	
	jQuery('body').on('click', '.food_google_signin_button', function() {
		lastOAuthWindow = window.open("/food/google/signin/oauth2", "google_sign_in", "width=900,height=700");
		return(false);
	});
});
