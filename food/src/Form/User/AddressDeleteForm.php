<?php

namespace Drupal\food\Form\User;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class AddressDeleteForm extends FormBase {

    public function getFormId() {
        return 'food_user_address_delete_form';
    }

    //$user parameter below is actually user id from url.
    public function buildForm(array $form, FormStateInterface $form_state, $user = NULL, $address_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;

            $form['message'] = array(
                '#type' => 'item',
                '#markup' => '<p class="close-now">Are you sure you want to delete the address.</p>',
            );

            $form['submit'] = array(
                '#type' => 'submit',
                '#value' => $this->t('Delete'),
            );

        }else{
            $form['empty'] = array(
                '#type' => 'item',
                '#markup' => '<p>Address Not Found.</p>'
            );
        }

        $form['#attached']['library'][] = 'food/form.user.address';        

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
		$uid = \Drupal::routeMatch()->getParameter('user');
        $address_id = \Drupal::routeMatch()->getParameter('address_id');
        $op = $form_state->getTriggeringElement()['#value']->__toString();

        if($op == 'Delete' && $uid != NULL && $address_id != NULL){
          $entity = $this->getEntity(TRUE);
          if (isset($entity['address_id'])) {
            db_delete('food_user_address')
                ->condition('owner_user_id', $uid)
                ->condition('address_id', $address_id)
                ->execute();
                drupal_set_message(t('Address deleted successfully...'));        
            }            
        }
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

		}

        return($entity);
    }

}
