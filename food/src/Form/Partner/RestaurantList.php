<?php

namespace Drupal\food\Form\Partner;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

class RestaurantList extends ControllerBase {

  public function show() {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isAdministrator = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
    $isPlatform = $currentUser->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
    $add_restaurant = $currentUser->hasPermission('food add restaurant');
    $addlink = array();
    
    if($add_restaurant){
      $addlink = array('add_button' => array(
        '#type' => 'link',
        '#title' => ' Add new ',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#url' => Url::fromRoute('food.partner.restaurant.add'),
      ));
    }

    //The table description.
    $build = array(//'#markup' => 'My restaurants',
    );
    
    $build['page_title'] = [
     '#markup' => $this->t(''),
    ];

    if ($isAdministrator) {
      $build['admin_link'] = [
        '#markup' => $this->t('<a href="@adminlink" class="restaurant-admin-button">Admin page</a>',
          [
            '@adminlink' => Url::fromRoute('food.restaurant_settings')
              ->toString(),
          ]),
      ];
    }

    $build['add_button'] = array(
      'active_button' => array(
        '#type' => 'link',
        '#title' => ' Live ',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#url' => Url::fromUri('internal:/partner/restaurant/list'),
      ),
      'deactive_button' => array(
        '#type' => 'link',
        '#title' => ' Deactivated ',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#url' => Url::fromUri('internal:/partner/restaurant/list',
          array('query' => array('status' => 'deactive'))),
      ),$addlink,
    );

    $header = array(
      array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
      array('data' => $this->t('City'), 'field' => 'city'),
      array('data' => $this->t('')),
      array('data' => $this->t('')),
      array('data' => $this->t('')),
      array('data' => $this->t('')),
    );

    $rows = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants([
      'header' => $header,
      'pageSize' => 15,
      'conditionCallback' => function ($query) use (&$request) {
        $status = \Drupal::request()->query->get('status');
        if (!empty($status) && $status == 'deactive') {
          $query = $query->condition('status', 0);
        }
        else {
          $query = $query->condition('status', 1);
        }

        $query = $query->orderBy('restaurant_id', 'DESC');

        return ($query);
      },
    ]);

    foreach ($rows as &$row) {

      $editUrl = Url::fromRoute('entity.food_restaurant.edit_form',
        array('food_restaurant' => $row->restaurant_id));
      $editLink = Link::fromTextAndUrl(t('Edit'), $editUrl);

      $manageMenuUrl = Url::fromRoute('food.partner.restaurant.menu.list',
        array('restaurant_id' => $row->restaurant_id));
      $manageMenuLink = Link::fromTextAndUrl(t('Manage Menu'), $manageMenuUrl);

      $manageCuisineUrl = Url::fromRoute('food.partner.restaurant.cuisine.list',
        array('restaurant_id' => $row->restaurant_id));
      $manageCuisineLink = Link::fromTextAndUrl(t('Manage Cuisine'),
        $manageCuisineUrl);

      $ownerChangeLink = NULL;
      if ($isPlatform) {
        $ownerChangeUrl = Url::fromRoute('food.platform.restaurant.owner.change',
          array('restaurant_id' => $row->restaurant_id));
        $ownerChangeUrl->setOptions([
          'attributes' => [
            'class' => ['use-ajax'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode([
              'width' => 700,
            ]),
          ],
        ]);
        $ownerChangeLink = Link::fromTextAndUrl(t('Change Owner'),
          $ownerChangeUrl);
      }


      $restaurantStatusChangeUrl = Url::fromRoute('food.restaurant.delete',
        array('restaurant_id' => $row->restaurant_id));
      $restaurantStatusChangeUrl->setOptions([
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ]);
      $restaurantStatusChangeLink = Link::fromTextAndUrl(t('Delete Restaurant'),
        $restaurantStatusChangeUrl);

      $copyRestaurantUrl = Url::fromRoute('food.partner.restaurant.copy',
        array('restaurant_id' => $row->restaurant_id));
      $copyRestaurantLink = Link::fromTextAndUrl(t('Clone restaurant'),
        $copyRestaurantUrl);

      $RestaurantUrl = Url::fromRoute('entity.food_restaurant.canonical',
        array('food_restaurant' => $row->restaurant_id),
        array('attributes' => array('target' => '_blank')));
      $RestaurantLink = Link::fromTextAndUrl(t($row->name), $RestaurantUrl)
        ->toString();

      if ($this->restaurant_mark($row->restaurant_id,
          $row->changed) == 1 && $isAdministrator) {
        $restaurant_name = $RestaurantLink . ' <span class="text-warning">new</span>';
      }
      elseif ($this->restaurant_mark($row->restaurant_id,
          $row->changed) == 2 && $isAdministrator) {
        $restaurant_name = $RestaurantLink . ' <span class="text-warning">updated</span>';
      }
      else {
        $restaurant_name = $RestaurantLink;
      }
       $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
       $copylink ="";
      if((!$user->hasRole('partner')) && (!$user->hasRole('subuser'))):
           $copylink  =  $this->checkRestaurantMenuAccess($row->restaurant_id) ? $copyRestaurantLink->toString() : '';
        endif; 

      $row = array(
        'data' => array(
          'name' => array(
            'data' => array(
              '#type' => 'html_tag',
              '#tag' => 'p',
              '#value' => $restaurant_name,
            ),
          ),
          'city' => $row->city,
          'edit_link' => $this->checkRestaurantMenuAccess($row->restaurant_id) ? $editLink->toString() : '',
          'manage_menu_link' => $this->checkRestaurantMenuAccess($row->restaurant_id) ? $manageMenuLink->toString() : '',
          'manage_cuisine_link' => $this->checkRestaurantMenuAccess($row->restaurant_id) ? $manageCuisineLink->toString() : '',
          'owner_change_link' => $isPlatform ? $ownerChangeLink->toString() : '',
          'copy_restaurant_link' => $copylink,
          'status_update_link' => $isAdministrator ? $restaurantStatusChangeLink->toString() : '',
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

  public static function restaurant_mark($restaurant_id, $timestamp) {
    $cache = &drupal_static(__FUNCTION__, array());

    if (\Drupal::currentUser()->isAnonymous()) {
      return \Drupal\food\Core\RestaurantController::MARK_READ;
    }
    if (!isset($cache[$restaurant_id])) {
      $cache[$restaurant_id] = restaurant_last_viewed($restaurant_id);
    }
    if ($cache[$restaurant_id] == 0 && $timestamp > \Drupal\food\Core\RestaurantController::NODE_NEW_LIMIT) {
      return \Drupal\food\Core\RestaurantController::MARK_NEW;
    }
    elseif ($timestamp > $cache[$restaurant_id] && $timestamp > \Drupal\food\Core\RestaurantController::NODE_NEW_LIMIT) {
      return \Drupal\food\Core\RestaurantController::MARK_UPDATED;
    }
    return \Drupal\food\Core\RestaurantController::MARK_READ;
  }
  
  public static function checkRestaurantMenuAccess($food_restaurant){

    $user = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food edit restaurant')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($food_restaurant);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return FALSE;     
      }
      return TRUE;
    }
    if($user->hasRole('subuser') && $user->hasPermission('food edit restaurant')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($user->id());
      if(!empty($subuser_permission) && $subuser_permission['permission'] == 'full' && in_array($food_restaurant, $subuser_restaurants)){
        return TRUE;        
      }
    }
    return FALSE;
  }

}
