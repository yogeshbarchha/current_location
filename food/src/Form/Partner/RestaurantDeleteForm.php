<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;

class RestaurantDeleteForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_change_status_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
      $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
      $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);

      $form['display_status'] = array(
        '#type' => 'item',
        '#markup' => '<p class="close-now">Are you sure you want to delete '.$restaurant->name.'</p>',
      );
      
      $form['restaurant_id'] = array('#type' => 'hidden', '#value' => $restaurant_id);

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete') ,
      );

      $form['cancel'] = array(
        '#type' => 'submit',
        '#value' => t('Cancel') ,
      );
      
      return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
      $op = $form_state->getTriggeringElement()['#value']->__toString();
      
      if($op == 'Delete' && !empty($form_state->getValue('restaurant_id'))){
        $restaurant_id = $form_state->getValue('restaurant_id');
        $restaurant_entity = \Drupal::entityTypeManager()->getStorage('food_restaurant')->load($restaurant_id);
        $restaurant_entity->delete();
        
        drupal_set_message(t('Restaurant delete successfully'), 'status');        
      }

        $url = Url::fromRoute('entity.food_restaurant.collection');
        $form_state->setRedirectUrl($url);
    }

}
