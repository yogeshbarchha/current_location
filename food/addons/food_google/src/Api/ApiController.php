<?php

namespace Drupal\food_google\Api;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

    public function execute($op) {
		$data = NULL;
		
		switch ($op) {
			case 'Google.Login':
				$data = GoogleResponder::authenticate();
				break;
        }

        return $data;
    }

}
