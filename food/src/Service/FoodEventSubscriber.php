<?php

namespace Drupal\food\Service;

use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FoodEventSubscriber implements EventSubscriberInterface {

  public function subscribeAutoloader(GetResponseEvent $event) {
	spl_autoload_register(array($this, 'autoLoadFoodVendorClasses'));
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
	//https://drupal.stackexchange.com/questions/201273/problem-with-event-subscriber-kerneleventsrequest-is-not-fired-on-cached-pag
	//https://api.drupal.org/api/drupal/vendor%21symfony%21http-kernel%21KernelEvents.php/constant/KernelEvents%3A%3ACONTROLLER/8.2.x
	//https://api.drupal.org/api/drupal/vendor%21symfony%21http-kernel%21KernelEvents.php/class/KernelEvents/8.2.x
    $events[KernelEvents::REQUEST][] = array('subscribeAutoloader', 30);
    $events[KernelEvents::RESPONSE][] = array('onRespond');
    return $events;
  }
  
  private function autoLoadFoodVendorClasses($class) {
	if(strpos($class, 'Imbibe') === 0) {
		$basePath = drupal_get_path('module', 'food');
		require $basePath . '/vendor/' . (str_replace('\\', '/', $class)) . '.php';
	} else if (strpos($class, 'Stripe') === 0) {
		$basePath = drupal_get_path('module', 'food');
		require $basePath . '/vendor/' . (str_replace('\\', '/', $class)) . '.php';
	} else if(strpos($class, 'JsonMapper') === 0) {
		$basePath = drupal_get_path('module', 'food');
		require $basePath . '/vendor/JsonMapper/src/JsonMapper.php';
		require $basePath . '/vendor/JsonMapper/src/JsonMapper/Exception.php';
	}
  }

  public function onRespond(FilterResponseEvent $event) {
    // The RESPONSE event occurs once a response was created for replying to a request.
    // For example you could override or add extra HTTP headers in here
    $response = $event->getResponse();
	if($response->headers->get('X-Food-No-Cache') === 'TRUE') {
		$response->headers->set('Cache-Control', 'no-cache, no-store, must-revalidate');
		$response->headers->set('Pragma', 'no-cache');
	}
  }
}
