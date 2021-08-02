<?php

namespace Drupal\food\Core\Menu;

class ItemVariationCategory {
	
	/**
     * @var string
     */
	public $name;

	/**
     * @var string
     */
	public $display_type;

	/**
     * @var boolean
     */
	public $required;

	/**
     * @var \Drupal\food\Core\Menu\ItemVariationCategoryOption[]
     */
	public $options;
	
}
