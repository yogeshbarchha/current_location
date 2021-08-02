jQuery(document).ready(function() {
	jQuery('body').append('<audio id="beep"><source src="/modules/food/resources/sound/beep-09.mp3" type="audio/mpeg"></audio>');
	setTimeout(refreshReport(0), 5000);
});

function refreshReport(page = null) {
	var ids = [];
	jQuery('.mutliSelect input[type="checkbox"]:checked').each(function(){
		ids.push(jQuery(this).val());
	});
	page_id = 0;
	if(page != null){
      page_id = page;
	}
	var params = {restaurant_ids : ids, page:page_id};
	Drupal.ajax({
		url: drupalSettings.food.dashboardRefreshUrl + '?' + jQuery.param(params),
		success: function() {
			Drupal.Ajax.prototype.success.apply(this, arguments);
			var playBeep = getCookie('food_partner_play_beep');
			if(playBeep == 'yes') {
			    if(!jQuery('.header-top').hasClass('orderalertcolor')){
			        jQuery('.header-top').addClass('orderalertcolor');    
			    }
			    
			    if(!jQuery('#partner-order-list .food-entity-list-table tbody tr').hasClass('orderalertcolor')){
			        jQuery('#partner-order-list .food-entity-list-table tbody tr').addClass('orderalertcolor');    
			    }
				jQuery('#beep')[0].play();
			}else{
				jQuery('.header-top').removeClass('orderalertcolor');
		        jQuery('#partner-order-list .food-entity-list-table tbody tr').removeClass('orderalertcolor');
			}
			setTimeout(function() {
			    refreshReport(page_id);
			}, 5000);
		}
	}).execute();
}


Drupal.behaviors.dashboard = {
  attach: function (context, settings) {
	jQuery('ul.pagination li a').once().click(function(event) {
		event.preventDefault();
		var url = jQuery(this).attr('href');
        var page = getUrlParameter(url,'page');
        refreshReport(page);
	});
	
  }
};

function getUrlParameter(url,name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    var results = regex.exec(url);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
};

function blink(elem, times, speed) {
    if (times > 0 || times < 0) {
        if (jQuery(elem).hasClass("orderalertcolor")) 
            jQuery(elem).removeClass("orderalertcolor");
        else
            jQuery(elem).addClass("orderalertcolor");
    }

    clearTimeout(function () {
        blink(elem, times, speed);
    });

    if (times > 0 || times < 0) {
        setTimeout(function () {
            blink(elem, times, speed);
        }, speed);
        times -= .5;
    }
}