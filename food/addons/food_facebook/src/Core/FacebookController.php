<?php

namespace Drupal\food_facebook\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;
use Drupal\food\Core\ControllerBase;

abstract class FacebookController extends ControllerBase {

	public static function getFacebookProfile($access_token, $validateApp = FALSE) {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		if($validateApp) {
			self::validateAppToken($access_token);
		}
		
		$params = [
			"client_id" => $platform_settings->facebook_settings->client_id,
			"client_secret" => $platform_settings->facebook_settings->client_secret,
			"access_token" => $access_token,
			"fields" => "email",
		];
		$tokenValidationUrl = "https://graph.facebook.com/me?" . http_build_query($params);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $tokenValidationUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		
		$result = json_decode($result);
		if(isset($result->error)) {
			throw new \Exception('Invalid Facebook token.');			
		} else {
			$email = PhpHelper::getNestedValue($result, ['email']);
			//$returnUrl = PhpHelper::getNestedValue($result, ['state']);
			
			return ([
				'email' => $email,
			]);
		}
	}

	private static function validateAppToken($access_token) {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		$params = [
			"input_token" => $access_token,
		];
		$tokenValidationUrl = "https://graph.facebook.com/debug_token?" . http_build_query($params);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $tokenValidationUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		
		$result = json_decode($result);
		if(isset($result->error)) {
			throw new \Exception('Invalid Facebook token.');			
		} else {
			$app_id = PhpHelper::getNestedValue($result, ['data', 'app_id']);
			
			if($app_id != $platform_settings->facebook_settings->client_id) {
				//throw new \Exception('The specified token does not belong to an authorized app.');
			}
		}
	}
	
}
