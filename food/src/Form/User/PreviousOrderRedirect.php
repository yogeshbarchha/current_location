<?php 

namespace Drupal\food\Form\User;

use Drupal\food\Core\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Url;
use Drupal\Core\Render\Markup;

/**
* 
*/
class PreviousOrderRedirect extends ControllerBase {
	
	public function index() {
		$order_id = \Drupal::routeMatch()->getParameter('order_id');
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
		$restaurant_menu_item_id = \Drupal::routeMatch()->getParameter('restaurant_menu_item_id');
    	$restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);
    	$order_menu_data = array();
dpr($order); dpr($restaurant_menu_item);
		if(!empty($order) && !empty($restaurant_menu_item)) {

			\Drupal\food\Core\CartController::unlinkCurrentCart();

			foreach ($order->order_details->items as $item) {

					if($item->restaurant_menu_item_id == $restaurant_menu_item_id) {

					$restaurant_menu_item_id = $item->restaurant_menu_item_id;
					$restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);
					$cart = \Drupal\food\Core\CartController::getCurrentCart();

					if (empty($cart->order_details)) {
						$cart->order_details = new \Drupal\food\Core\Order\Order();
					}

					$cart->order_details->restaurant_id = $cart->restaurant_id;

					if (empty($cart->order_details->items)) {
						$cart->order_details->items = array();
					}

					$currentItem = new \Drupal\food\Core\Order\OrderItem();
					$currentItem->restaurant_menu_item_id = $restaurant_menu_item_id;
					$currentItem->quantity = intval($item->quantity);

					if(!empty($item->size)){
						foreach ($item->size as $size) {
							$sizeIndex = $size->id;            
						}
					}else{
						$sizeIndex = NULL;
					}

					if ($sizeIndex != NULL) {
						$currentItem->size = new \Drupal\food\Core\Order\OrderItemSize();
						$currentItem->size->id = $sizeIndex;
					}

					$currentItem->options = [];

					if(!empty($item->options)){
						foreach ($item->options as $options_key => $options) {
							if (strpos($options_key, 'category') === 0) {
								if ($options->id == \Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE) {
									continue;
								}
							$option = new \Drupal\food\Core\Order\OrderItemOption();
							$option->category_id = $options->category_id;
							$option->id = $options->id;
							$currentItem->options[] = $option;
							}
						}
					}

					$currentItem->updateItemTotals();
					$currentItem->instructions = $item->instructions;

					$existingItem = NULL;

					foreach ($cart->order_details->items as $tempItem) {
						$propertiesToIgnore = [
						'Drupal\food\Core\Order\OrderItemOption::index',
						'Drupal\food\Core\Order\OrderItem::quantity',
						'Drupal\food\Core\Order\OrderItem::item_total_amount',
						];
						if (PhpHelper::compareDeep($currentItem, $tempItem,	['propertiesToIgnore' => $propertiesToIgnore])) {
							$existingItem = $tempItem;
						break;
						}
					}

					if ($existingItem != NULL) {
						$existingItem->quantity += $currentItem->quantity;
						$existingItem->item_total_amount += $currentItem->item_total_amount;
					}else {
						$cart->order_details->items[] = $currentItem;
					}

					\Drupal\food\Core\CartController::updateCart($cart);
					break;
				}
			}

			$url = Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $order->restaurant_id])->toString();
			return new RedirectResponse($url);
		}

	}

	public function OrderRedirect() {
		$order_id = \Drupal::routeMatch()->getParameter('order_id');
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
    	$order_menu_data = array();

		if(!empty($order)) {

			\Drupal\food\Core\CartController::unlinkCurrentCart();

			foreach ($order->order_details->items as $item) {

					$restaurant_menu_item_id = $item->restaurant_menu_item_id;
					$restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);
					$cart = \Drupal\food\Core\CartController::getCurrentCart();

					if (empty($cart->order_details)) {
						$cart->order_details = new \Drupal\food\Core\Order\Order();
					}

					$cart->order_details->restaurant_id = $cart->restaurant_id;

					if (empty($cart->order_details->items)) {
						$cart->order_details->items = array();
					}

					$currentItem = new \Drupal\food\Core\Order\OrderItem();
					$currentItem->restaurant_menu_item_id = $restaurant_menu_item_id;
					$currentItem->quantity = intval($item->quantity);

					if(!empty($item->size)){
						foreach ($item->size as $size) {
							$sizeIndex = $size->id;            
						}
					}else{
						$sizeIndex = NULL;
					}

					if ($sizeIndex != NULL) {
						$currentItem->size = new \Drupal\food\Core\Order\OrderItemSize();
						$currentItem->size->id = $sizeIndex;
					}

					$currentItem->options = [];

					if(!empty($item->options)){
						foreach ($item->options as $options_key => $options) {
							if (strpos($options_key, 'category') === 0) {
								if ($options->id == \Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE) {
									continue;
								}
							$option = new \Drupal\food\Core\Order\OrderItemOption();
							$option->category_id = $options->category_id;
							$option->id = $options->id;
							$currentItem->options[] = $option;
							}
						}
					}

					$currentItem->updateItemTotals();
					$currentItem->instructions = $item->instructions;

					$existingItem = NULL;

					foreach ($cart->order_details->items as $tempItem) {
						$propertiesToIgnore = [
						'Drupal\food\Core\Order\OrderItemOption::index',
						'Drupal\food\Core\Order\OrderItem::quantity',
						'Drupal\food\Core\Order\OrderItem::item_total_amount',
						];
						if (PhpHelper::compareDeep($currentItem, $tempItem,	['propertiesToIgnore' => $propertiesToIgnore])) {
							$existingItem = $tempItem;
						break;
						}
					}

					if ($existingItem != NULL) {
						$existingItem->quantity += $currentItem->quantity;
						$existingItem->item_total_amount += $currentItem->item_total_amount;
					}else {
						$cart->order_details->items[] = $currentItem;
					}

					\Drupal\food\Core\CartController::updateCart($cart);
			}

			$url = Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $order->restaurant_id])->toString();
			return new RedirectResponse($url);
		}
	}
}
