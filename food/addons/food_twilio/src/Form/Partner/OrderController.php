<?php

namespace Drupal\food_twilio\Form\Partner;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Symfony\Component\HttpFoundation\Request;

class OrderController extends ControllerBase {

  public function call() {
    $basePath = drupal_get_path('module', 'food');
    require $basePath . '/vendor/twilio-php/Twilio/autoload.php';

    $pendingOrders = \Drupal\food\Core\OrderController::findOrders([
      'conditionCallback' => function ($query) {
        $query = $query->condition('fo.status',
          \Drupal\food\Core\Order\OrderStatus::Submitted);

        return ($query);
      },
    ]);

    foreach ($pendingOrders as $order) {
      if (empty($order->meta)) {
        $order->meta = new \Drupal\food\Core\Order\OrderMeta();
      }

      $now = \Imbibe\Util\TimeUtil::now();
      $automated_call_count = PhpHelper::getNestedValue($order,
        ['meta', 'automated_call_count'], 0);
      $last_automated_call_time = PhpHelper::getNestedValue($order,
        ['meta', 'last_automated_call_time'], 0);

      //Send a call atleast after a 2 minute interval.
      if ($automated_call_count < 3 && ($last_automated_call_time == 0 || (($now - $last_automated_call_time) / 1000 > 120))) {

        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
        $phone_number = $restaurant->phone_number;

        $this->createCall($phone_number, $order->order_id);

        $order->meta->automated_call_count = $automated_call_count + 1;
        $order->meta->last_automated_call_time = \Imbibe\Util\TimeUtil::now();

        \Drupal\food\Core\OrderController::updateOrder($order);
      }
    }

    $build = array(
      '#markup' => time(),
    );
    //A combination of the follwing is the only thing that seems to prevent a page containing cart from being cached server-side.
    \Drupal::service('page_cache_kill_switch')->trigger();
    $build['#cache']['max-age'] = 0;
    $build['#attached']['http_header'][] = ['X-Food-No-Cache', 'TRUE'];

    return ($build);
  }

  public function orderCallback($order_id, Request $request) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);

    $orderCallbackUrl = Url::fromRoute('food_twilio.ordercallback',
      ['order_id' => $order_id])->setAbsolute()->toString();
    $orderInstructionsUrl = Url::fromRoute('food_twilio.orderinstructions',
      ['order_id' => $order_id])->setAbsolute()->toString();

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<Response>';
    $xml .= ' <Pause length="3"/>';
    $xml .= '<Gather action="' . $orderInstructionsUrl . '" method="POST">';
    $xml .= '<Say voice="alice" >Order    From    Food  on     Deal     Order       No                            ' . $order_id . ', press 1 to confirm or 9 to cancel</Say>';
    $xml .= '<Pause length="3"/>';
    $xml .= '<Say voice="alice" loop="4">';
    $xml .= 'Order    From    Food  on     Deal     Order       No                               ' . $order_id . ', press 1 to confirm or 9 to cancel .';
    $xml .= '</Say>';
    $xml .= '</Gather >';
    $xml .= '<Say> Sorry, I didn\'t get your response.</Say>';
    $xml .= '<Redirect>' . $orderCallbackUrl . '</Redirect></Response>';
    die($xml);
  }

  public function orderInstructions($order_id, Request $request) {
    $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
    $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);

    $user = \Drupal\user\Entity\User::load($restaurant->owner_user_id);
    \Drupal\food\Core\UserController::setCurrentUser($user);

    $basePath = drupal_get_path('module', 'food');
    require $basePath . '/vendor/twilio-php/Twilio/autoload.php';

    $response = new \Twilio\Twiml();
    if (array_key_exists('Digits', $_POST)) {
      if ($_POST['Digits'] == '1') {
        $response->say('Order Has Been confirmed ' . $order_id . ' . Thanks!');
        \Drupal\food\Core\OrderController::confirmOrder($order);
      }
      elseif ($_POST['Digits'] == '9') {
        $response->say('Order Cancelled by you. thanks!');
        \Drupal\food\Core\OrderController::cancelOrder($order);
      }
      else {
        $response->say('Sorry, I don\'t understand that choice.');
      }
    }
    else {
      // If no input was sent, use the <Gather> verb to collect user input
      $gather = $response->gather(array('numDigits' => 1));
      // use the <Say> verb to request input from the user
      $gather->say('For confirm this order, press 1. For cancel, press 9.');

      // If the user doesn't enter input, loop
      $response->redirect('/voice');
    }

    // Render the response as XML in reply to the webhook request
    header('Content-Type: text/xml');
    echo $response;

    die();
  }

  private function createCall($phone_number, $order_id) {
    $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();
    $sid = $platform_settings->twilio_settings->sid;
    $token = $platform_settings->twilio_settings->token;

    $orderCallbackUrl = Url::fromRoute('food_twilio.ordercallback',
      ['order_id' => $order_id])->setAbsolute()->toString();

    $client = new \Twilio\Rest\Client($sid, $token);
    $call = $client->calls->create("+" . $platform_settings->country_calling_code . $phone_number,
      "+" . $platform_settings->country_calling_code . $platform_settings->twilio_settings->from_number,
      array(
        "url" => $orderCallbackUrl,
      ));
  }

}
