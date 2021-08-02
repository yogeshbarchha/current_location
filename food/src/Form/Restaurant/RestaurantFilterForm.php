<?php

namespace Drupal\food\Form\Restaurant;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class RestaurantFilterForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_filter_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['title'] = array(
			'#type' => 'item',
			'#title' => $this->t('Filters'),
        );

		//$form['#theme'] = 'food_restaurant_search_form';

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
