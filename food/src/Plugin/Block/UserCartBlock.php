<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;

/**
 * Provides a 'UserCartBlock' Block.
 *
 * @Block(
 *   id = "user_cart_form_block",
 *   admin_label = @Translation("User Cart Block"),
 *   category = @Translation("Food"),
 * )
 */
class UserCartBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$cartRenderOptions = \Drupal\food\Form\Cart\CartController::getCurrentCartRenderOptions();		
		$cart = \Drupal\food\Core\CartController::getCurrentCart();
		$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);
		
		$delivery_mode = PhpHelper::getNestedValue($cart, ['order_details', 'delivery_mode']);
		$items = PhpHelper::getNestedValue($cart, ['order_details', 'items']);
		if($items != NULL) {
			foreach($items as $index => $item) {
				$decrementCartItemUrl = Url::fromRoute('food.cart.item.changequantity', ['restaurant_menu_item_id' => $item->restaurant_menu_item_id, 'index' => $index, 'quantity' => -1]);
				$decrementCartItemUrl->setOptions([
					'attributes' => [
						'class' => ['use-ajax'],
					]
				]);
				$decrementCartItemLink = Link::fromTextAndUrl('-', $decrementCartItemUrl);
				$item->decrementCartItemLink = $decrementCartItemLink->toString();

				$incrementCartItemUrl = Url::fromRoute('food.cart.item.changequantity', ['restaurant_menu_item_id' => $item->restaurant_menu_item_id, 'index' => $index, 'quantity' => 1]);
				$incrementCartItemUrl->setOptions([
					'attributes' => [
						'class' => ['use-ajax'],
					]
				]);
				$incrementCartItemLink = Link::fromTextAndUrl('+', $incrementCartItemUrl);
				$item->incrementCartItemLink = $incrementCartItemLink->toString();

				$deleteCartItemUrl = Url::fromRoute('food.cart.item.delete', ['restaurant_menu_item_id' => $item->restaurant_menu_item_id, 'index' => $index]);
				$deleteCartItemUrl->setOptions([
					'attributes' => [
						'class' => ['use-ajax'],
					]
				]);
				$deleteCartItemLink = Link::fromTextAndUrl('x', $deleteCartItemUrl);
				$item->deleteCartItemLink = $deleteCartItemLink->toString();
			}
							
			$deliveryLinkUrl = Url::fromRoute('food.cart.setdeliverymode', ['delivery_mode' => \Drupal\food\Core\Restaurant\DeliveryMode::Delivery]);
			$deliveryLinkUrl->setOptions([
				'attributes' => [
					'class' => ['use-ajax', 'btn', 'btn-default', $delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? 'btn-success' : ''],
					'role' => 'button',
				]
			]);
			$deliveryLink = Link::fromTextAndUrl('Delivery', $deliveryLinkUrl);
			$cart->deliveryLink = $deliveryLink->toString();
			
			$pickupLinkUrl = Url::fromRoute('food.cart.setdeliverymode', ['delivery_mode' => \Drupal\food\Core\Restaurant\DeliveryMode::Pickup]);
			$pickupLinkUrl->setOptions([
				'attributes' => [
					'class' => ['use-ajax', 'btn', 'btn-default', $delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Pickup ? 'btn-success' : ''],
					'role' => 'button',
				]
			]);
			$pickupLink = Link::fromTextAndUrl('Pickup', $pickupLinkUrl);
			$cart->pickupLink = $pickupLink->toString();
		}
		
		$form = \Drupal::formBuilder()->getForm('Drupal\food\Form\Cart\UserCartForm');		
		$form['#cart'] = $cart;
		$form['#restaurant'] = $restaurant;
		$form['#cartRenderOptions'] = $cartRenderOptions;
		$form['#minimum_order_amount'] = $delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ?
											PhpHelper::getNestedValue($restaurant, ['order_types', 'delivery_settings', 'minimum_order_amount'], 0):
											0;
		
		//A combination of the follwing is the only thing that seems to prevent a page containing cart from being cached server-side.
		\Drupal::service('page_cache_kill_switch')->trigger();
		$form['#cache']['max-age'] = 0;
		$form['#attached']['http_header'][] = ['X-Food-No-Cache', 'TRUE'];
		
		//$form['#theme'] = 'my_awesome_form';
		$form['#attached']['library'][] = 'food/form.user.cartblock';
        $form['#attached']['drupalSettings']['food'] = array(
			'username' => \Drupal::currentUser()->getAccount()->name,
			'currencySymbol' => \Drupal\food\Core\PlatformController::getPlatformSettings()->derived_settings->currency_symbol,
			'cart' => array(
				'restaurant_id' => $cart->restaurant_id,
				'delivery_mode' => PhpHelper::getNestedValue($cart, ['order_details', 'delivery_mode']),
				'platform_discount_pct' => PhpHelper::getNestedValue($cart, ['order_details', 'breakup', 'platform_discount_pct'], 0),
				'restaurant_discount_pct' => PhpHelper::getNestedValue($cart, ['order_details', 'breakup', 'restaurant_discount_pct'], 0),
				'setTipPctUrl' => Url::fromRoute('food.cart.settippct', ['tip_pct' => 10000])->toString(),
				'search_params' => [
					'user_address' => PhpHelper::getNestedValue($cart, ['search_params', 'user_address'], ''),
				],
				'user' => array(
					'username' => \Drupal::currentUser()->getAccount()->getUsername(),
					'displayName' => \Drupal::currentUser()->getAccount()->getDisplayName(),
					'email' => \Drupal::currentUser()->getAccount()->getEmail(),
				),
				'order_details' => [
					'user_phone' => $cart->order_details->user_phone,
					'breakup' => [
						'items_total_amount' => PhpHelper::getNestedValue($cart, ['order_details', 'breakup', 'items_total_amount'], 0),
						'net_amount' => PhpHelper::getNestedValue($cart, ['order_details', 'breakup', 'net_amount'], 0),
					]
				],
				'restaurant' => [
					'restaurant_id' => $restaurant->restaurant_id,
					'name' => $restaurant->name,
					'latitude' => $restaurant->latitude,
					'longitude' => $restaurant->longitude,
					'delivery_radius' => $restaurant->delivery_radius,
					'delivery_area_type' => $restaurant->delivery_area_type,
					'delivery_polygon' => json_encode($restaurant->delivery_polygon),
					'restaurant_url' => Url::fromRoute('entity.food_restaurant.canonical', ['food_restaurant' => $cart->restaurant_id])->toString(),
					'minimum_order_amount' => $form['#minimum_order_amount'],
				]
			),
        );
		
		return ($form);
	}
}
