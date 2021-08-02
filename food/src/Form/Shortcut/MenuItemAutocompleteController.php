<?php

namespace Drupal\food\Form\Shortcut;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\Unicode;

/**
 * Defines a route controller for entity autocomplete form elements.
 */
class MenuItemAutocompleteController extends ControllerBase {

  /**
   * Handler for autocomplete request.
   */
  public function handleAutocomplete(Request $request, $restaurant, $count) {
    $results = [];

    // Get the typed string from the URL, if it exists.
    if ($input = $request->query->get('q')) {
      $menu_items = \Drupal\food\Core\MenuController::getAllRestaurantMenuItem([
          'pageSize' => 0,
          'conditionCallback' => function($query) use (&$request, &$input, &$restaurant, &$count) {
              $query->condition('fm.restaurant_id', $restaurant);
              $query->condition('fm.name', '%' . db_like($input) . '%', 'LIKE');
              $query->orderBy('fm.name');
              $query->range(0, $count);   
              return($query);
          }
      ]);
      // @todo: Apply logic for generating results based on typed_string and other
      // arguments passed.
      if(!empty($menu_items)){
        foreach ($menu_items as $key => $value) {
         $results[] = ['value' => $value->name, 'label' => $value->name];
        }        
      }
    }

    return new JsonResponse($results);
  }

}