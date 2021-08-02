<?php

namespace Drupal\food\Service;

use Drupal\block\BlockViewBuilder;

class FoodTwigExtension extends \Twig_Extension {

  /**
   * Generates a list of all Twig functions that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig functions. The key denotes the
   *   function name used in the tag, e.g.:
   *   @code
   *   {{ food_platform_settings() }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the function does.
   */
  public function getFunctions() {
    return array(
      'food_platform_settings' => new \Twig_Function_Function(array('Drupal\food\Service\FoodTwigExtension', 'food_platform_settings')),
      'food_get_drupal_menu_items_by_menu' => new \Twig_Function_Function(array('Drupal\food\Service\FoodTwigExtension', 'food_get_drupal_menu_items_by_menu')),
      'food_get_block_by_name' => new \Twig_Function_Function(array('Drupal\food\Service\FoodTwigExtension', 'food_get_block_by_name')),
      'food_get_menu_item_index' => new \Twig_Function_Function(array('Drupal\food\Service\FoodTwigExtension', 'food_get_menu_item_index')),
    );
  }

  /**
   * Generates a list of all Twig filters that this extension defines.
   *
   * @return array
   *   A key/value array that defines custom Twig filters. The key denotes the
   *   filter name used in the tag, e.g.:
   *   @code
   *   {{ foo|testfilter }}
   *   @endcode
   *
   *   The value is a standard PHP callback that defines what the filter does.
   */
  public function getFilters() {
    return array(
    );
  }

  /**
   * Gets a unique identifier for this Twig extension.
   *
   * @return string
   *   A unique identifier for this Twig extension.
   */
  public function getName() {
    return 'food.twig_extension';
  }

  public static function food_platform_settings() {
    return (\Drupal\food\Core\PlatformController::getPlatformSettings());
  }

  public static function food_get_drupal_menu_items_by_menu($menu_name) {
    return (\Drupal\food\Util::getDrupalMenuItemsByMenu($menu_name));
  }

  public static function food_get_block_by_name($block_name) {
    $block = \Drupal::service('plugin.manager.block')->createInstance($block_name, []);
    return  $block->build();
  }

  public static function food_get_menu_item_index($menu_item_id, $menu_items){
    if(is_object($menu_items)){
      $menu_items = (array) $menu_items;
    }
    foreach ($menu_items as &$menu_item) {
      $menu_item = (array) $menu_item;
    }

    return array_search($menu_item_id, array_column($menu_items, 'restaurant_menu_item_id'));
  }

}
