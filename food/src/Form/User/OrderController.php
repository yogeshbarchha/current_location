<?php

namespace Drupal\food\Form\User;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

class OrderController extends ControllerBase {
	
	public function confirmOrder($restaurant_id, $order_id) {
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
		
		if($order->status == \Drupal\food\Core\Order\OrderStatus::Submitted) {
			\Drupal\food\Core\OrderController::confirmOrder($order);
			drupal_set_message($this->t('Order confirmed successfully.'));
		} else {
			drupal_set_message($this->t('This order has already been processed.'));
		}

		$url = Url::fromRoute('food.cart.order.confirmation', ['order_id' => $order_id]);
		return new RedirectResponse($url->toString());
	}

	public function cancelOrder($restaurant_id, $order_id) {
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
        
        if(!empty($order_id) && db_field_exists('food_order','notification')){
        	$notification = array('customer_cancel_order' => array('admin' => 1,	'owner' => 1));
			db_update('food_order')
				->fields(array('notification' => json_encode($notification)))
				->condition('order_id', $order_id)
				->execute();        	
        }
		
		if($order->status == \Drupal\food\Core\Order\OrderStatus::Submitted) {
			\Drupal\food\Core\OrderController::cancelOrder($order);
			drupal_set_message($this->t('Order cancelled successfully.'));
		} else {
			drupal_set_message($this->t('This order has already been processed.'));
		}

		$url = Url::fromRoute('food.cart.order.confirmation', ['order_id' => $order_id]);
		return new RedirectResponse($url->toString());
	}

}
