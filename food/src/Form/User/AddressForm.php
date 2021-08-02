<?php

namespace Drupal\food\Form\User;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class AddressForm extends FormBase {

    public function getFormId() {
        return 'food_user_address_form';
    }

    //$user parameter below is actually user id from url.
    public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $address_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}
		
       
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Full name'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->contact_name : '',
        );

       /* $form['contact_info']['email'] = array(
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->email : '',
        );
    */
        $form['phone_number'] = array(
			'#type' => 'tel',
            '#title' => $this->t('Phone Number'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->phone_number : '',
			'#attributes' => [
				'maxlength' => 10,
			],
        );

       /* $form['contact_info']['fax_number'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Fax Number'),
            '#required' => FALSE,
            '#default_value' => $entity != NULL ? $entity->fax_number : '',
        ); */


     
        $form['type'] = array(
            '#type' => 'radios',
            '#title' => $this->t('Type'),
            '#options' => array(
                0 => t('Home'),
                1 => t('Office'),
            ),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->type : 0,
        );
         $form['address_info']['address_line2'] = array(
             '#type' => 'textfield',
             '#title' => $this->t('Building/House No.'),
             '#required' => TRUE,
             '#default_value' => $entity != NULL ? $entity->address_line2 : '',
         );
        $form['address_info']['address_line'] = array(
                '#type' => 'textfield',
                '#title' => $this->t('Street Address'),
                '#required' => TRUE,
                '#default_value' => $entity != NULL ? $entity->address_line1 : '',
        );

        $form['address_info']['address_line_latitude'] = array(
                '#type' => 'hidden',
                '#default_value' => $entity != NULL ? $entity->latitude : '',
            );
        $form['address_info']['address_line_longitude'] = array(
            '#type' => 'hidden',
            '#default_value' => $entity != NULL ? $entity->longitude : '',
        );

       

        // $form['address_info']['address_line2'] = array(
        //     '#type' => 'textfield',
        //     '#title' => $this->t('Address Line2'),
        //     '#default_value' => $entity != NULL ? $entity->address_line2 : '',
        // );

        $form['address_info']['city'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->city : '',
        );

        $form['address_info']['state'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('State'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->state : '',
        );

        $form['address_info']['postal_code'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Postal Code'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->postal_code : '',
        );

     /*   $form['address_info']['country'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->country : '',
        );
   */

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#button_type' => 'primary',
        );

        $form['#attached']['library'][] = 'food/form.user.address';

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
		$uid = \Drupal::routeMatch()->getParameter('user');

        $combine_address = $form_state->getValue('address_line1').' '.$form_state->getValue('address_line2').' '.$form_state->getValue('city').' '.$form_state->getValue('state').' '.$form_state->getValue('postal_code').' '.$form_state->getValue('country');

        //print "<pre>";print_r('https://maps.googleapis.com/maps/api/geocode/json?address='.$combine_address);

		$entity = $this->getEntity(TRUE);

        $entity = array_merge($entity, array(
            'type' => $form_state->getValue('type'),
            'contact_name' => $form_state->getValue('name'),
            'phone_number' => $form_state->getValue('phone_number'),
            'fax_number' => $form_state->getValue('fax_number'),
            'email' => $form_state->getValue('email'),
            'address_line1' => $form_state->getValue('address_line'),
            'address_line2' => $form_state->getValue('address_line2'),
            'city' => $form_state->getValue('city'),
            'state' => $form_state->getValue('state'),
            'postal_code' => $form_state->getValue('postal_code'),
            'country' => $form_state->getValue('country'),
            'latitude' => !empty($form_state->getValue('address_line_latitude')) ? $form_state->getValue('address_line_latitude') : 0,
            'longitude' => !empty($form_state->getValue('address_line_longitude')) ? $form_state->getValue('address_line_longitude') : 0,
        ));

        if (isset($entity['address_id'])) {
            db_update('food_user_address')
                ->fields($entity)
                ->condition('address_id', $entity['address_id'])
                ->execute();

            drupal_set_message(t('Address updated successfully...'));
			
        } else {
            db_insert('food_user_address')
                ->fields($entity)
                ->execute();

            drupal_set_message(t('Added address successfully.'));
        }
		
		$url = Url::fromRoute('food.user.address.list', ['user' => $uid]);
		$form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $uid = \Drupal::routeMatch()->getParameter('user');
        $address_id = \Drupal::routeMatch()->getParameter('address_id');
		
		\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($uid);
		
		$entity = NULL;
		if($address_id != NULL) {
			$entity = \Drupal\food\Core\AddressController::getUserAddress($uid, $address_id);
			$entity = (array) $entity;
		} else {
			if ($createDefault) {
				$entity = array(
					'owner_user_id' => $uid,
					'created_time' => \Imbibe\Util\TimeUtil::now(),
				);
			}
		}

        return($entity);
    }

}
