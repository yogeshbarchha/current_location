/*
  Dropdown with Multiple checkbox select with jQuery - May 27, 2013
  (c) 2013 @ElmahdiMahmoud
  license: https://www.opensource.org/licenses/mit-license.php
*/

jQuery(".dropdown dt a").on('click', function() {
  jQuery(".dropdown dd ul").slideToggle('fast');
});

jQuery(".dropdown dd ul li a").on('click', function() {
  jQuery(".dropdown dd ul").hide();
});

function getSelectedValue(id) {
  return jQuery("#" + id).find("dt a span.value").html();
}

jQuery(document).ready(function() {
  jQuery(document).bind('click', function(e) {
    var jQueryclicked = jQuery(e.target);
    if (!jQueryclicked.parents().hasClass("dropdown")){
      jQuery(".dropdown dd ul").hide();
    }
  });
});

jQuery('.mutliSelect input[type="checkbox"]').on('change', function() {
  
  if(jQuery(this).val() == 'All'){
    jQuery('.mutliSelect input[type="checkbox"]').prop('checked', this.checked);
    if(jQuery(this).is(':checked')){
      var title = 'All Restaurant';  
    }else{
      var title = 'No Restaurant';  
    }
    
  }else if(jQuery(".mutliSelect input[type='checkbox']:checked").length > 1){
    var title = 'Multiple Restaurant';
  }else if(jQuery(".mutliSelect input[type='checkbox']:checked").length == 1)   {
    var title = jQuery(".mutliSelect input[type='checkbox']:checked").attr('title');
  }else{
    var title = 'No Restaurant';
  }
  var html = '<span title="' + title + '">' + title + '</span>';
  jQuery('.multiSel').html(html);
  jQuery(".hida").hide();
  // Refresh tabel on filter
  var ids = [];
  jQuery('.mutliSelect input[type="checkbox"]:checked').each(function(){
    ids.push(jQuery(this).val());
  });
  var params = {restaurant_ids: ids};
  if (drupalSettings.food.dashboardOrderStatus == 'active') {
    Drupal.ajax({
      url: drupalSettings.food.dashboardRefreshUrl + '?' + jQuery.param(params),
      success: function() {
        Drupal.Ajax.prototype.success.apply(this, arguments);
        var playBeep = getCookie('food_partner_play_beep');
        if(playBeep == 'yes') {
          jQuery(".header-top").css({ 'background':'#32CD32' });
          jQuery("#partner-order-list .food-entity-list-table tbody tr").css({'color':'#fff','background-color':'#32CD32'});
          jQuery('#beep')[0].play();
        }else{
          jQuery(".header-top").css({ 'background':'#868533' });
        }
      }
    }).execute();
  }
  else if (drupalSettings.food.dashboardOrderStatus == 'complete') {
    Drupal.ajax({
      url: drupalSettings.food.completeOrderRefreshUrl + '?' + jQuery.param(params),
      success: function() {
        Drupal.Ajax.prototype.success.apply(this, arguments);
      }
    }).execute();
  }
  else if (drupalSettings.food.dashboardOrderStatus == 'cancel') {
    Drupal.ajax({
      url: drupalSettings.food.cancelOrderRefreshUrl + '?' + jQuery.param(params),
      success: function() {
        Drupal.Ajax.prototype.success.apply(this, arguments);
      }
    }).execute();
  }
  else if (drupalSettings.food.dashboardOrderStatus == 'scheduled') {
    Drupal.ajax({
      url: drupalSettings.food.scheduleRefreshUrl + '?' + jQuery.param(params),
      success: function() {
        Drupal.Ajax.prototype.success.apply(this, arguments);
      }
    }).execute();
  }
});