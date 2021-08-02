<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;

class MenuForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_menu_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {

        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        
        $form['menu_add_link'] = array(
            '#title' => $this->t('Add New Menu'),
            '#type' => 'link',
            '#url' => Url::fromRoute('food.partner.restaurant.menu.new',['restaurant_id' => $restaurant_id]),
            '#attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ],
        );

        $form['restaurant_menu_active'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Activate/Deactivate Item'),
            '#attributes' => array(
                'class' => array(
                    'restaurant_menu_active'
                )
            ),
            '#default_value' => \Drupal\food\Core\EntityStatus::Enabled,
        );
        
        $rows = \Drupal\food\Core\MenuController::getAllMenus();
        $values = array();
        foreach ($rows as &$row) {
            $values[$row->menu_id] = $row->name;
        }
		
        $form['menu_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Menu'),
            '#required' => TRUE,
            '#options' => $values,
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
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $menu_id = $form_state->getValue('menu_id');
		
        $query = db_select('food_restaurant_menu', 'fm')
            ->fields('fm')
            ->condition('fm.restaurant_id', $restaurant_id)
            ->condition('fm.menu_id', $menu_id);
			
        $validator = $query->countQuery()->execute()->fetchField();
        if ($validator > 0) {
            $form_state->setErrorByName('menu_id', $this->t('The menu already exists for the selected restaurant!'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$entity = $this->getEntity();

        $entity = array_merge($entity, array(
			'menu_id' => $form_state->getValue('menu_id'),
            'status' => $form_state->getValue('restaurant_menu_active'),
        ));
		
		db_insert('food_restaurant_menu')
			->fields($entity)
			->execute();

		drupal_set_message(t('Menu added for restaurant successfully.'));

        \Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);
		
		$url = Url::fromRoute('food.partner.restaurant.menu.list', array('restaurant_id' => $entity['restaurant_id']));
		$form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
		
		$entity = NULL;
		if($restaurant_menu_id != NULL) {
			$entity = \Drupal\food\Core\MenuController::getRestaurantMenu($restaurant_menu_id);
			$entity = (array) $entity;
		} else {
			if ($createDefault) {
				$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
				$entity = array(
					'owner_user_id' => $restaurant->owner_user_id,
					'restaurant_id' => $restaurant_id,
				);
			}
		}

        return($entity);
    }

}
