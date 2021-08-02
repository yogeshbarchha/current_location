<?php

namespace Drupal\food\Core\Restaurant;

class PaymentSettings {
	
	/**
     * @var int
     */
	public $payment_mode;
	
	/**
     * @var \Drupal\food\Core\Restaurant\CashOnDeliveryPaymentSettings
     */
	public $cash_on_delivery_settings;

	/**
     * @var \Drupal\food\Core\Restaurant\CardPaymentSettings
     */
	public $card_payment_settings;
	
	/**
     * @var \Drupal\food\Core\Restaurant\WireTransferPaymentSettings
     */
	public $wire_transfer_payment_settings;

	/**
     * @var \Drupal\food\Core\Restaurant\ChequePaymentSettings
     */
	public $cheque_payment_settings;
	
}
