<?php

namespace Drupal\food_google\Api;
use Drupal\food\Api\ApiResponderBase;

abstract class GoogleResponder extends ApiResponderBase {

    public static function authenticate() {
        $auth_code = $_POST['auth_code'];
		
		$access_token = \Drupal\food_google\Core\GoogleController::exchangeAuthCode($auth_code);
		$profile = \Drupal\food_google\Core\GoogleController::getGoogleProfile($access_token, FALSE);
		$email = \Imbibe\Util\PhpHelper::getNestedValue($profile, ['email']);
		$user = \Drupal\food\Core\UserController::ensureUser($email);
		
		$profile = \Drupal\food\Core\UserController::getUserProfile($user->id());
		
		$token = \Drupal\food\Core\UserController::ensureUserAuthToken($user->id());
		$profile['token'] = $token->token;
		
		return(array('success' => true, 'data' => $profile));
    }

}
