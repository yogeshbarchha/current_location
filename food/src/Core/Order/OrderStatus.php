<?php

namespace Drupal\food\Core\Order;

class OrderStatus extends \Imbibe\Language\EnumBase {
	
	const Submitted = 0;
	const Confirmed = 10;
	const Cancelled = 100;
	
}
