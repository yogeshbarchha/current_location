<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class DishForm extends ContentEntityForm {

	/**
	 * {@inheritdoc}
	 */
    public function buildForm(array $form, FormStateInterface $form_state) {
		$form = parent::buildForm($form, $form_state);

        $entity = $this->getEntity1(FALSE);
        if ($entity != NULL) {
            $entity = (object) $entity;
        }
        
        $form['name'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Name'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->name : '',
        );
        

        if ($entity != NULL && is_numeric($entity->image_fid)) {
            $picture = \Drupal\file\Entity\File::load($entity->image_fid);
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
        $form['description'] = array(
            '#type' => 'text_format',
            '#default_value' => $entity != NULL ? $entity->description : '',
        );
        $form['url_slug'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Url Slug'),
            '#default_value' => $entity != NULL ? $entity->url_slug : '',
        );

		
        return ($form);
    }

	/**
	 * {@inheritdoc}
	 */
    public function save(array $form, FormStateInterface $form_state) {
        $entity = $this->getEntity1();

        $entity = array_merge($entity, array(
            'name' => $form_state->getValue('name'),
            'description' => $form_state->getValue('description'),
            'url_slug' => $form_state->getValue('url_slug'),
        ));
		
        $picture = $form_state->getValue('picture');
        if (!empty($picture)) {
            $file = \Drupal\file\Entity\File::load($picture[0]);
            $file->setPermanent();
            $file->save();

            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($file, 'food', 'food_dish', $file->id());

            $entity['image_fid'] = $file->id();
        }

		$baseEntity = $this->getEntity();
		foreach($entity as $key => $value) {
			$baseEntity->$key = $value;
		}
		$this->updateChangedTime($baseEntity);
		$baseEntity->save();
			
        if (isset($entity['dish_id'])) {
            drupal_set_message(t('Dish updated successfully...'));
        } else {
            drupal_set_message(t('Dish added successfully.'));
        }

        $url = Url::fromRoute('entity.food_dish.collection');
        $form_state->setRedirectUrl($url);
    }

    private function getEntity1($createDefault = TRUE) {
        $dish_id = \Drupal\food\Util::getDishIdFromUrl();
        $entity = NULL;

        if ($dish_id != NULL) {
            $entity = \Drupal\food\Core\DishController::getDishById($dish_id);
            $entity = (array) $entity;
        } else {
            if ($createDefault) {
                $entity = array(
                );
            }
        }

        return ($entity);
    }

    private function getUploadLocation() {
        $dir = 'public://images/platform/dish';

        if (!file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS)) {
            $service = \Drupal::service('file_system');
            $service->mkdir($dir, NULL, TRUE); 
        }

        return ($dir);
    }

}
