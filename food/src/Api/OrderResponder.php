<?php

namespace Drupal\food\Api;

abstract class OrderResponder extends ApiResponderBase {

    public static function breakupOrder() {
		$order_details = ApiResponderBase::getPostVariable('order_details');
		
		$order = \Imbibe\Json\JsonHelper::deserializeObject($order_details, '\Drupal\food\Core\Order\Order');
		$order->updateBreakup(['updateItemTotals' => TRUE]);

		$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
		\Drupal\food\Core\RestaurantController::applyRestaurntDealToOrder($restaurant, $order);
		$order->updateBreakup();

		return(array('success' => true, 'data' => $order));
    }

    public static function placeOrder() {
        parent::initializeAuthenticatedRequest();

		$order_details = ApiResponderBase::getPostVariable('order_details');
		$order = \Imbibe\Json\JsonHelper::deserializeObject($order_details, '\Drupal\food\Core\Order\Order');
		
		$cart = \Drupal\food\Core\CartController::createCartFromOrderDetails($order);
		$order = \Drupal\food\Core\CartController::createOrder($cart);

		return(array('success' => true, 'data' => $order));
    }
    
    public static function getUserOrders() {
        parent::initializeAuthenticatedRequest();
        
        $user_id = \Drupal::currentUser()->id();
        $orders = \Drupal\food\Core\OrderController::getOrdersByUserId($user_id);
        \Drupal\food\Core\OrderController::assignEntityRestaurants($orders);
		
        return(array('success' => true, 'data' => $orders));
    }

}
