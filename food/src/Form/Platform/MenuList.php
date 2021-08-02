<?php

namespace Drupal\food\Form\Platform;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class MenuList extends ControllerBase {

  public function show() {
    //Heading.
    $build = array(//'#markup' => 'Menu List',
    );

    $build['container'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#title' => 'Add Menu',
        '#url' => Url::fromRoute('food.platform.menu.add'),
        '#attributes' => [
          'class' => ['use-ajax', 'restaurant-admin-button'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ),
    );

    //Table header
    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('Description'), 'field' => 'description'),
      array('data' => $this->t('')),
    );

    $rows = \Drupal\food\Core\MenuController::getAllMenus(['header' => $header]);

    foreach ($rows as &$row) {
      $url = Url::fromRoute('food.platform.menu.edit',
        array('menu_id' => $row->menu_id));
      $url->setOptions([
        'attributes' => [
          'class' => ['use-ajax', 'restaurant-admin-button'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ]);
      $edit_link = Link::fromTextAndUrl(t('Edit'), $url);

      $row = array(
        'data' => array(
          'name' => $row->name,
          'description' => $row->description,
          'edit_link' => $edit_link->toString(),
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

    return $build;
  }
}
