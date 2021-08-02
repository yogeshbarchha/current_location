<?php

namespace Drupal\food\Core\Location;

class Address {
	
	/**
     * @var int
     */
	public $address_id;
	
	/**
     * @var int
     */
	public $owner_user_id;
	
	/**
     * @var int
     */
	public $type;
	
	/**
     * @var string
     */
	public $contact_name;
	
	/**
     * @var string
     */
	public $phone_number;
	
	/**
     * @var string
     */
	public $fax_number;
	
	/**
     * @var string
     */
	public $email;
	
	/**
     * @var string
     */
	public $address_line1;
	
	/**
     * @var string
     */
	public $address_line2;
	
	/**
     * @var string
     */
	public $city;
	
	/**
     * @var string
     */
	public $state;
	
	/**
     * @var string
     */
	public $postal_code;
	
	/**
     * @var string
     */
	public $country;
	
	/**
     * @var double
     */
	public $latitude;
	
	/**
     * @var double
     */
	public $longitude;
	
}
