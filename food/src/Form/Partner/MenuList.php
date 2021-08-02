<?php

namespace Drupal\food\Form\Partner;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MenuList extends ControllerBase {

  public function show() {
    $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

    $build = array(//'#markup' => 'My restaurants',
    );

    $build['container'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#title' => 'Add Menu',
        '#url' => Url::fromRoute('food.partner.restaurant.menu.add',
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
        '#title' => '  Back to Restaurant',
        '#url' => Url::fromRoute('entity.food_restaurant.collection'),
      ),
    );

    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('')),
      array('data' => $this->t('')),
    );

    $rows = \Drupal\food\Core\MenuController::getRestaurantMenus($restaurant_id,
      ['header' => $header]);
    foreach ($rows as &$row) {
      $manageSectionUrl = Url::fromRoute('food.partner.restaurant.menu.section.list',
        [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $row->menu_id,
          'restaurant_menu_id' => $row->restaurant_menu_id,
        ]);
      $manageSectionLink = Link::fromTextAndUrl(t('Manage Sections'),
        $manageSectionUrl);

      $deleteMenuUrl = Url::fromRoute('food.partner.restaurant.menu.delete', [
        'restaurant_id' => $restaurant_id,
        'menu_id' => $row->menu_id,
        'restaurant_menu_id' => $row->restaurant_menu_id,
      ]);
      $deleteMenuUrl->setOptions([
        'attributes' => [
          'class' => ['delete-button'],
        ],
      ]);
      $deleteMenuLink = Link::fromTextAndUrl(t('Delete Menu'), $deleteMenuUrl);

      $row = array(
        'data' => array(
          'name' => $row->name,
          'manage_sections_link' => $manageSectionLink->toString(),
          'delete_menu_link' => $deleteMenuLink->toString(),
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

  public function delete($restaurant_id, $menu_id) {
    db_delete('food_restaurant_menu')
      ->condition('restaurant_id', $restaurant_id)
      ->condition('menu_id', $menu_id)
      ->execute();

    drupal_set_message(t('Restaurant menu deleted successfully.'));

    \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);

    $manageMenuUrl = Url::fromRoute('food.partner.restaurant.menu.list',
      ['restaurant_id' => $restaurant_id]);

    $response = new RedirectResponse($manageMenuUrl->toString());
    $response->send();
    return;
  }
}
