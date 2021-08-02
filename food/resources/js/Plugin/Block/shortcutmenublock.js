// jQuery(document).ready(function() {
//  jQuery(".ShortcutMenu a").click(function(){
//      var id = jQuery(this).attr('id');
//      jQuery(".ShortcutMenuContent #"+id).siblings().hide();
//      jQuery(".ShortcutMenuContent").slideToggle("slow");
//      jQuery(".ShortcutMenuContent #"+id).slideToggle("slow");
//  });
// });

jQuery('.show-form').on('click', function (event){
    event.preventDefault();
    var elem = jQuery(this); //writing $(this) every time is bad
    var target = jQuery('div[data-target="'+elem.attr("data-target")+'"]');

    if(elem.hasClass('active')){ 
        //remove from this
        elem.removeClass("active");
        //close box    
        target.slideUp("slow");
    } else { //toggle menu when clicking on some other link
        //remove from everywhere
        jQuery('.show-form').removeClass('active');
        //slide every box up
        jQuery('.collapse').slideUp("slow");
        //add to this only
        elem.addClass('active'); 
        //slide associated box down
        target.slideDown("slow");
    }
});

jQuery(document).ready(function() {
   if(drupalSettings.food.admin){
        setTimeout(refreshNotifications, 5000);
        setTimeout(refreshNotifications2, 5000);
        setTimeout(customercancelorderNotifications, 5000);
        setTimeout(vendorsubusercancelorderNotifications, 5000);
    }

    if(drupalSettings.food.partner){
        setTimeout(adjustmentNotifications, 5000);
        setTimeout(customercancelorderNotifications, 5000);
        setTimeout(vendorsubusercancelorderNotifications, 5000);
    }
});

function refreshNotifications() {
    // alert(drupalSettings.food.restaurantStatus);
    Drupal.ajax({
        url: drupalSettings.food.newShortcutNotificationRefreshUrl,
        success: function() {
            Drupal.Ajax.prototype.success.apply(this, arguments);
            var count = jQuery('.ShortcutMenuContent[data-target="new_restaurants"] .restaurant-notifications li' ).length
            // alert(count);
            if (count) {
                jQuery('a[data-target="new_restaurants"] sup').text(count);
            }else{
                jQuery('a[data-target="new_restaurants"] sup').text('');
            }
            setTimeout(refreshNotifications, 5000);
        }
    }).execute();
}

function refreshNotifications2() {
    // alert(drupalSettings.food.restaurantStatus);
    Drupal.ajax({
        url: drupalSettings.food.updatedShortcutNotificationRefreshUrl,
        success: function() {
            Drupal.Ajax.prototype.success.apply(this, arguments);
            var count = jQuery('.ShortcutMenuContent[data-target="updated_restaurants"] .restaurant-notifications li' ).length
            // alert(count);
            if (count) {
                jQuery('a[data-target="updated_restaurants"] sup').text(count);
            }else{
                jQuery('a[data-target="updated_restaurants"] sup').text('');
            }
            setTimeout(refreshNotifications2, 5000);
        }
    }).execute();
}


function adjustmentNotifications() {
    Drupal.ajax({
        url: drupalSettings.food.adjustmentNotificationRefreshUrl,
        success: function() {
            Drupal.Ajax.prototype.success.apply(this, arguments);
            var count = jQuery('.ShortcutMenuContent[data-target="order_adjustment"] .restaurant-notifications li' ).length
            if (count) {
                jQuery('a[data-target="order_adjustment"] sup').text(count);
            }else{
                jQuery('a[data-target="order_adjustment"] sup').text('');
            }
            setTimeout(adjustmentNotifications, 5000);
        }
    }).execute();
}

function customercancelorderNotifications() {
    Drupal.ajax({
        url: drupalSettings.food.customerCancelOrderNotificationRefreshUrl,
        success: function() {
            Drupal.Ajax.prototype.success.apply(this, arguments);
            var count = jQuery('.ShortcutMenuContent[data-target="customer_cancel_order"] .restaurant-notifications li' ).length
            if (count) {
                if(jQuery("audio#beep").length){
                    jQuery('#beep')[0].play();
                }else{
                   jQuery('body').append('<audio id="beep"><source src="/modules/food/resources/sound/beep-09.mp3" type="audio/mpeg"></audio>');
                   jQuery('#beep')[0].play();
                }
                jQuery('a[data-target="customer_cancel_order"] sup').text(count);
            }else{
                jQuery('a[data-target="customer_cancel_order"] sup').text('');
            }
            setTimeout(customercancelorderNotifications, 5000);
        }
    }).execute();
}

function vendorsubusercancelorderNotifications() {
    Drupal.ajax({
        url: drupalSettings.food.vendorSubuserCancelOrderNotificationRefreshUrl,
        success: function() {
            Drupal.Ajax.prototype.success.apply(this, arguments);
            var count = jQuery('.ShortcutMenuContent[data-target="vendor_subuser_cancel_order"] .restaurant-notifications li' ).length
            if (count) {
                jQuery('a[data-target="vendor_subuser_cancel_order"] sup').text(count);
            }else{
                jQuery('a[data-target="vendor_subuser_cancel_order"] sup').text('');
            }
            setTimeout(vendorsubusercancelorderNotifications, 5000);
        }
    }).execute();
}