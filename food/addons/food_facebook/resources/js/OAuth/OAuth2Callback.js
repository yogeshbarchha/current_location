jQuery(document).ready(function() {
	var hash = document.location.hash;
	if(hash) {
		if(hash[0] == '#') {
			hash = hash.substr(1);
		}
		
		Drupal.ajax({
			url: drupalSettings.food.processOAuthResponseUrl + '?hash=' + encodeURIComponent(hash),
			success: function() {
				if(window.opener) {
					window.opener.location.reload();
				}
				window.close();
			}
		}).execute();		
	}
});
