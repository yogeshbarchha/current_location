<?php
namespace Drupal\food\Core;

abstract class PlatformController extends ControllerBase {

    public static function getSupportedCurrencies() {
		$currencies = [];

		$currencies['USD'] = self::getCurrency('USD', 'United States Dollar', '$');
		$currencies['INR'] = self::getCurrency('INR', 'Indian National Rupee', 'Rs.');
		
		return ($currencies);
    }
	
	public static function getPlatformSettings($returnNull = FALSE) {
		$platform_settings = &drupal_static('food.platform.platform_settings');
		if (isset($platform_settings)) {
			return ($platform_settings);
		}
		
		$result = db_select('config', 'c')
					->fields('c')
					->condition('name', 'food.platform.platform_settings')
					->execute()
					->fetchObject();

		if($result) {
			$decodedData = unserialize($result->data);
            $platform_settings = \Imbibe\Json\JsonHelper::deserializeObject($decodedData, '\Drupal\food\Core\Platform\PlatformSettings');
		} else {
			if($returnNull) {
				return (NULL);
			} else {
				$platform_settings = new \Drupal\food\Core\Platform\PlatformSettings();
			}
		}
		
		self::populateDefaultPlatformSettings($platform_settings);		
		return ($platform_settings);
	}
	
	public static function updatePlatformSettings($obj) {
		$encodedData = json_encode($obj);
		$encodedData = serialize($encodedData);
		
		drupal_static_reset('food.platform.platform_settings');
		$existingData = self::getPlatformSettings(TRUE);
		if($existingData != NULL) {
			db_update('config')
				->fields(array(
					'collection' => '',
					'data' => $encodedData,
				))
				->condition('name', 'food.platform.platform_settings')
				->execute();
		} else {
			db_insert('config')
				->fields(array(
					'collection' => '',
					'name' => 'food.platform.platform_settings',
					'data' => $encodedData,
				))
				->execute();
		}
		
		drupal_static_reset('food.platform.platform_settings');
	}
	
	private static function populateDefaultPlatformSettings($platform_settings) {
		if(empty($platform_settings->currency_code)) {
			$platform_settings->currency_code = 'USD';
		}

		if(empty($platform_settings->derived_settings)) {
			$platform_settings->derived_settings = new \Drupal\food\Core\Platform\DerivedSettings();
		}

		if(empty($platform_settings->derived_settings->currency_symbol)) {
			$platform_settings->derived_settings->currency_symbol = self::getSupportedCurrencies()[$platform_settings->currency_code]->symbol;
		}

		$theme = \Drupal::service('theme.manager')->getActiveTheme();
		$platform_settings->derived_settings->theme_name = $theme->getName();
		
		$platform_settings->derived_settings->top_banner_url = '/themes/' . $theme->getName() . '/images/banner.jpg';
		$platform_settings->derived_settings->restaurant_name_image_url = '/themes/' . $theme->getName() . '/images/site-name.png';
		
		$themes = \Drupal::service('theme_handler')->listInfo();
		if(array_key_exists('bootstrap', $themes)) {
			$theme_settings = \Drupal\bootstrap\Bootstrap::getTheme();
			
			$food_theme_banner_image = $theme_settings->getSetting('food_theme_banner_image');
			if(is_array($food_theme_banner_image) && count($food_theme_banner_image) > 0) {
				$fid = intval($food_theme_banner_image[0]);
				$image = \Drupal\file\Entity\File::load($fid);

				if (isset($image)) {
					$platform_settings->derived_settings->top_banner_url = $image->url();
				}
			}
			
			$food_theme_restaurant_name_image = $theme_settings->getSetting('food_theme_restaurant_name_image');
			if(is_array($food_theme_restaurant_name_image) && count($food_theme_restaurant_name_image) > 0) {
				$fid = intval($food_theme_restaurant_name_image[0]);
				$image = \Drupal\file\Entity\File::load($fid);

				if (isset($image)) {
					$platform_settings->derived_settings->restaurant_name_image_url = $image->url();
				}
			}
		}
		
		$config = \Drupal::config('system.site');
		$platform_settings->derived_settings->site_name = $config->get('name');
		$platform_settings->derived_settings->site_slogan = $config->get('slogan');
		
		$platform_settings->derived_settings->logo_url = theme_get_setting('logo.url');
		if(!$platform_settings->derived_settings->logo_url) {
			$platform_settings->derived_settings->logo_url = '/themes/food_theme/images/logo.png';
		}
	}

	private static function getCurrency($code, $name, $symbol) {
		$c = new \Drupal\food\Core\Currency\Currency();

		$c->code = $code;
		$c->name = $name;
		$c->symbol = $symbol;
		
		return ($c);
	}
	
}
