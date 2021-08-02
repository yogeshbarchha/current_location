<?php

namespace Drupal\food_facebook\Api;
use Drupal\food\Api\ApiResponderBase;

abstract class FacebookResponder extends ApiResponderBase {

    public static function authenticate() {
        $access_token = $_POST['access_token'];

		$profile = \Drupal\food_facebook\Core\FacebookController::getFacebookProfile($access_token, FALSE);
		$email = \Imbibe\Util\PhpHelper::getNestedValue($profile, ['email']);
		$user = \Drupal\food\Core\UserController::ensureUser($email);
		
		$profile = \Drupal\food\Core\UserController::getUserProfile($user->id());

		$token = \Drupal\food\Core\UserController::ensureUserAuthToken($user->id());
		$profile['token'] = $token->token;
		
		return(array('success' => true, 'data' => $profile));
    }

}
