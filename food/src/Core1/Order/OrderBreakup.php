<?php

namespace Drupal\food\Core\Order;

class OrderBreakup {
	
	/**
     * @var double
     */
	public $items_total_amount;	
	

	/**
     * @var double
     */
	public $restaurant_discount_pct;

	/**
     * @var double
     */
	public $effective_restaurant_discount_pct;

	/**
     * @var double
     */
	public $restaurant_discount_amount;

	/**
     * @var double
     */
	public $platform_discount_pct;

	/**
     * @var double
     */
	public $effective_platform_discount_pct;

	/**
     * @var double
     */
	public $platform_discount_amount;

	/**
     * @var double
     */
	public $total_discount_pct;

	/**
     * @var double
     */
	public $total_discount_amount;

	
	/**
     * @var double
     */
	public $delivery_charges_amount;

	
	/**
     * @var double
     */
	public $tip_pct;

	/**
     * @var double
     */
	public $tip_amount;

	
	/**
     * @var double
     */
	public $tax_pct;

	/**
     * @var double
     */
	public $tax_amount;
	
	
	/**
     * @var double
     */
	public $platform_commission_pct;
	
	/**
     * @var double
     */
	public $platform_commission_amount;
	
	
	/**
     * @var string
     */
	public $payment_mode_processing_fee_formula;
	
	/**
     * @var double
     */
	public $payment_mode_processing_fee_amount;
	
	
	/**
     * @var double
     */
	public $restaurant_amount;
	
	/**
     * @var double
     */
	public $net_platform_amount;
	
	/**
     * @var double
     */
	public $net_restaurant_amount;	

	
	/**
     * @var double
     */
	public $net_amount_due_to_restaurant;	
	
	/**
     * @var double
     */
	public $net_amount_due_to_platform;	
	
	/**
     * @var double
     */
	public $net_amount_due;	
	
	/**
     * @var double
     */
	public $net_amount;	
	
}
