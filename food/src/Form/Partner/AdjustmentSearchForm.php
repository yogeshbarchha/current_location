<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class AdjustmentSearchForm extends FormBase {

    public function getFormId() {        
        return 'food_partner_adjustment_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {
        $rows = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants();
        $values = array('' => $this->t('All'));
        foreach ($rows as &$row) {
            $values[$row->restaurant_id] = $row->name;
        }
		
        $form['restaurant_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Restaurant'),
            '#options' => $values,
        );

        $form['submit'] = array(
            '#type' => 'button',
            '#value' => t('Search'),
            '#button_type' => 'primary',
        );
		
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
