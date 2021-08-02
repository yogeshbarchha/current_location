<?php

namespace Drupal\food\Form\Partner;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class CuisineList extends ControllerBase {

  public function show() {
    $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

    $build = array(//'#markup' => 'My restaurants',
    );

    $build['container'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#title' => 'Add Cuisine',
        '#url' => Url::fromRoute('food.partner.restaurant.cuisine.add',
          array('restaurant_id' => $restaurant_id)),
        '#attributes' => [
          'class' => ['use-ajax', 'restaurant-admin-button'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ),
      'back_button_restaurant' => array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#title' => '  Back to Restaurant list',
        '#url' => Url::fromRoute('entity.food_restaurant.collection'),
      ),
    );

    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('')),
      array('data' => $this->t('')),
    );

    $rows = \Drupal\food\Core\CuisineController::getRestaurantCuisines($restaurant_id,
      ['header' => $header]);
    foreach ($rows as &$row) {
      $deleteCuisineUrl = Url::fromRoute('food.partner.restaurant.cuisine.delete',
        [
          'restaurant_id' => $restaurant_id,
          'cuisine_id' => $row->cuisine_id,
          'restaurant_cuisine_id' => $row->restaurant_cuisine_id,
        ]);
      $deleteCuisineUrl->setOptions([
        'attributes' => [
          'class' => ['delete-button'],
        ],
      ]);
      $deleteCuisineLink = Link::fromTextAndUrl(t('Delete Cuisine'),
        $deleteCuisineUrl);

      $row = array(
        'data' => array(
          'name' => $row->name,
          'delete_cuisine_link' => $deleteCuisineLink->toString(),
        ),
      );
    }

    //Generate the table.
    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#attributes' => array(
        'class' => 'food-entity-list-table',
      ),
    );

    //Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager',
    );

    $build['#attached']['library'][] = 'food/common';

    return ($build);
  }

  public function delete($restaurant_id, $cuisine_id) {
    db_delete('food_restaurant_cuisine')
      ->condition('restaurant_id', $restaurant_id)
      ->condition('cuisine_id', $cuisine_id)
      ->execute();

    drupal_set_message(t('Restaurant cuisine deleted successfully.'));

    \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);

    $manageCuisineUrl = Url::fromRoute('food.partner.restaurant.cuisine.list',
      ['restaurant_id' => $restaurant_id]);

    $response = new RedirectResponse($manageCuisineUrl->toString());
    $response->send();
    return;
  }
}
