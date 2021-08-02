<?php

namespace Drupal\food\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;

abstract class RestaurantController extends ControllerBase {
	const NODE_NEW_LIMIT = REQUEST_TIME - 30 * 24 * 60 * 60;
    const MARK_READ = 0;
    const MARK_NEW = 1;
    const MARK_UPDATED = 2;

    public static function searchRestaurants($conditionCallback = NULL, $config = array()) {
      /*  $query = db_select('food_restaurant', 'fr')
            ->fields('fr');*/
			/*updated by Manmohan 050418 */
			$query = db_select('food_restaurant', 'fr')
            ->fields('fr', array('restaurant_id', 'created_time', 'created', 'status', 'name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'latitude', 'longitude',   'about_detailed',  'speciality', 'order_types', 'timings', 'deals', 'tax_pct','image_fid'));

        if ($conditionCallback != NULL) {
            $query = call_user_func_array($conditionCallback, [$query]);
        }

        $config['defaultSortField'] = 'name';
        $config['hydrateCallback'] = array('\Drupal\food\Core\RestaurantController', 'hydrateRestaurant');
        $rows = ControllerBase::executeListQuery($query, $config);

        self::assignImageUrls($rows);

        return($rows);
    }

    public static function getAllRestaurantsByStatus($status) {
        $rows = self::searchRestaurants(function($query) use (&$status) {
                $query = $query
                    ->condition('fr.status', $status);

                return($query);
            });

        self::assignImageUrls($rows);

        return($rows);
    }

    public static function searchRestaurantsBySearchParams($search_params) {
        $cart = \Drupal\food\Core\CartController::getCurrentCart(['search_mode' => \Drupal\food\Core\Cart\SearchMode::Address, 'search_params' => $search_params]);

        $restaurants = self::searchRestaurants(function($query) use (&$search_params) {
                return(self::addSearchParamsDbSearchConditions($query, $search_params));
            });

        $restaurants = self::filterRestaurantsBySearchParams($restaurants, $search_params);
			self::assignImageUrls($restaurants);
	
			foreach ($restaurants as $restaurant) {
				$restaurant->restaurant_url = Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $restaurant->restaurant_id])->toString();
			}
		
		usort($restaurants, function($r1, $r2) {
			if($r1->distance == $r2->distance) {
				return (0);
			} elseif($r1->distance < $r2->distance) {
				return (-1);
			} else {
				return (1);
			}
		});

        return ($restaurants);
    }

    public static function addSearchParamsDbSearchConditions($query, $search_params) {
        $query = $query
            ->condition('status', \Drupal\food\Core\EntityStatus::Enabled);

		$query = self::addRestaurantDistanceExpression($query, $search_params->latitude, $search_params->longitude);

        if (isset($search_params->cuisine_ids) && count($search_params->cuisine_ids) > 0) {
            $innerQuery = db_select('food_restaurant_cuisine', 'frc')
                ->condition('cuisine_id', $search_params->cuisine_ids, 'IN')
                ->fields('frc', ['restaurant_id']);
            $query = $query
                ->condition('restaurant_id', $innerQuery, 'IN');
        }

        if (isset($search_params->service_area_ids) && count($search_params->service_area_ids) > 0) {
			$expression = self::getDbDistanceExpression('fsa.latitude', 'fsa.longitude', 'fr.latitude', 'fr.longitude');
            $conditionQuery = db_select('food_service_area', 'fsa')
				->condition('fsa.service_area_id', $search_params->service_area_ids, 'IN')
                ->where("$expression <= fsa.radius")
                ->fields('fsa', ['service_area_id']);
            $query = $query
                ->exists($conditionQuery);
        }

        if (isset($search_params->dish_ids) && count($search_params->dish_ids) > 0) {
            $innerQuery = db_select('food_restaurant_menu_item', 'frmi')
                ->condition('dish_id', $search_params->dish_ids, 'IN')
                ->fields('frmi', ['restaurant_id']);
            $query = $query
                ->condition('restaurant_id', $innerQuery, 'IN');
        }

        return($query);
    }

    public static function getCurrentUserRestaurants($config = array()) {
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isPlatform = $currentUser->hasRole(RoleController::Platform_Role_Name);
        $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
        $isSubuser = $currentUser->hasRole(RoleController::Subuser_Role_Name);

        $query = db_select('food_restaurant', 'fr')
            ->fields('fr');

        if ($isPartner) {
            $query = $query
                ->condition('fr.owner_user_id', \Drupal::currentUser()->id());
        }elseif($isSubuser){
        	$restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
        	$restaurant_owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurants[0]);
        	if(!empty($restaurants)){
	        	$query = $query
	                ->condition('fr.owner_user_id', $restaurant_owner_id);
	            $query = $query
	                ->condition('fr.restaurant_id', $restaurants, 'IN');
        	}else{
        		$query = $query->condition('fr.restaurant_id', 0);
        	}
        }
        
        $conditionCallback = PhpHelper::getNestedValue($config, ['conditionCallback']);
		if($conditionCallback != NULL) {
			$query = call_user_func_array($conditionCallback, [$query]);
		}

        $config['defaultSortField'] = 'name';
        $config['hydrateCallback'] = array('\Drupal\food\Core\RestaurantController', 'hydrateRestaurant');
        $rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }

    public static function getRestaurantById($restaurant_id, $config = array()) {
        $query = db_select('food_restaurant', 'fr')
            ->condition('restaurant_id', $restaurant_id)
        /* ->fields('fr');*/
		/*updated by Manmohan 090518 */
		//$query = db_select('food_restaurant', 'fr')
            ->fields('fr', array('restaurant_id', 'created_time', 'created', 'status', 'name', 'address_line1', 'address_line2', 'city', 'state', 'postal_code', 'country', 'latitude', 'longitude',   'about_detailed',  'speciality', 'order_types', 'timings', 'deals', 'tax_pct','image_fid'));

        $config['hydrateCallback'] = array('\Drupal\food\Core\RestaurantController', 'hydrateRestaurant');
        $row = ControllerBase::executeRowQuery($query, $config);
        return($row);
    }

    public static function getRestaurantRouletteDiscounts($restaurant_id) {
		$restaurant = self::getRestaurantById($restaurant_id);
		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		
		$discounts = [];

		$disable_platform_deals1 = PhpHelper::getNestedValue($platform_settings, ['order_settings', 'disable_platform_deals', FALSE]);
		$disable_platform_deals2 = PhpHelper::getNestedValue($restaurant, ['platform_settings', 'disable_platform_deals', FALSE]);
		if($disable_platform_deals1 != TRUE && $disable_platform_deals2 != TRUE) {
			$deals = PhpHelper::getNestedValue($restaurant, ['platform_settings', 'deals']);
			if(empty($deals)) {
				$deals = PhpHelper::getNestedValue($platform_settings, ['deals']);
			}
			
			if(empty($deals)) {
				$discounts[] = ['discount_pct' => 5, 'text' => 'Discount ' . 5 . '%'];
				$discounts[] = ['discount_pct' => 18, 'text' => 'Discount ' . 18 . '%'];
				$discounts[] = ['discount_pct' => 5, 'text' => 'Discount ' . 5 . '%'];
				$discounts[] = ['discount_pct' => 7, 'text' => 'Discount ' . 7 . '%'];
				$discounts[] = ['discount_pct' => 8, 'text' => 'Discount ' . 8 . '%'];
				$discounts[] = ['discount_pct' => 9, 'text' => 'Discount ' . 9 . '%'];
				$discounts[] = ['discount_pct' => 10, 'text' => 'Discount ' . 10 . '%'];
				$discounts[] = ['discount_pct' => 12, 'text' => 'Discount ' . 12 . '%'];
				$discounts[] = ['discount_pct' => 7, 'text' => 'Discount ' . 7 . '%'];
				$discounts[] = ['discount_pct' => 9, 'text' => 'Discount ' . 9 . '%'];
			} else {
				foreach($deals as $deal) {
					$discounts[] = ['discount_pct' => $deal->discount_pct, 'text' => 'Discount ' . $deal->discount_pct . '%'];
				}
			}
		}
		
		return ($discounts);		
    }

    public static function getRestaurantTipPercentages($restaurant_id) {
		$arr = [];

		$arr[] = ['tip_pct' => 0, 'text' => 'Tip'];
		$arr[] = ['tip_pct' => 10, 'text' => '10 %'];
		$arr[] = ['tip_pct' => 15, 'text' => '15 %'];
		$arr[] = ['tip_pct' => 20, 'text' => '20 %'];
		$arr[] = ['tip_pct' => 25, 'text' => '25 %'];
		
		return ($arr);		
    }

    public static function getRestaurantCondiments($restaurant_id) {
		$arr = [];

		$arr[] = ['text' => 'Knife and Fork'];
		$arr[] = ['text' => 'Napkins'];
		$arr[] = ['text' => 'Paper Plates'];
		$arr[] = ['text' => 'Salt'];
		$arr[] = ['text' => 'Pepper'];
		$arr[] = ['text' => 'Spoon'];
		$arr[] = ['text' => 'Straws'];
		$arr[] = ['text' => 'Toothpicks'];
		
		return ($arr);		
    }

    public static function isDeliverableByRestaurantId($restaurant_id, $latitude, $longitude) {
		$query = db_select('food_restaurant', 'fr')
					->fields('fr')
					->condition('restaurant_id', $restaurant_id);
					
		$query = self::addRestaurantDistanceExpression($query, $latitude, $longitude);
		
        $restaurant = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\RestaurantController', 'hydrateRestaurant'));
		
		return (self::isDeliverableByRestaurant($restaurant, $latitude, $longitude));
    }

    public static function isDeliverableByRestaurant($restaurant, $latitude, $longitude) {
		$distance = $restaurant->distance;
		
		switch ($restaurant->delivery_area_type) {
			case \Drupal\food\Core\Location\DeliveryAreaType::Circle:
				if ($restaurant->distance > $restaurant->delivery_radius) {
					return (FALSE);
				}
				break;

			case \Drupal\food\Core\Location\DeliveryAreaType::Polygon:
				$point = new \Drupal\food\Core\Location\Point();
				$point->latitude = $latitude;
				$point->longitude = $longitude;

				if ($point->checkPolygonPosition($restaurant->delivery_polygon) == \Drupal\food\Core\Location\PointPolygonPosition::Outside) {
					return (FALSE);
				}
				break;
		}
		
		return (TRUE);
    }

    public static function getApplicableTimingRange($restaurant, $time = NULL, $rangeName = 'open_timings') {
		if($time == NULL) {
			$time = time();
		}

		$day = strtolower(date('l', $time));
		$timing = PhpHelper::getNestedValue($restaurant, ['timings', $rangeName, $day]);

		return ($timing);
	}

    public static function isRestaurantOpen($restaurant, $time = NULL) {
		if($time == NULL) {
			$time = time();
		}

		$timing = self::getApplicableTimingRange($restaurant, $time, 'open_timings');

		return ($timing != NULL && $timing->isCurrent($time) ? TRUE : FALSE);
	}

    public static function isRestaurantDeliveryOpen($restaurant, $time = NULL) {
		if($time == NULL) {
			$time = time();
		}

		$day = strtolower(date('l', $time));
		$timing = PhpHelper::getNestedValue($restaurant, ['timings', 'delivery_timings', $day]);

		return ($timing != NULL && $timing->isCurrent($time) ? TRUE : FALSE);
	}
	
	public static function applyRestaurntDealToOrder($restaurant, $order) {
		$deals = PhpHelper::getNestedValue($restaurant, ['deals'], array());
		for($i = count($deals) - 1; $i >= 0; $i--) {
			$deal = $deals[$i];
			if($deal->min_order_amount <= $order->breakup->items_total_amount) {
				$order->breakup->restaurant_discount_pct = $deal->discount_pct;
				break;
			}
		}
	}
	
    public static function hydrateRestaurant($row) {
        $row->order_types = \Imbibe\Json\JsonHelper::deserializeObject($row->order_types, '\Drupal\food\Core\Restaurant\OrderTypeSettings');
        $row->timings = \Imbibe\Json\JsonHelper::deserializeObject($row->timings, '\Drupal\food\Core\Restaurant\RestaurantTimings');
        $row->delivery_polygon = \Imbibe\Json\JsonHelper::deserializeArray($row->delivery_polygon, '\Drupal\food\Core\Location\Point');
        $row->settlement_payment_settings = \Imbibe\Json\JsonHelper::deserializeObject($row->settlement_payment_settings, '\Drupal\food\Core\Restaurant\PaymentSettings');
        $row->order_contact_details = \Imbibe\Json\JsonHelper::deserializeObject($row->order_contact_details, '\Drupal\food\Core\Restaurant\OrderContactDetails');
        $row->platform_settings = \Imbibe\Json\JsonHelper::deserializeObject($row->platform_settings, '\Drupal\food\Core\Restaurant\PlatformSettings');
        $row->deals = \Imbibe\Json\JsonHelper::deserializeArray($row->deals, '\Drupal\food\Core\Restaurant\RestaurantDeal');

		$row->derived_fields = new \StdClass();
        if (isset($row->distance)) {
            $row->distance = round($row->distance, 3, PHP_ROUND_HALF_UP);
			$row->derived_fields->distance = $row->distance;
        }
		$row->derived_fields->current_open_timing_range = self::getApplicableTimingRange($row);
		$row->derived_fields->isOpen = self::isRestaurantOpen($row);
		$row->isOpen = $row->derived_fields->isOpen;
		
		$parts = [];
		if(!empty($row->address_line1)) $parts[] = $row->address_line1;
		if(!empty($row->address_line2)) $parts[] = $row->address_line2;
		if(!empty($row->city)) $parts[] = $row->city;
		if(!empty($row->state)) $parts[] = $row->state;
		if(!empty($row->country)) $parts[] = $row->country;
		if(!empty($row->postal_code)) $parts[] = $row->postal_code;
		
		$row->derived_fields->formatted_address = implode(", ", $parts);
		$row->formatted_address = $row->derived_fields->formatted_address;
    }

    private static function filterRestaurantsBySearchParams($restaurants, $search_params) {
        $restaurants = array_filter($restaurants, function($restaurant) use (&$search_params) {

            if (isset($search_params->delivery_mode) && !empty($search_params->delivery_mode)) {
                $delivery_mode_name = \Drupal\food\Core\Restaurant\DeliveryMode::getValueName($search_params->delivery_mode);
                $delivery_mode_settings = PhpHelper::getNestedValue($restaurant, ['order_types', strtolower($delivery_mode_name) . '_settings']);

                if (PhpHelper::getNestedValue($delivery_mode_settings, ['enabled']) != TRUE) {
                    return (FALSE);
                }

                if ($search_params->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
					$isDeliverable = self::isDeliverableByRestaurant($restaurant, $search_params->latitude, $search_params->longitude);
					if($isDeliverable == FALSE) {
						return (FALSE);
					}
                }
            }

            if (isset($search_params->restaurant_open_status)) {
                switch ($search_params->restaurant_open_status) {
                    case \Drupal\food\Core\Restaurant\RestaurantOpenStatus::Open:
                        if ($restaurant->derived_fields->isOpen == FALSE) {
                            return(FALSE);
                        }
                        break;

                    case \Drupal\food\Core\Restaurant\RestaurantOpenStatus::Closed:
                        if ($restaurant->derived_fields->isOpen == TRUE) {
                            return(FALSE);
                        }
                        break;
                }
            }

            if (isset($search_params->distance) && $search_params->distance != 0) {
                if ($restaurant->distance > $search_params->distance) {
                    return(FALSE);
                }
            }

            return (TRUE);
        });

        return($restaurants);
    }
	
	private static function addRestaurantDistanceExpression($query, $latitude, $longitude) {
		if(empty($latitude) || empty($longitude)) {
			$alias = $query->addExpression("0", 'distance');
		} else {
			$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
			$multiplier = $platform_settings->use_miles ? 3959 : 6371;

			//https://stackoverflow.com/questions/1006654/fastest-way-to-find-distance-between-two-lat-long-points
			$expression = self::getDbDistanceExpression($latitude, $longitude, 'latitude', 'longitude');
			$alias = $query->addExpression($expression, 'distance');
		}
		
		return ($query);
	}
	
	private static function getDbDistanceExpression($lat1, $lng1, $lat2, $lng2) {
		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
		$multiplier = $platform_settings->use_miles ? 3959 : 6371;
		
		//https://stackoverflow.com/questions/1006654/fastest-way-to-find-distance-between-two-lat-long-points
		$expression = "
(
	$multiplier *
	acos(
		cos(radians($lat1))
		* cos(radians($lat2))
		* cos(radians($lng2) - radians($lng1))
		+ sin(radians($lat1)) * sin(radians($lat2))
	)
)
";
		
		return ($expression);
	}

	public static function updateRestaurant($restaurant_id = NULL) {
        
        if($restaurant_id == null){
        	return;
        }

        $restaurant_entity = \Drupal::entityTypeManager()->getStorage('food_restaurant')->load($restaurant_id);
        $restaurant_entity->setChangedTime(REQUEST_TIME);
        $restaurant_entity->save();
	}

}
