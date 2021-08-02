<?php

namespace Drupal\food_facebook\Api;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

    public function execute($op) {
		$data = NULL;
		
		switch ($op) {
			case 'Facebook.Login':
				$data = FacebookResponder::authenticate();
				break;
        }

        return $data;
    }

}
