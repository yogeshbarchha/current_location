<?php

namespace Drupal\food_stripe\Api;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

    public function execute($op) {
		$data = NULL;
		
		switch ($op) {
			case 'Stripe.CreateCharge':
				$data = StripeResponder::createCharge();
				break;
        }

        return $data;
    }

}
