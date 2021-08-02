<?php

namespace Drupal\food\Core;

use Imbibe\Util\PhpHelper;

abstract class CartController extends ControllerBase {

    public static function getSessionId() {
		static $food_session_id = NULL;
		
		if(!empty($food_session_id)) {
			return($food_session_id);
		}
		
        if (!empty($_COOKIE['food_session_id'])) {
            $food_session_id = $_COOKIE['food_session_id'];
			return($food_session_id);
        }
        
        $length = 25;
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $food_session_id = '';
        for ($i = 0; $i < $length; $i++) {
            $food_session_id .= $characters[rand(0, $charactersLength - 1)];
        }
        setcookie('food_session_id', $food_session_id, strtotime('+1 year'), "/");
        
        return($food_session_id);
    }

    public static function unlinkCurrentCart() {
		if (isset($_COOKIE['food_session_id'])) {
			unset($_COOKIE['food_session_id']);
			setcookie('food_session_id', null, -1, '/');
			return (true);
		} else {
			return (false);
		}
	}
	
    public static function getCurrentCart($updates = NULL, $config = NULL) {
		//Its important to keep variable name to $row below for drupal_static to work, if the variable was not present in the static cache.
		$row = &drupal_static('Drupal\food\Core\CartController::currentCart');
		if (isset($row)) {
			return ($row);
		}

		$food_session_id = self::getSessionId();
		
        $query = db_select('food_user_cart', 'fuc')
            ->condition('session_id', $food_session_id)
            ->fields('fuc',array('cart_id', 'session_id', 'created_time', 'restaurant_id', 'user_id', 'status', 'search_mode', 'search_params', 'order_details'));
			
		$row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\CartController', 'hydrateUserCart'));
		if($row == NULL) {
			$autoCreate = PhpHelper::getNestedValue($config, ['autoCreate']);
			if($autoCreate === FALSE) {
				return (NULL);
			}
			
			$arr = array(
				'session_id' => $food_session_id,
				'created_time' => \Imbibe\Util\TimeUtil::now(),
				'user_id' => \Drupal::currentUser()->id(),
				//Default to restaurant search mode. If search mode exists in $updates, it would be overwritten below in applyCartUpdates.
				'search_mode' => \Drupal\food\Core\Cart\SearchMode::Restaurant,
			);
			
			self::applyCartUpdates($arr, $updates);
			db_insert('food_user_cart')
				->fields($arr)
				->execute();
			
			$row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\CartController', 'hydrateUserCart'));
		} else {
			if($updates != NULL && count($updates) > 0) {
				$arr = (array) $row;
				self::applyCartUpdates($arr, $updates);
				self::updateCartInternal($arr);
				
				//$row = ControllerBase::executeRowQuery($query, array('\Drupal\food\Core\CartController', 'hydrateUserCart'));
				foreach($updates as $key => $value) {
					$row->$key = $value;
				}
			}
		}
		
		if($row->order_details == NULL) {
			$row->order_details = new \Drupal\food\Core\Order\Order();
		}
		
		return($row);
    }
	
    public static function updateCart($cart, $updates = NULL) {
		if(!empty($cart->order_details)) {
			$cart->order_details->restaurant_id = $cart->restaurant_id;
			$cart->order_details->updateBreakup();
		}

		$arr = (array) $cart;
		
		self::applyCartUpdates($arr, $updates);
		self::updateCartInternal($arr);
			
		drupal_static_reset('Drupal\food\Core\CartController::currentCart');
	}
	
	public static function hydrateUserCart($row) {
		$row->search_params = \Imbibe\Json\JsonHelper::deserializeObject($row->search_params, '\Drupal\food\Core\Cart\SearchParams');
		$row->order_details = \Imbibe\Json\JsonHelper::deserializeObject($row->order_details, '\Drupal\food\Core\Order\Order');

		$row->derived_fields = new \StdClass();
	}
    
	public static function createCartFromOrderDetails($order) {
		//Remove existing cart if any.
		self::unlinkCurrentCart();
		
		//Create a new cart.
		$cart = self::getCurrentCart([
			'restaurant_id' => $order->restaurant_id,
			'user_id' => \Drupal::currentUser()->id(),
			'order_details' => $order,
		]);
		
		return ($cart);
	}
	
	public static function createOrder($cart) {		
		$fields = array(
			'created_time' => \Imbibe\Util\TimeUtil::now(),
			'restaurant_id' => $cart->restaurant_id,
			'user_id' => $cart->user_id,
			'cart_id' => $cart->cart_id,
			'status' => \Drupal\food\Core\Order\OrderStatus::Submitted,
			'delivery_mode' => $cart->order_details->delivery_mode,
			'payment_mode' => $cart->order_details->payment_mode,
			'net_amount' => $cart->order_details->breakup->net_amount,
			'order_details' => json_encode($cart->order_details),
			'user_name' => $cart->order_details->user_name,
			'user_address_id' => $cart->order_details->user_address_id,
			'user_phone' => $cart->order_details->user_phone,
			'user_apartment_number' => $cart->order_details->user_apartment_number,
			'user_address' => $cart->order_details->user_address,
			'user_ip_address' => $_SERVER['REMOTE_ADDR'],
			'instructions' => $cart->order_details->instructions,
		);
		
		db_insert('food_order')
			->fields($fields)
			->execute();

		$order = OrderController::getOrderByCartId($cart->cart_id);
				
		$oldHash = md5(serialize($order));
		
		\Drupal::moduleHandler()->invokeAll('food_order_postplace', array($order, $cart));
		
		$newHash = md5(serialize($order));
		if($oldHash != $newHash) {
			//An implementing module changed the order, need to re-save it.
			OrderController::updateOrder($order);
		}

        return($order);
	}
    
	private static function updateCartInternal($arr) {
		$arr = self::prepareForUpdation('food_user_cart', $arr);
		
		db_update('food_user_cart')
			->fields($arr)
            ->condition('session_id', $arr['session_id'])
			->execute();
	}
    
	private static function applyCartUpdates(&$arr, $updates = array()) {
		if($updates == NULL) {
			return;
		}
		
		foreach($updates as $key => $value) {
			$arr[$key] = is_object($value) || is_array($value) ? json_encode($value) : $value;
		}
	}
}
