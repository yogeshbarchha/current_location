<?php

namespace Drupal\food_stripe\Api;
use Drupal\food\Api\ApiResponderBase;

abstract class StripeResponder extends ApiResponderBase {

    public static function createCharge() {
        $token = $_POST['payment_id'];
        $amount = floatval($_POST['amount']);

		food_stripe_create_charge($token, $amount);

		return(array('success' => true));
    }

}
