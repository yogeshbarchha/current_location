<?php

namespace Drupal\food\Core;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Serialization\Json;
/**
* 
*/
class UserReferenceController extends ControllerBase
{
	
	public function userInfo(Request $request){
      $uid = $request->get('uid');

      if(!empty($uid) && is_numeric($uid)){
      	$account = \Drupal\user\Entity\User::load($uid);
      	if(!empty($account)){
      		$user = array();
      		$user['name'] = $account->getUsername();
      		$user['email'] = $account->getEmail();
      		$user['phone'] = isset($account->toArray()['field_phone_number'][0]) ? $account->toArray()['field_phone_number'][0]['value'] : '';

      		$response = new Response();
			$response->setContent(json_encode($user));
			$response->headers->set('Content-Type', 'application/json');
			return $response;
      	}

      }
	}
}