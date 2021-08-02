<?php

namespace Drupal\food\Api;

abstract class ApiResponderBase {

	public static function initializeAuthenticatedRequest() {
		$username = $_POST['username'];
		$token = $_POST['token'];

		if(empty($username)) {
			throw new \Exception('No user name specified.');
		}
		if(empty($token)) {
			throw new \Exception('No token specified.');
		}
		
		$uid = \Drupal::service('user.auth')->authenticate(
			$username,
			$token
		);
		
		if(!$uid) {
			$token = \Drupal\food\Core\UserController::getUserAuthTokenByToken($token);
			if($token) {
				$uid = $token->user_id;
			}
		}
		
		if(!$uid) {
			\Drupal::logger('food')->error('Invalid user name and/or token:- ' . $username . '/' . $token);
			throw new \Exception('Invalid user name and/or token.');
		}
		
		$user = \Drupal\user\Entity\User::load($uid);
		\Drupal\food\Core\UserController::setCurrentUser($user);
		
		return ($user);
	}

    public static function getPostVariable($name, $optional = FALSE, $defaultValue = NULL) {
        if(isset($_POST[$name])) {
            return ($_POST[$name]);
        } else {
            if($optional == FALSE) {
                throw new \Exception('Please provide ' . $name . '.');
            }
        }
		
		return($defaultValue);
    }

}
