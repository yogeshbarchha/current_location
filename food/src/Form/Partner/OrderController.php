<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;
use Drupal\food\Core\RoleController;
use Drupal\user\Entity\User;

class OrderController extends ControllerBase {

	public function confirmOrder($restaurant_id, $order_id) {
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
		
		if($order->status == \Drupal\food\Core\Order\OrderStatus::Submitted) {
			\Drupal\food\Core\OrderController::confirmOrder($order);
		} else {
			drupal_set_message($this->t('This order has already been processed.'));
		}

		$response = new AjaxResponse();
		//$response->addCommand(new AlertCommand('Order processed successfully...'));
		drupal_set_message($this->t('Order processed successfully...'));
		$response->addCommand(new RedirectCommand(Url::fromRoute('food.partner.report.dashboard')->toString()));
        return ($response);
	}

	public function cancelOrder($restaurant_id, $order_id) {
		$currentUser = User::load(\Drupal::currentUser()->id());
    	$isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
    	$isSubuser = $currentUser->hasRole(RoleController::Subuser_Role_Name);
		$order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
		
		if((string)$order->status === (string)\Drupal\food\Core\Order\OrderStatus::Submitted) {
			if(!empty($order_id) && db_field_exists('food_order','notification') && ($isPartner || $isSubuser)) {
	        	$notification = array('vendor_subuser_cancel_order' => array('admin' => 1,	'owner' => 1));
				db_update('food_order')
					->fields(array('notification' => json_encode($notification)))
					->condition('order_id', $order_id)
					->execute();

				$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
            	$restaurant_owner = \Drupal\user\Entity\User::load($restaurant->owner_user_id);

				$vender_subuser_cancel_notification_body = [
	                'user' => $currentUser->getUsername(),
	                'order_id' => $order->order_id,
	                'restaurant_name' => $restaurant->name,
            	];
				
				$system_site_config = \Drupal::config('system.site');
 				$site_email = $system_site_config->get('mail');
				
				// Send Vendor notification email.
				\Drupal::service('plugin.manager.mail')
				->mail('food', 'vender_subuser_cancel_notification_email', $restaurant_owner->getEmail(),
				$currentUser->getPreferredLangcode(), ['vender_subuser_cancel_notification_body' => $vender_subuser_cancel_notification_body]);
				
				// Send Admin notification email.
				\Drupal::service('plugin.manager.mail')
				->mail('food', 'vender_subuser_cancel_notification_email', $site_email,
				$currentUser->getPreferredLangcode(), ['vender_subuser_cancel_notification_body' => $vender_subuser_cancel_notification_body]);
				drupal_set_message(t('Order Cancel mail has been send successfully.'));
        	}
			\Drupal\food\Core\OrderController::cancelOrder($order);
		} else {
			drupal_set_message($this->t('This order has already been processed.'));
		}

		$response = new AjaxResponse();
		//$response->addCommand(new AlertCommand('Order processed successfully...'));
		drupal_set_message($this->t('Order processed successfully...'));
		$response->addCommand(new RedirectCommand(Url::fromRoute('food.partner.report.dashboard')->toString()));
        return ($response);
	}

}
