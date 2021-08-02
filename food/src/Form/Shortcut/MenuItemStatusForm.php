<?php

namespace Drupal\food\Form\Shortcut;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;

class MenuItemStatusForm extends FormBase {

    public function getFormId() {
        return 'food_menu_item_status_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurants = NULL) {
      
    if(empty($restaurants)){
            $form['empty'] = array(
                '#type' => 'item',
                '#markup' => '<p>No Restaurants Found</p>',
            );

            return $form;
        }

    $user_input = $form_state->getUserInput();

    if (!isset($user_input['restaurant'])) {
        $default_restaurant = key($restaurants);
    }else{
        $default_restaurant = $user_input['restaurant'];
    }

    if (!isset($user_input['restaurant_menu'])) {
        $restaurant_menu = '';
    }else{
        $restaurant_menu = $user_input['restaurant_menu'];            
    }
    
     if (!isset($user_input['restaurant_status'])) {
        $restaurant_status = '2';
    }else{
        $restaurant_status = $user_input['restaurant_status'];
    }   


    $header = array(
            array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
            array('data' => $this->t('Description'), 'field' => 'description', 'sort' => 'asc'),
            array('data' => $this->t('Status')),
    );
    $rows = array();

    $menu_items = \Drupal\food\Core\MenuController::getRestaurantMenuItemstoshortcut($default_restaurant,$restaurant_menu,$restaurant_status, ['header' => $header,'pageSize' => 0]);
    
    $form['status_message'] = [
          '#type' => 'markup',
          '#markup' => '<div id="menu-item-message-wrapper"></div>',
    ];

    $form['restaurant'] = array(
        '#type' => 'select',
        '#title' => $this->t('Restaurant'),
        '#options' => $restaurants,
        '#required' => TRUE,
        '#default_value' => $default_restaurant,
        '#ajax' => array(
            'callback' => '::changeMenuItemAjax',
            'wrapper' => 'restaurant_menu_item_wrapper',
            'method' => 'replace',
         ),
    );
     $form['restaurant_menu'] = array(
        '#type' => 'textfield',
        '#title' => $this->t('Search Menu'),
        '#autocomplete_route_name' => 'food.menu.item.autocomplete',
        '#autocomplete_route_parameters' => array('restaurant' => $default_restaurant, 'count' => 50),
        '#ajax' => array(
            'event' => 'autocompleteclose', 
            'callback' => '::changeMenuItemAjax',
            'wrapper' => 'restaurant_menu_item_wrapper',
            'method' => 'replace',
         ),
	 '#attributes' => array(
          'count' => 50,
         ),
    );
      $form['restaurant_status'] = array(
        '#type' => 'select',
          '#options' => array("2"=> 'All' ,"1" => 'Activate',"0" => 'Deactivate' ),
        '#title' => $this->t('Status'),
        '#default_value' => $restaurant_status,
        '#ajax' => array(
           'change' => TRUE,
            'event' => 'change', 
            'callback' => '::changeMenuItemAjax',
            'wrapper' => 'restaurant_menu_item_wrapper',
            'method' => 'replace',
         ),
    );

    $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Update'),
        '#button_type' => 'primary',
        '#ajax' => array(
              'callback' => '::promptCallback',
              'wrapper' => 'menu-item-message-wrapper',
            ),
    );

    $form['menu_info'] = array(
        '#type' => 'fieldset',
        '#title' => $this->t('Menu'),
        '#prefix' => '<div id="restaurant_menu_item_wrapper">',
        '#suffix' => '</div>',
    );

    $form['menu_info']['menu_item_table'] = array(
            '#type' => 'table',
            '#header' => array(
            $this->t('Name'),
            $this->t('Description'),
            $this->t('Active'),
            ),
    );

    if(!empty($menu_items)){
      foreach ($menu_items as $key => $value) {
        $form['menu_info']['menu_item_table'][$value->restaurant_menu_item_id]['title'] = array(
          '#type' => 'item',
          '#markup' => $value->name,
        );
        $form['menu_info']['menu_item_table'][$value->restaurant_menu_item_id]['description'] = array(
          '#type' => 'item',
          '#markup' => !empty($value->description) ? $value->description : '-',
        );
        $form['menu_info']['menu_item_table'][$value->restaurant_menu_item_id]['status'] = array(
          '#type' => 'checkbox',
          '#attributes' => array(
            'class' => array(),
          ),
          '#value' => $value->status ? 1 : '',
        );
      }
    }

    $form['#attached']['library'][] = 'food/form.shortcut.pickupdeliveryform';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }


  public function changeMenuItemAjax(array $form, FormStateInterface $form_state) {
      return $form['menu_info'];
  }
  
  public function promptCallback(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getUserInput();
    $ajax_response = new AjaxResponse();

    if(!empty($values['restaurant']) && is_numeric($values['restaurant'])){
      foreach ($values['menu_item_table'] as $key => $value) {
        $status = isset($value['status']) ? 1 : 0;
        db_update('food_restaurant_menu_item')
        ->fields(array('status' => $status))
        ->condition('restaurant_menu_item_id', $key)
        ->execute();          
      }
      \Drupal\food\Core\RestaurantController::updateRestaurant($values['restaurant']);
      $text = '<div class="alert alert-success alert-dismissable">
                   <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                   Item updated successfully...
                   </div>';
      $ajax_response->addCommand(new HtmlCommand('#menu-item-message-wrapper', $text));
      return $ajax_response;
    }
  }

}

