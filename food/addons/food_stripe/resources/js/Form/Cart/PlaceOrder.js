(function ($, Drupal, drupalSettings) {
  jQuery(document).ready(function () {
    setupStripe();
  });

  if ($('.order_payment_mode-cash').length) {
    $('#edit-card-element').hide();
  }

})(jQuery, Drupal, drupalSettings);

function setupStripe () {
  if (!window['Stripe']) {
    return;
  }

  var isExplicitSubmit = false;

  function stripeTokenHandler (token) {
    // Insert the token ID into the form so it gets submitted to the server
    var form = document.getElementById('food-user-cart-order-placement-form');
    jQuery('input[name=card_auth_token]').val(token.id);

    // Submit the form
    //form.submit();
    isExplicitSubmit = true;
    jQuery('#edit-submit').click();
    isExplicitSubmit = false;
    jQuery('#edit-submit').attr('disabled', 'disabled')
  }

  var style = {
    base: {
      color: '#303238',
      fontSize: '16px',
      lineHeight: '42px',
      fontSmoothing: 'antialiased',
      '::placeholder': {
        color: '#ccc'
      }
    },
    invalid: {
      color: '#e5424d',
      ':focus': {
        color: '#303238'
      }
    }
  };

  var classes = {
    base: 'credit-card-form-base',
    empty: 'credit-card-form-empty'
  };
  var stripe = Stripe(
    document.location.protocol.toLowerCase() == 'https:' ? drupalSettings.food.stripe.live_publishable_api_key : drupalSettings.food.stripe.test_publishable_api_key);
  var elements = stripe.elements();
  var card = elements.create('card', {style: style, classes: classes});
  card.mount('#card-element');

  card.addEventListener('change', function (event) {
    var displayError = document.getElementById('card-errors');
    if (event.error) {
      displayError.textContent = event.error.message;
    }
    else {
      displayError.textContent = '';
    }
  });

  var form = document.getElementById('food-user-cart-order-placement-form');
  form.addEventListener('submit', function (event) {
    if (isExplicitSubmit) {
      return;
    }

    if (jQuery('div[data-drupal-selector="edit-card"]')
      .is(':visible') == false) {
      //Cash
      return;
    }

    event.preventDefault();

    stripe.createToken(card).then(function (result) {
      if (result.error) {
        // Inform the user if there was an error
        var errorElement = document.getElementById('card-errors');
        errorElement.textContent = result.error.message;
      }
      else {
        // Send the token to your server
        stripeTokenHandler(result.token);
      }
    });
  });
}
