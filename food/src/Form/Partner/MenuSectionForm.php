<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class MenuSectionForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_menu_section_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
        $restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
        $restaurant_menu_section_id = \Drupal::routeMatch()->getParameter('restaurant_menu_section_id');
        
        $entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}
        
        $form['restaurant_section_active'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Activate/Deactivate Item'),
            '#attributes' => array(
                'class' => array(
                    'restaurant_section_active'
                )
            ),
            '#default_value' => $entity != NULL ? $entity->status : \Drupal\food\Core\EntityStatus::Enabled,
        );

        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->name : '',
        );

        $form['description'] = array(
            '#type' => 'textarea',
            '#title' => $this->t('Description'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->description : '',
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
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
        $restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
        $restaurant_menu_section_id = \Drupal::routeMatch()->getParameter('restaurant_menu_section_id');

        $entity = $this->getEntity(TRUE);

        $entity = array_merge($entity, array(
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'status' => $form_state->getValue('restaurant_section_active'),
        ));
        
        if( $restaurant_menu_section_id != NULL ) {
			db_update('food_restaurant_menu_section')
				->fields($entity)
				->condition('restaurant_menu_section_id', $restaurant_menu_section_id)
				->execute();
				
			drupal_set_message(t('Menu section updated successfully.'));
		} else {
			db_insert('food_restaurant_menu_section')
            ->fields($entity)
            ->execute();

            drupal_set_message(t('Menu section added successfully.'));  
		}

        \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);
        
        $url = Url::fromRoute('food.partner.restaurant.menu.section.list', array('restaurant_id' => $entity['restaurant_id'], 'menu_id' => $entity['menu_id'], 'restaurant_menu_id' => $entity['restaurant_menu_id']));
        $form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
        $restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
        $restaurant_menu_section_id = \Drupal::routeMatch()->getParameter('restaurant_menu_section_id');

        $entity = NULL;
        if ($restaurant_menu_section_id != NULL) {
            $entity = \Drupal\food\Core\MenuController::getRestaurantMenuSection($restaurant_menu_section_id);
            $entity = (array) $entity;
        } else {
            if ($createDefault) {
                $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
                $entity = array(
                    'owner_user_id' => $restaurant->owner_user_id,
                    'restaurant_id' => $restaurant_id,
                    'menu_id' => $menu_id,
                    'restaurant_menu_id' => $restaurant_menu_id,
                );
            }
        }

        return($entity);
    }

}
