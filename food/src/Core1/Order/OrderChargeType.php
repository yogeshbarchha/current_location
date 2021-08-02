<?php

namespace Drupal\food\Core\Order;

class OrderChargeType extends \Imbibe\Language\EnumBase {
	
	const Chargeback = 1;
	const Adjustment = 2;
	
}
