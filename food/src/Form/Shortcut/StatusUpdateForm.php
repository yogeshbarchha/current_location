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

class StatusUpdateForm extends FormBase {

    public function getFormId() {
        return 'food_status_update_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurants = NULL) {
        
        if(empty($restaurants)){
            $form['empty'] = array(
                '#type' => 'item',
                '#markup' => '<p>No Restaurants Found</p>',
            );

            return $form;
        }
        
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
        if (empty($form_state->getValue('restaurant'))) {
            $default_restaurant = key($restaurants);
        }
        else {
            $default_restaurant = $form_state->getValue('restaurant');
        }

        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($default_restaurant);
        $status_field_access = \Drupal\food\Form\Partner\RestaurantForm::checkRestaurantStatus($restaurant->restaurant_id,  $restaurant->owner_user_id);

        $form['status_message'] = [
          '#type' => 'markup',
          '#markup' => '<div id="status-message-wrapper"></div>',
        ];
 
        $form['restaurant'] = array(
            '#type' => 'select',
            '#title' => $this->t('Restaurant'),
            '#options' => $restaurants,
            '#required' => TRUE,
            '#default_value' => $default_restaurant,
            '#ajax' => array(
                'callback' => '::changeOptionsAjax',
                'wrapper' => 'submit-field-wrapper',
            ),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => $restaurant->status ? t('Deactivate') : t('Activate'),
            '#button_type' => $restaurant->status ? 'danger' : 'success',
            '#prefix' => '<div id="submit-field-wrapper">', 
            '#suffix' => '</div>',
            '#ajax' => array(
              'callback' => '::promptCallback',
              'wrapper' => 'status-message-wrapper',
            ),
        );

        if(!$isAdmin && !$status_field_access){
            $form['submit']['#attributes'] = array('disabled' => true);
        }

        return ($form);
    }

    public function changeOptionsAjax(array $form, FormStateInterface $form_state) {
        return $form['submit'];
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

    public function promptCallback(array &$form, FormStateInterface $form_state) {
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
        $ajax_response = new AjaxResponse();
        
        if(!empty($form_state->getValue('restaurant'))){
          $values = $form_state->getValues();
          $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($form_state->getValue('restaurant'));
          $restaurant_active = $restaurant->status ? 0 : 1;
          $processed_by = ($isAdmin && !$restaurant_active) ? 0 : 1;
          
            if(\Drupal\food\Form\Partner\RestaurantForm::checkIfRestaurantExists($restaurant->restaurant_id)){
                if($isAdmin){                    
                    \Drupal\food\Form\Partner\RestaurantForm::updateRestaurantStatus($restaurant->restaurant_id, $processed_by);
                }
            }else{
                \Drupal\food\Form\Partner\RestaurantForm::addNewRestaurantStatus($restaurant->restaurant_id, $restaurant->owner_user_id, $processed_by);          
            }

          $restaurant_entity = \Drupal::entityTypeManager()->getStorage('food_restaurant')->load($form_state->getValue('restaurant'));
          $restaurant_entity->status = $restaurant->status ? 0 : 1 ;
          $restaurant_entity->save();
          $text = '<div class="alert alert-success alert-dismissable">
                   <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                   Restaurant Updated Successfully.
                   </div>';
          $form['submit']['#value'] = $restaurant->status ? t('Activate') : t('Deactivate');
          $form['submit']['#button_type'] = $restaurant->status ? t('success') : t('danger');
          $ajax_response->addCommand(new HtmlCommand('#status-message-wrapper', $text));
          $ajax_response->addCommand(new ReplaceCommand('#submit-field-wrapper', $form['submit']));
          return $ajax_response;
        }
    }

}
