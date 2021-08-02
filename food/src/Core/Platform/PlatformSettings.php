<?php

namespace Drupal\food\Core\Platform;

class PlatformSettings {

	/**
     * @var string
     */
	public $currency_code;

	/**
     * @var boolean
     */
	public $use_miles;

	/**
     * @var int
     */
	public $country_calling_code;

	/**
     * @var string
     */
	public $partner_support_phone_number;

	/**
     * @var string
     */
	public $user_support_phone_number;

	/**
     * @var string
     */
	public $email;

	/**
     * @var string
     */
	public $address;

	/**
     * @var string
     */
	public $google_api_key;
	
	
	/**
     * @var \Drupal\food\Core\Platform\PlatformGoogleSettings
     */
	public $platform_google_settings;
	
	/**
     * @var \Drupal\food\Core\Platform\OrderSettings
     */
	public $order_settings;
	
	/**
     * @var \Drupal\food\Core\Platform\DerivedSettings
     */
	public $derived_settings;
	
	
	public function setUndefinedJsonProperty($mapper, $propName, $jsonValue) {
		$parts = explode('_', $propName);
		for($i = 0; $i < count($parts); $i++) {
			$parts[$i] = ucfirst($parts[$i]);
		}
		
		$instance = \Drupal\food\Util::getAddOnModuleClassInstance($parts[0], 'Core\\Platform\\' . implode("", $parts), FALSE);

		if(!empty($instance)) {
			$instance = $mapper->map($jsonValue, $instance);
			$this->$propName = $instance;
		}
	}
}
