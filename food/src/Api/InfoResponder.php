<?php

namespace Drupal\food\Api;
use Imbibe\Util\PhpHelper;

abstract class InfoResponder extends ApiResponderBase {

	public static function getAboutInfo() {
        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		
		return array(
			'data' => array(
				'company_name' => PhpHelper::getNestedValue($platform_settings, ['derived_settings', 'site_name']),
				'contact_number' => PhpHelper::getNestedValue($platform_settings, ['user_support_phone_number']),
				'email' => PhpHelper::getNestedValue($platform_settings, ['email']),
				'office_address' => PhpHelper::getNestedValue($platform_settings, ['address']),
			)
		);
	}
}
