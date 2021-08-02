<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Routing;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;


class NewMenuForm extends FormBase {

    public function getFormId() {
        return 'food_restaurant_new_menu_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $menu_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}

        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

        $form['status_message'] = array(
          '#type' => 'markup',
          '#markup' => '<div id="new-menu-status-wrapper"></div>',
        );

        $form['restaurant_menu_add_link'] = array(
            '#title' => $this->t('Back'),
            '#type' => 'link',
            '#url' => Url::fromRoute('food.partner.restaurant.menu.add',['restaurant_id' => $restaurant_id]),
            '#attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ],
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
            '#ajax' => array(
              'callback' => '::promptCallback',
              'wrapper' => 'new-menu-status-wrapper',
            ),
        );
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
		
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

    public function promptCallback(array &$form, FormStateInterface $form_state) {
           $ajax_response = new AjaxResponse();
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

            $text = '<div class="alert alert-success alert-dismissable">
                   <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                   Menu updated successfully....
                   </div>';
            $ajax_response->addCommand(new HtmlCommand('#new-menu-status-wrapper', $text));
            
        } else {
            $menu_id = db_insert('food_menu')
                ->fields($entity)
                ->execute();

            $text = '<div class="alert alert-success alert-dismissable">
                   <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                   Menu Created Successfully.
                   </div>';
            $ajax_response->addCommand(new HtmlCommand('#new-menu-status-wrapper', $text));
        }      
               
        return $ajax_response;
    }


}
