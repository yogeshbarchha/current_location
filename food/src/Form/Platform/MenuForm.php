<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class MenuForm extends FormBase {

    public function getFormId() {
        return 'food_platform_menu_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $menu_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}

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
		$entity = $this->getEntity();

        $entity = array_merge($entity, array(
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
        ));

        if (isset($entity['menu_id'])) {
            db_update('food_menu')
                ->fields($entity)
                ->condition('menu_id', $entity['menu_id'])
                ->execute();

            drupal_set_message(t('Menu updated successfully...'));
            
        } else {
            $menu_id = db_insert('food_menu')
                ->fields($entity)
                ->execute();

            drupal_set_message(t('Menu added successfully.'));
        }
		
		$url = Url::fromRoute('food.platform.menu.list');
		$form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $menu_id = \Drupal::routeMatch()->getParameter('menu_id');
		
		$entity = NULL;
		if($menu_id != NULL) {
			$entity = \Drupal\food\Core\MenuController::getMenu($menu_id);
			$entity = (array) $entity;
		} else {
			if ($createDefault) {
				$entity = array(
				);
			}
		}

        return($entity);
    }

}
