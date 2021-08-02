<?php

namespace Drupal\food\Form\Partner;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MenuSectionList extends ControllerBase {

  public function show() {
    $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
    $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
    $restaurant_menu_id = \Drupal::routeMatch()
      ->getParameter('restaurant_menu_id');

    $build = array(//'#markup' => 'My restaurants',
    );

    $build['container'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#title' => 'Add Section',
        '#url' => Url::fromRoute('food.partner.restaurant.menu.section.add', [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $menu_id,
          'restaurant_menu_id' => $restaurant_menu_id,
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
    );

    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('')),
    );
    $rows = \Drupal\food\Core\MenuController::getRestaurantMenuSections($restaurant_id,
      $restaurant_menu_id, ['header' => $header]);
    foreach ($rows as &$row) {
      $manageSectionUrl = Url::fromRoute('food.partner.restaurant.menu.section.item.list',
        [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $row->menu_id,
          'restaurant_menu_id' => $row->restaurant_menu_id,
          'restaurant_menu_section_id' => $row->restaurant_menu_section_id,
        ]);
      /*$manageSectionUrl->setOptions([
          'attributes' => [
              'class' => ['use-ajax'],
              'data-dialog-type' => 'modal',
              'data-dialog-options' => Json::encode([
                  'width' => 700,
              ]),
          ]
      ]);*/
      $manageSectionLink = Link::fromTextAndUrl(t('Menu Items'),
        $manageSectionUrl);

      $editMenuSectionUrl = Url::fromRoute('food.partner.restaurant.menu.section.edit',
        [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $row->menu_id,
          'restaurant_menu_id' => $row->restaurant_menu_id,
          'restaurant_menu_section_id' => $row->restaurant_menu_section_id,
        ]);
      $editMenuSectionUrl->setOptions([
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ]);
      $editMenuSectionLink = Link::fromTextAndUrl(t('Edit Section'),
        $editMenuSectionUrl);

      $deleteMenuSectionUrl = Url::fromRoute('food.partner.restaurant.menu.section.delete',
        [
          'restaurant_id' => $restaurant_id,
          'menu_id' => $row->menu_id,
          'restaurant_menu_id' => $row->restaurant_menu_id,
          'restaurant_menu_section_id' => $row->restaurant_menu_section_id,
        ]);
      $deleteMenuSectionUrl->setOptions([
        'attributes' => [
          'class' => ['delete-button'],
        ],
      ]);
      $deleteMenuSectionLink = Link::fromTextAndUrl(t('Delete Section'),
        $deleteMenuSectionUrl);

      $row = array(
        'data' => array(
          'name' => $row->name,
          'manage_section_link' => $manageSectionLink->toString(),
          'edit_section_link' => $editMenuSectionLink->toString(),
          'delete_section_link' => $deleteMenuSectionLink->toString(),
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
    $restaurant_menu_section_id) {
    db_delete('food_restaurant_menu_section')
      ->condition('restaurant_id', $restaurant_id)
      ->condition('menu_id', $menu_id)
      ->condition('restaurant_menu_id', $restaurant_menu_id)
      ->condition('restaurant_menu_section_id', $restaurant_menu_section_id)
      ->execute();

    drupal_set_message(t('Restaurant Section deleted successfully.'));

    \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);

    $manageMenuUrl = Url::fromRoute('food.partner.restaurant.menu.section.list',
      [
        'restaurant_id' => $restaurant_id,
        'menu_id' => $menu_id,
        'restaurant_menu_id' => $restaurant_menu_id,
      ]);

    $response = new RedirectResponse($manageMenuUrl->toString());
    $response->send();
    return;
  }

}
