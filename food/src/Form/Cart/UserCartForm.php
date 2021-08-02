<?php

namespace Drupal\food\Form\Cart;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class UserCartForm extends FormBase {

    public function getFormId() {
        return 'food_user_cart_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
		//$form['#theme'] = 'food_user_cart_form';

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
