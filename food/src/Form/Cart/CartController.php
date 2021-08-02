<?php

namespace Drupal\food\Form\Cart;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Component\Serialization\Json;

class CartController extends ControllerBase {

  public function registerDirectRestaurantSearch() {
    $restaurant_id = $_GET['restaurant_id'];
    $cart = \Drupal\food\Core\CartController::getCurrentCart(['search_mode' => \Drupal\food\Core\Cart\SearchMode::Restaurant]);

    if ($cart->restaurant_id != $restaurant_id) {
      $cart->restaurant_id = $restaurant_id;

      $cart->order_details = new \Drupal\food\Core\Order\Order();
      $cart->order_details->restaurant_id = $restaurant_id;

      \Drupal\food\Core\CartController::updateCart($cart);
    }

    $response = new AjaxResponse();

    return ($response);
  }

  public function switchCartRestaurant($restaurant_id) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    $cart->restaurant_id = $restaurant_id;
    if (empty($cart->order_details)) {
      $cart->order_details = new \Drupal\food\Core\Order\Order();
    }
    $cart->order_details->restaurant_id = $restaurant_id;
    $cart->order_details->items = NULL;
    $cart->order_details->breakup = NULL;

    \Drupal\food\Core\CartController::updateCart($cart);

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]));
  }

  public function changeItemQuantity($restaurant_menu_item_id,
    $index,
    $quantity) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    foreach ($cart->order_details->items as $curIndex => $item) {
      if ($curIndex == $index && $item->restaurant_menu_item_id = $restaurant_menu_item_id) {
        $item->quantity += intval($quantity);
        if ($item->quantity <= 0) {
          array_splice($cart->order_details->items, $index, 1);
        }
        else {
          $item->item_total_amount = $item->quantity * $item->unit_price;
        }

        break;
      }
    }

    \Drupal\food\Core\CartController::updateCart($cart);

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]));
  }

  public function deleteCartItem($restaurant_menu_item_id, $index) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    foreach ($cart->order_details->items as $curIndex => $item) {
      if ($curIndex == $index && $item->restaurant_menu_item_id = $restaurant_menu_item_id) {
        array_splice($cart->order_details->items, $index, 1);
        break;
      }
    }

    \Drupal\food\Core\CartController::updateCart($cart);

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]));
  }

  public function setOrderDeliveryMode($delivery_mode) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    $cart->order_details->delivery_mode = $delivery_mode;

    \Drupal\food\Core\CartController::updateCart($cart);

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]));
  }

  public function roulette() {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

    $cart = \Drupal\food\Core\CartController::getCurrentCart();
    $cart->user_id = \Drupal::currentUser()->id();

    //Apply restaurant deal discount.
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);
    \Drupal\food\Core\RestaurantController::applyRestaurntDealToOrder($restaurant,
      $cart->order_details);
    \Drupal\food\Core\CartController::updateCart($cart);

    $rouletteDiscounts = \Drupal\food\Core\RestaurantController::getRestaurantRouletteDiscounts($cart->restaurant_id);
    if (empty($rouletteDiscounts) || PhpHelper::getNestedValue($platform_settings,
        ['order_settings', 'disable_platform_deals'],
        FALSE) == TRUE || PhpHelper::getNestedValue($restaurant,
        ['platform_settings', 'disable_platform_deals'], FALSE) == TRUE) {
      $url = Url::fromRoute('food.cart.deliveryoptions');
      return new RedirectResponse($url->toString());
    }

    $deliveryOptionsUrl = Url::fromRoute('food.cart.deliveryoptions');
    $deliveryOptionsUrl->setOptions([
      'attributes' => [
        'class' => ['btn', 'btn-default', 'btn-primary'],
        'role' => 'button',
      ],
    ]);
    $deliveryOptionsLink = Link::fromTextAndUrl('Checkout',
      $deliveryOptionsUrl);

    $restaurantMenuUrl = Url::fromRoute('entity.food_restaurant.canonical',
      ['food_restaurant' => $cart->restaurant_id]);
    $restaurantMenuUrl->setOptions([
      'attributes' => [
        'class' => ['btn', 'btn-default', 'modify-button'],
        'role' => 'button',
      ],
    ]);
    $restaurantMenuLink = Link::fromTextAndUrl('Modify Cart',
      $restaurantMenuUrl);

    $user_cart_block_html = \Drupal\food\Form\Cart\CartController::getCurrentCartHtml([
      'render_mode' => \Drupal\food\Core\Cart\CartRenderMode::ReadOnly,
      'hide_discount' => empty($cart->order_details->breakup->platform_discount_pct),
    ]);

    $build = array(
      '#markup' => '',
      '#theme' => 'food_user_cart_roulette',
      'user_cart_block_html' => $user_cart_block_html,
      'additionalData' => [
        'deliveryOptionsLink' => $deliveryOptionsLink->toString(),
        'restaurantMenuLink' => $restaurantMenuLink->toString(),
        'restaurant' => $restaurant,
        'currentUser' => \Drupal::currentUser(),
      ],
      '#attached' => [
        'library' => ['food/form.cart.roulette'],
        'drupalSettings' => [
          'food' => [
            'deals' => $deals,
            'rouletteDiscounts' => $rouletteDiscounts,
            'setPlatformDiscountUrl' => Url::fromRoute('food.cart.item.setplatformdiscount',
              ['discount' => 10000])->toString(),
          ],
        ],
      ],
    );

    return ($build);
  }

  public function setPlatformDiscount($discount) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    $existingPlatformDiscount = $cart->order_details->breakup->platform_discount_pct;
    if (empty($existingPlatformDiscount) || $existingPlatformDiscount == 0) {
      $cart->order_details->breakup->platform_discount_pct = $discount;
      \Drupal\food\Core\CartController::updateCart($cart);
    }

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::ReadOnly]));
  }

  public function deliveryOptions() {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();
    $cart->user_id = \Drupal::currentUser()->id();

    //Apply restaurant deal discount. This is needed as roulette page can be skipped for a variety of reasons.
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($cart->restaurant_id);
    \Drupal\food\Core\RestaurantController::applyRestaurntDealToOrder($restaurant,
      $cart->order_details);
    \Drupal\food\Core\CartController::updateCart($cart);

    $placeOrderUrl = Url::fromRoute('food.cart.placeorder');
    $placeOrderUrl->setOptions([
      'attributes' => [
        'class' => ['btn', 'btn-default', 'btn-primary'],
        'role' => 'button',
      ],
    ]);
    $placeOrderLink = Link::fromTextAndUrl('Checkout', $placeOrderUrl);

    $restaurantMenuUrl = Url::fromRoute('entity.food_restaurant.canonical',
      ['food_restaurant' => $cart->restaurant_id]);
    $restaurantMenuUrl->setOptions([
      'attributes' => [
        'class' => ['btn', 'btn-default', 'modify-button'],
        'role' => 'button',
      ],
    ]);
    $restaurantMenuLink = Link::fromTextAndUrl('Modify Cart',
      $restaurantMenuUrl);

    $form = \Drupal::formBuilder()
      ->getForm('Drupal\food\Form\Cart\DeliveryOptionsForm');
    $user_cart_block_html = \Drupal\food\Form\Cart\CartController::getCurrentCartHtml(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::ReadOnly]);
    $user_addresses = \Drupal\food\Core\AddressController::getUserAddresses($cart->user_id);


    $user_last_order = \Drupal\food\Core\OrderController::findOrders([
       'pageSize' => 1,
       'conditionCallback' => function($query) use (&$cart) {

          $query = $query->condition('user_id', \Drupal::currentUser()->id());

          if(!empty($cart) && $cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery){
            $query = $query->condition('delivery_mode', \Drupal\food\Core\Restaurant\DeliveryMode::Delivery);
          }

          if(!empty($cart) && $cart->order_details->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Pickup){
            $query = $query->condition('delivery_mode', \Drupal\food\Core\Restaurant\DeliveryMode::Pickup);
          }

          $query = $query->orderBy('order_id','DESC');

          return($query);
        }

      ]);

    $add_url = Url::fromRoute('food.user.address.add', ['user' => \Drupal::currentUser()->id()]);
    $add_url->setOptions([
                'query' => ['destination' => $_SERVER['REQUEST_URI']],
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
    $add_link = Link::fromTextAndUrl(t('Add Address'), $add_url);
    
    foreach ($user_addresses as $key => &$user_address) {
      $edit_url = Url::fromRoute('food.user.address.edit', ['user' => \Drupal::currentUser()->id(), 'address_id' => $user_address->address_id]);
      $edit_url->setOptions([
                'query' => ['destination' => $_SERVER['REQUEST_URI']],
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);

      $edit_link = Link::fromTextAndUrl(t('<i class="glyphicon glyphicon-edit"></i>'), $edit_url);
      
      $user_address->edit_link = $edit_link->toString();

      $delete_url = Url::fromRoute('food.user.address.delete', ['user' => \Drupal::currentUser()->id(), 'address_id' => $user_address->address_id]);
      $delete_url->setOptions([
                'query' => ['destination' => $_SERVER['REQUEST_URI']],
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);

      $delete_link = Link::fromTextAndUrl(t('<i class="glyphicon glyphicon-remove-circle"></i>'), $delete_url);
      
      $user_address->delete_link = $delete_link->toString();

      if (!empty($user_last_order) && isset($user_last_order[0]->order_details->user_address)) {
        if($user_last_order[0]->order_details->user_address_id == $user_address->address_id){
          $temp = array($key => $user_addresses[$key]);
          unset($user_addresses[$key]);
          $user_addresses = $temp + $user_addresses;
        }        
      }
    }

    $build = array(
      '#markup' => '',
      'user_addresses' => $user_addresses,
      'form' => $form,
      '#theme' => 'food_user_cart_delivery_options',
      'user_cart_block_html' => $user_cart_block_html,
      'additionalData' => [
        'cart' => $cart,
        'placeOrderLink' => $placeOrderLink->toString(),
        'restaurantMenuLink' => $restaurantMenuLink->toString(),
        'addAddressLink' => $add_link->toString(),
      ],
      '#attached' => [
        'library' => ['food/form.cart.deliveryoptions'],
        'drupalSettings' => [
          'food' => [
            'user_addresses' => $user_addresses,
            'address_count' => count($user_addresses),
            'restaurantMenuUrl' => $restaurantMenuUrl->toString(),
          ],
        ],
      ],
    );

    return ($build);
  }

  public function placeOrder() {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();
 if($cart->restaurant_id!=""){
    $form = \Drupal::formBuilder()
      ->getForm('Drupal\food\Form\Cart\OrderPlacementForm');

    $restaurantMenuUrl = Url::fromRoute('entity.food_restaurant.canonical',
      ['food_restaurant' => $cart->restaurant_id]);
    $restaurantMenuUrl->setOptions([
      'attributes' => [
        'class' => ['btn', 'btn-default', 'modify-button'],
        'role' => 'button',
      ],
    ]);
    $restaurantMenuLink = Link::fromTextAndUrl('Modify Cart',
      $restaurantMenuUrl);

    $user_cart_block_html = \Drupal\food\Form\Cart\CartController::getCurrentCartHtml(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::ReadOnly]);

    $build = array(
      '#markup' => '',
      'form' => $form,
      '#theme' => 'food_user_cart_place_order',
      'user_cart_block_html' => $user_cart_block_html,
      'additionalData' => [
        'restaurantMenuLink' => $restaurantMenuLink->toString(),
      ],
      '#attached' => [
        'library' => ['food/form.cart.placeorder'],
        'drupalSettings' => [
          'food' => [],
        ],
      ],
    );

    return ($build);
    }else {
    $url = Url::fromRoute('<front>');
     $response = new RedirectResponse($url->toString());
     $response->send();

  }
  }

  public function setTipPct($tip_pct) {
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    $cart->order_details->breakup->tip_pct = $tip_pct;

    \Drupal\food\Core\CartController::updateCart($cart);

    return (self::getCartUpdationResponse(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::ReadOnly]));
  }

  public static function getCurrentCartRenderOptions() {
    $options = &drupal_static('Food\User\CartController::CurrentCartRenderOptions',
      new \Drupal\food\Core\Cart\CartRenderOptions());
    return ($options);
  }

  public static function setCurrentCartRenderOptions($newOptions) {
    drupal_static_reset('Food\User\CartController::CurrentCartRenderOptions');
    $options = &drupal_static('Food\User\CartController::CurrentCartRenderOptions');

    if (empty($options)) {
      $options = new \Drupal\food\Core\Cart\CartRenderOptions();
    }

    foreach ($newOptions as $key => $value) {
      $options->$key = $value;
    }
  }

  public static function getCurrentCartHtml($newOptions) {
    self::setCurrentCartRenderOptions($newOptions);

    $block = \Drupal\block\Entity\Block::load('usercartblock');
    $user_cart_block_html = \Drupal::entityTypeManager()
      ->getViewBuilder('block')
      ->view($block);

    return ($user_cart_block_html);
  }

  public static function getCartUpdationResponse($newOptions) {
    $user_cart_block_html = self::getCurrentCartHtml($newOptions);
    $response = new AjaxResponse();
    $response->addCommand(new HtmlCommand('.user-cart-container',
      $user_cart_block_html));
    return ($response);
  }
}
