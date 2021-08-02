<?php

namespace Drupal\food_google\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;
use Drupal\food\Core\ControllerBase;

abstract class GoogleController extends ControllerBase {

	public static function exchangeAuthCode($auth_code) {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		$generatedUrl = Url::fromRoute('food_google.signin.oauth2callback')->setAbsolute()->toString(TRUE);
		$returnUrl = $generatedUrl->getGeneratedUrl();

		$params = [
			"code" => $auth_code,
			"client_id" => $platform_settings->google_settings->client_id,
			"client_secret" => $platform_settings->google_settings->client_secret,
			"redirect_uri" => $returnUrl,
			"grant_type" => 'authorization_code',
		];
		$tokenExchangeUrl = "https://www.googleapis.com/oauth2/v3/token";

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $tokenExchangeUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		
		$result = json_decode($result);
		if(isset($result->error)) {
			throw new \Exception('Invalid Google token.');			
		} else {
			$access_token = PhpHelper::getNestedValue($result, ['access_token']);
			//$returnUrl = PhpHelper::getNestedValue($result, ['state']);

			return ($access_token);
		}
	}

	public static function getGoogleProfile($access_token, $validateApp = FALSE) {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		if($validateApp) {
			self::validateAppToken($access_token);
		}
		
		$params = [
			"access_token" => $access_token,
		];
		$tokenValidationUrl = "https://www.googleapis.com/oauth2/v3/tokeninfo?" . http_build_query($params);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $tokenValidationUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		
		$result = json_decode($result);
		if(isset($result->error)) {
			throw new \Exception('Invalid Google token.');			
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
			"id_token" => $access_token,
		];
		$tokenValidationUrl = "https://www.googleapis.com/oauth2/v3/tokeninfo?" . http_build_query($params);

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $tokenValidationUrl);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($curl, CURLOPT_POST, true);
		//curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
		//curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = curl_exec($curl);
		
		$result = json_decode($result);
		if(isset($result->error)) {
			throw new \Exception('Invalid Google token.');			
		} else {
			$aud = PhpHelper::getNestedValue($result, ['aud']);
			if($aud != $platform_settings->google_settings->client_id) {
				//throw new \Exception('The specified token does not belong to an authorized app.');
			}
		}
	}
	
}
