Drupal.behaviors.food = {
  attach: function (context, settings) {
    // Using once() with more complexity.
    jQuery('.food_facebook_signin_button, .food_google_signin_button').once().on('click', function () {
        jQuery('#drupal-modal').modal('toggle');
    });
  }
};