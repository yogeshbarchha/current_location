<?php

namespace Drupal\food\Core\Restaurant;

class PaymentMode extends \Imbibe\Language\EnumBase {
	
	const CashOnDelivery = 1;
	const Card = 2;
	const WireTransfer = 3;
	const Cheque = 4;
	
}
