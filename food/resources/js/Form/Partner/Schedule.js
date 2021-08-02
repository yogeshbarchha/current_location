jQuery(document).ready(function() {
	function refreshReport() {
		Drupal.ajax({
			url: drupalSettings.food.scheduleRefreshUrl,
			success: function() {
				Drupal.Ajax.prototype.success.apply(this, arguments);
				setTimeout(refreshReport, 5000);
			}
		}).execute();
	}
	
	//jQuery('body').append('<audio id="beep"><source src="/modules/food/resources/sound/beep-09.mp3" type="audio/mpeg"></audio>');
	setTimeout(refreshReport, 5000);
});


Drupal.behaviors.dashboard = {
  attach: function (context, settings) {

	jQuery('ul.pagination li a').once().click(function(event) {
		event.preventDefault();
		var url = jQuery(this).attr('href');
        var page = getUrlParameter(url,'page');
        var ids = [];
		jQuery('.mutliSelect input[type="checkbox"]:checked').each(function(){
			ids.push(jQuery(this).val());
		});
		var params = {restaurant_ids : ids, page:page};
        Drupal.ajax({
	      url: drupalSettings.food.scheduleRefreshUrl + '?' + jQuery.param(params),
	      success: function() {
	        Drupal.Ajax.prototype.success.apply(this, arguments);
	      }
	    }).execute();
	});
  }
};

function getUrlParameter(url,name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(url);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};