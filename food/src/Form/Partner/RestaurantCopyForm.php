<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class RestaurantCopyForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_copy_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Clone Restaurant'),
            '#button_type' => 'primary',
        );
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $current_restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($current_restaurant_id, ['skipHydrateCallback' => TRUE]);
        $restaurant = (array) $restaurant;

        unset($restaurant['restaurant_id']);
        $restaurant['name'] = $restaurant['name'] . ' - Clone';
        $new_restaurant_id = db_insert('food_restaurant')
            ->fields($restaurant)
            ->execute();

        $restaurant_menus = \Drupal\food\Core\MenuController::getRestaurantMenus($current_restaurant_id, ['skipHydrateCallback' => TRUE, 'returnTableDataOnly' => TRUE]);
        foreach ($restaurant_menus as $restaurant_menu) {
            $restaurant_menu = (array) $restaurant_menu;
            $current_restaurant_menu_id = $restaurant_menu['restaurant_menu_id'];
            
            unset($restaurant_menu['restaurant_menu_id']);
            $restaurant_menu['restaurant_id'] = $new_restaurant_id;
            $new_restaurant_menu_id = db_insert('food_restaurant_menu')
                ->fields($restaurant_menu)
                ->execute();

            $restaurant_menu_sections = \Drupal\food\Core\MenuController::getRestaurantMenuSections($current_restaurant_id, $current_restaurant_menu_id, ['skipHydrateCallback' => TRUE]);
            foreach ($restaurant_menu_sections as $restaurant_menu_section) {
                $restaurant_menu_section = (array) $restaurant_menu_section;
                $current_restaurant_menu_section_id = $restaurant_menu_section['restaurant_menu_section_id'];
                
                unset($restaurant_menu_section['restaurant_menu_section_id']);
                $restaurant_menu_section['restaurant_id'] = $new_restaurant_id;
                $restaurant_menu_section['restaurant_menu_id'] = $new_restaurant_menu_id;

                $new_restaurant_menu_section_id = db_insert('food_restaurant_menu_section')
                    ->fields($restaurant_menu_section)
                    ->execute();

                $restaurant_menu_items = \Drupal\food\Core\MenuController::getRestaurantMenuItems($current_restaurant_id, $current_restaurant_menu_id, $current_restaurant_menu_section_id, ['skipHydrateCallback' => TRUE]);
                foreach ($restaurant_menu_items as $restaurant_menu_item) {
                    $restaurant_menu_item = (array) $restaurant_menu_item;
                    $current_restaurant_menu_item_id = $restaurant_menu_item['restaurant_menu_item_id'];
                        
                    unset($restaurant_menu_item['restaurant_menu_item_id']);
                    $restaurant_menu_item['restaurant_id'] = $new_restaurant_id;
                    $restaurant_menu_item['restaurant_menu_id'] = $new_restaurant_menu_id;
                    $restaurant_menu_item['restaurant_menu_section_id'] = $new_restaurant_menu_section_id;

                    db_insert('food_restaurant_menu_item')
                        ->fields($restaurant_menu_item)
                        ->execute();
                }
            }
        }

        $url = Url::fromRoute('entity.food_restaurant.collection');
        $form_state->setRedirectUrl($url);
    }

}
