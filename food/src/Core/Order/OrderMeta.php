<?php

namespace Drupal\food\Core\Order;

use Imbibe\Util\PhpHelper;

class OrderMeta {

	/**
     * @var int
     */
	public $automated_call_count;

	/**
     * @var int
     */
	public $last_automated_call_time;

	/**
     * @var string
     */
	public $user_short_url;

	/**
     * @var string
     */
	public $partner_short_url;
	
}
