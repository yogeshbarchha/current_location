<?php

namespace Drupal\food\Form\Rating;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class RatingController extends ControllerBase {
	
	public static function getRating(){

		$orderID = \Drupal::request()->request->get('orderID');
		$ratingPoints = \Drupal::request()->request->get('ratingPoints');
		$ratingNum = 1;

		if(!empty($orderID) && !empty($ratingPoints)){

			$ratingRow = \Drupal\food\Core\ReviewController::getAverageRatingByOrderId($orderID);

			if($ratingRow){
				$ratingRow['status'] = 'ok';
			}else{
				$ratingRow['status'] = 'err';
			}

			$response = new Response();
			$response->setContent(json_encode($ratingRow));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
		}
	}
}