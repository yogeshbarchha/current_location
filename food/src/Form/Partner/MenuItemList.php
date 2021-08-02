<?php

namespace Drupal\food\Form\Partner;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MenuItemList extends ControllerBase {

  public function show() {
    $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
    $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
    $restaurant_menu_id = \Drupal::routeMatch()
      ->getParameter('restaurant_menu_id');
    $restaurant_menu_section_id = \Drupal::routeMatch()
      ->getParameter('restaurant_menu_section_id');

    $build = array(//'#markup' => 'My restaurants',
    );

    $build['container'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#title' => 'Add Item',
        '#url' => Url::fromRoute('food.partner.restaurant.menu.section.item.add',
          [
            'restaurant_id' => $restaurant_id,
            'menu_id' => $menu_id,
            'restaurant_menu_id' => $restaurant_menu_id,
            'restaurant_menu_section_id' => $restaurant_menu_section_id,
          ]),
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
      'back_button_menu' => array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#title' => '  Back to Menu list',
        '#url' => Url::fromRoute('food.partner.restaurant.menu.list',
          array('restaurant_id' => $restaurant_id)),
      ),
      'back_button_section' => array(
        '#type' => 'link',
        '#title' => '  Back to Section list',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#url' => Url::fromRoute('food.partner.restaurant.menu.section.list', [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $menu_id,
          'restaurant_menu_id' => $restaurant_menu_id,
        ]),
      ),
    );

    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array(
        'data' => $this->t('Description'),
        'field' => 'description',
        'sort' => 'asc',
      ),
      array('data' => $this->t('')),
    );

    $rows = \Drupal\food\Core\MenuController::getRestaurantMenuItems($restaurant_id,
      $restaurant_menu_id, $restaurant_menu_section_id, ['header' => $header]);
    foreach ($rows as &$row) {
      $url = Url::fromRoute('food.partner.restaurant.menu.section.item.edit', [
        'restaurant_id' => $restaurant_id,
        'menu_id' => $row->menu_id,
        'restaurant_menu_id' => $row->restaurant_menu_id,
        'restaurant_menu_section_id' => $row->restaurant_menu_section_id,
        'restaurant_menu_item_id' => $row->restaurant_menu_item_id,
      ]);
      $url->setOptions([
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ]);

      $edit_link = Link::fromTextAndUrl(t('Edit'), $url);

      $deleteMenuItemUrl = Url::fromRoute('food.partner.restaurant.menu.section.item.delete',
        [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $row->menu_id,
          'restaurant_menu_id' => $row->restaurant_menu_id,
          'restaurant_menu_section_id' => $row->restaurant_menu_section_id,
          'restaurant_menu_item_id' => $row->restaurant_menu_item_id,
        ]);
      $deleteMenuItemUrl->setOptions([
        'attributes' => [
          'class' => ['delete-button'],
        ],
      ]);
      $deleteMenuItemLink = Link::fromTextAndUrl(t('Delete Item'),
        $deleteMenuItemUrl);

      $row = array(
        'data' => array(
          'name' => $row->name,
          'description' => $row->description,
          'edit_link' => $edit_link->toString(),
          'delete_menu_item_link' => $deleteMenuItemLink->toString(),
        ),
      );
    }

    //Generate the table.
    $build['table'] = array(
      '#theme' => 'table',
      '#header' => $header,
      '#rows' => $rows,
    );

    //Finally add the pager.
    $build['pager'] = array(
      '#type' => 'pager',
    );

    $build['#attached']['library'][] = 'food/common';

    return ($build);
  }

  public function delete($restaurant_id,
    $menu_id,
    $restaurant_menu_id,
    $restaurant_menu_section_id,
    $restaurant_menu_item_id) {
    db_delete('food_restaurant_menu_item')
      ->condition('restaurant_id', $restaurant_id)
      ->condition('menu_id', $menu_id)
      ->condition('restaurant_menu_id', $restaurant_menu_id)
      ->condition('restaurant_menu_section_id', $restaurant_menu_section_id)
      ->condition('restaurant_menu_item_id', $restaurant_menu_item_id)
      ->execute();

    drupal_set_message(t('Restaurant Item deleted successfully.'));

    \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);

    $manageMenuUrl = Url::fromRoute('food.partner.restaurant.menu.section.item.list',
      [
        'restaurant_id' => $restaurant_id,
        'menu_id' => $menu_id,
        'restaurant_menu_id' => $restaurant_menu_id,
        'restaurant_menu_section_id' => $restaurant_menu_section_id,
      ]);

    $response = new RedirectResponse($manageMenuUrl->toString());
    $response->send();
    return;
  }

}
