<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

class ServiceAreaForm extends ContentEntityForm {

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
        
        $form['address'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
            '#required' => TRUE,
            '#default_value' => $entity != NULL ? $entity->address : '',
            '#attributes' => array(
                'class' => array(
                    'address'
                )
            ),
        );
        $form['country'] = array(
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#required' => TRUE,
            '#attributes' => array(
                'class' => array(
                    'country'
                )
            ),
            '#default_value' => $entity != NULL ? $entity->country : '',
        );
        
        $form['restaurant_map'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Map'),
            '#attributes' => array(
                'class' => array(
                    'scrollMap'
                )
            )
        );
        $form['restaurant_map']['latitude_val'] = array(
            '#type' => 'hidden',
            '#attributes' => array(
                'class' => array(
                    'latitude_val'
                )
            ),
            '#default_value' => $entity != NULL ? $entity->latitude : 40.67660124,
        );
        $form['restaurant_map']['longitude_val'] = array(
            '#type' => 'hidden',
            '#attributes' => array(
                'class' => array(
                    'longitude_val'
                )
            ),
            '#default_value' => $entity != NULL ? $entity->longitude : -73.86895552,
        );
        $form['restaurant_map']['delivery_radius_val'] = array(
            '#type' => 'hidden',
        );
        $form['restaurant_map']['delivery_radius'] = array(
            '#type' => 'number',
            '#title' => $this->t('Radius (In Kilometres)'),
            '#step' => '.001',
            '#default_value' => $entity != NULL ? $entity->radius : 1,
        );
        $form['restaurant_map']['delivery_map'] = array(
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => '',
            '#attributes' => array(
                'id' => 'google_map',
                'class' => array(
                    'google_map'
                )
            ),
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

		
        $form['#attached']['library'][] = 'food/form.platform.service.area';

        return ($form);
    }

	/**
	 * {@inheritdoc}
	 */
    public function save(array $form, FormStateInterface $form_state) {
        $entity = $this->getEntity1();

        $entity = array_merge($entity, array(
            'name' => $form_state->getValue('name'),
            'address' => $form_state->getValue('address'),
            'country' => $form_state->getValue('country'),
            'latitude' => $form_state->getValue('latitude_val'),
            'longitude' => $form_state->getValue('longitude_val'),
            'radius' => round($form_state->getValue('delivery_radius'), 3, PHP_ROUND_HALF_UP),
            'description' => $form_state->getValue('description'),
            'url_slug' => $form_state->getValue('url_slug'),
        ));
		
        $picture = $form_state->getValue('picture');
        if (!empty($picture)) {
            $file = \Drupal\file\Entity\File::load($picture[0]);
            $file->setPermanent();
            $file->save();

            $file_usage = \Drupal::service('file.usage');
            $file_usage->add($file, 'food', 'food_service_area', $file->id());

            $entity['image_fid'] = $file->id();
        }

		$baseEntity = $this->getEntity();
		foreach($entity as $key => $value) {
			$baseEntity->$key = $value;
		}
		$this->updateChangedTime($baseEntity);
		$baseEntity->save();
			
        if (isset($entity['service_area_id'])) {
            drupal_set_message(t('Service Area updated successfully...'));
        } else {
            drupal_set_message(t('Service Area added successfully.'));
        }

        $url = Url::fromRoute('entity.food_service_area.collection');
        $form_state->setRedirectUrl($url);
    }

    private function getEntity1($createDefault = TRUE) {
        $service_area_id = \Drupal\food\Util::getServiceAreaIdFromUrl();
        $entity = NULL;

        if ($service_area_id != NULL) {
            $entity = \Drupal\food\Core\ServiceAreaController::getServiceAreaById($service_area_id);
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
        $dir = 'public://images/platform/servicearea';

        if (!file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS)) {
            $service = \Drupal::service('file_system');
            $service->mkdir($dir, NULL, TRUE); 
        }

        return ($dir);
    }

}
