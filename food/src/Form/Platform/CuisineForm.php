<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class CuisineForm extends FormBase {

    public function getFormId() {
        return 'food_platform_cuisine_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $menu_id = NULL) {
        $entity = $this->getEntity(FALSE);
        if ($entity != NULL) {
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

        $form['featured'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Featured'),
            '#default_value' => $entity != NULL ? $entity->featured : '',
            '#suffix' => '<br/>',
        );

        if ($entity != NULL && is_numeric($entity->image_fid)) {
            $picture = \Drupal\file\Entity\File::load($entity->image_fid);
            if ($picture) {
                $form['current_picture'] = array(
                    '#type' => 'html_tag',
                    '#title' => t('Current Picture'),
                    '#tag' => 'img',
                    '#attributes' => array(
                        'src' => $picture->url(),
                        'style' => 'max-height: 150px;'
                    ),
                );
            }
        }
        $form['picture'] = array(
            '#type' => 'managed_file',
            '#title' => $this->t('Picture'),
            '#upload_validators' => array(
                'file_validate_extensions' => array('png', 'jpg', 'jpeg', 'gif'),
                'file_validate_size' => 4194304, //4 MB
            ),
            '#theme' => 'image_widget',
            '#preview_image_style' => 'medium',
            '#upload_location' => $this->getUploadLocation(),
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
        $cuisine_id = \Drupal::routeMatch()->getParameter('cuisine_id');
        $name = $form_state->getValue('name');
		
        $query = db_select('food_cuisine', 'fc')
            ->fields('fc')
            ->condition('fc.name', $name);
		if(!empty($cuisine_id)) {
			$query = $query
				->condition('fc.cuisine_id', $cuisine_id, '!=');
		}
            
		$validator = $query->countQuery()->execute()->fetchField();
        if ($validator > 0) {
            $form_state->setErrorByName('cuisine_id', $this->t('Cuisine name already exists!'));
        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $entity = $this->getEntity();

        $entity = array_merge($entity, array(
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'featured' => $form_state->getValue('featured'),
        ));

        $picture = $form_state->getValue('picture');
        if (!empty($picture)) {
            $file = \Drupal\file\Entity\File::load($picture[0]);
            $file->setPermanent();
            $file->save();

            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($file, 'food', 'food_cuisine', $file->id());

            $entity['image_fid'] = $file->id();
        }

        if (isset($entity['cuisine_id'])) {
            db_update('food_cuisine')
                ->fields($entity)
                ->condition('cuisine_id', $entity['cuisine_id'])
                ->execute();

            drupal_set_message(t('Cuisine updated successfully...'));
        } else {
            $menu_id = db_insert('food_cuisine')
                ->fields($entity)
                ->execute();

            drupal_set_message(t('Cuisine added successfully.'));
        }

        $url = Url::fromRoute('food.platform.cuisine.list');
        $form_state->setRedirectUrl($url);
    }

    private function getEntity($createDefault = TRUE) {
        $cuisine_id = \Drupal::routeMatch()->getParameter('cuisine_id');

        $entity = NULL;
        if ($cuisine_id != NULL) {
            $entity = \Drupal\food\Core\CuisineController::getCuisine($cuisine_id);
            $entity = (array) $entity;
        } else {
            if ($createDefault) {
                $entity = array(
                );
            }
        }

        return($entity);
    }

    private function getUploadLocation() {
        //$restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        $dir = 'public://images/cuisine/';

        if (!file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS)) {
            $service = \Drupal::service('file_system');
            $service->mkdir($dir, NULL, TRUE);
        }

        return ($dir);
    }

}
