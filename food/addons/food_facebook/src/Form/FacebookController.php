<?php

namespace Drupal\food_facebook\Form;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;

class FacebookController extends ControllerBase {
	
	public function oauth2() {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		//Without TRUE passed to toString below, we get this exception:
		//LogicException: The controller result claims to be providing relevant cache metadata, but leaked metadata was detected. Please ensure you are not rendering content too early. Returned object class: Drupal\Core\Routing\TrustedRedirectResponse.
		//https://drupal.stackexchange.com/a/187094
		//https://www.drupal.org/node/2630808
		$generatedUrl = Url::fromRoute('food_facebook.signin.oauth2callback')->setAbsolute()->toString(TRUE);
		$returnUrl = $generatedUrl->getGeneratedUrl();
		
		$params = [
			"response_type" => "token",
			"client_id" => $platform_settings->facebook_settings->client_id,
			"redirect_uri" => $returnUrl,
			"scope" => "email",
			"state" => uniqid(),
		];

		$url = "https://www.facebook.com/dialog/oauth?" . http_build_query($params);
		return new TrustedRedirectResponse($url);
	}

	public function oauth2Callback() {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

		$build['#attached']['library'][] = 'food_facebook/oauth2callback';
		$build['#attached']['drupalSettings']['food'] = [
			'processOAuthResponseUrl' => Url::fromRoute('food_facebook.signin.processoauthresponse')->toString(),
		];
		
		return($build);
	}

	public function processOAuthResponse(Request $request) {
		$hash = $request->query->get('hash');
		parse_str($hash, $parameters);
		
		$access_token = PhpHelper::getNestedValue($parameters, ['access_token']);
		
		$profile = \Drupal\food_facebook\Core\FacebookController::getFacebookProfile($access_token);
		$email = PhpHelper::getNestedValue($profile, ['email']);
		$user = \Drupal\food\Core\UserController::ensureUser($email);
		user_login_finalize($user);

		$response = new AjaxResponse();
		return ($response);
	}
	
}
