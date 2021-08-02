<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class RestaurantOwnerChangeForm extends FormBase {

    public function getFormId() {
        return 'restaurant_owner_change_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);

        $user_storage = \Drupal::service('entity_type.manager')->getStorage('user');
        $userIds = $user_storage->getQuery()
            ->condition('status', 1)
            ->condition('roles', \Drupal\food\Core\RoleController::Partner_Role_Name)
            ->execute();
        $users = $user_storage->loadMultiple($userIds);

        $options = [];
        foreach($users as $user) {
            $options[$user->id()] = $user->getDisplayName();
        }
        $form['restaurant_owner_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select User'),
            '#required' => TRUE,
            '#options' => $options,
            '#default_value' => $restaurant->owner_user_id,
        );

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#button_type' => 'primary',
        );
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal::routeMatch()->getParameter('restaurant_id');

        self::updateOwnerByRestaurantId($restaurant_id, $form_state->getValue('restaurant_owner_id'));
        drupal_set_message(t('Restaurant owner change successfully.'));

        $url = Url::fromRoute('entity.food_restaurant.collection');
        $form_state->setRedirectUrl($url);
    }

    private function updateOwnerByRestaurantId($restaurant_id, $owner_user_id) {
        db_update('food_restaurant')
            ->fields(['owner_user_id' => $owner_user_id])
            ->condition('restaurant_id', $restaurant_id)
            ->execute();

        db_update('food_restaurant_cuisine')
            ->fields(['owner_user_id' => $owner_user_id])
            ->condition('restaurant_id', $restaurant_id)
            ->execute();

        db_update('food_restaurant_menu')
            ->fields(['owner_user_id' => $owner_user_id])
            ->condition('restaurant_id', $restaurant_id)
            ->execute();

        db_update('food_restaurant_menu_section')
            ->fields(['owner_user_id' => $owner_user_id])
            ->condition('restaurant_id', $restaurant_id)
            ->execute();

        db_update('food_restaurant_menu_item')
            ->fields(['owner_user_id' => $owner_user_id])
            ->condition('restaurant_id', $restaurant_id)
            ->execute();
    }

}
