<?php

namespace Drupal\food\Service;

use Drupal\Core\EventSubscriber\HttpExceptionSubscriberBase;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

class FoodHttpExceptionSubscriber extends HttpExceptionSubscriberBase {

	protected $currentUser;

	public function __construct(AccountInterface $current_user) {
		$this->currentUser = $current_user;
	}

	protected function getHandledFormats() {
		return ['html'];
	}

	public function on403(GetResponseForExceptionEvent $event) {
		$request = $event->getRequest();
		
		$is_anonymous = $this->currentUser->isAnonymous();
		$route_name = $request->attributes->get('_route');
		$is_not_login = $route_name != 'user.login';
		
		if ($is_anonymous && $is_not_login) {
			$query = $request->query->all();
			$query['destination'] = Url::fromRoute('<current>')->toString();
			$login_uri = Url::fromRoute('user.login', [], ['query' => $query]);
			$returnResponse = new RedirectResponse($login_uri->toString());
			$event->setResponse($returnResponse);
		}
	}

}
