<?php

namespace Drupal\food\Form\User;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ProfileForm extends FormBase {
  
  public function getFormId() {
    return 'food_user_profile_form';
  }
  
  //$user parameter below is actually user id from url.
  public function buildForm(array $form, FormStateInterface $form_state, $user = NULL) {
	$user = $this->getEntity();
	
	$form['first_name'] = array(
		'#type' => 'textfield',
		'#title' => t('First Name'),
		'#required' => TRUE,
		'#default_value' => $user->get('user_first_name')->value,
	);

	$form['last_name'] = array(
		'#type' => 'textfield',
		'#title' => t('Last Name'),
		'#required' => FALSE,
		'#default_value' => $user->get('user_last_name')->value,
	);

	$form['phone_number'] = array(
		'#type' => 'tel',
		'#title' => t('Phone Number'),
		'#required' => FALSE,
		'#default_value' => $user->get('field_phone_number')->value,
		'#attributes' => [
			'maxlength' => 10,
		],
	);

	$form['gender'] = array(
		'#type' => 'radios',
		'#title' => 'Gender',
		'#options' => array(
			'M' => t('Male'),
			'F' => t('Female'),
		),
		'#required' => FALSE,
		'#default_value' => $user->get('user_gender')->value,
	);
	
	if(!empty($user->user_picture) && !$user->user_picture->isEmpty()) {
		/*$image = $user->user_picture->first()->view('large');
		$rendered = \Drupal::service('renderer')->renderPlain($image);
		$form['current_picture'] = array(
			//'#type' => 'markup',
			//'#title' => t('Current Profile Picture'),
			'#markup' => $rendered,
		);*/
		$form['current_picture'] = array(
			'#type' => 'html_tag',
			'#title' => t('Current Profile Picture'),
			'#tag' => 'img',
			'#attributes' => array(
				'src' => $user->user_picture->entity->url(),
			),
		);
	}

	$field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
	$fieldStorage = \Drupal\field\Entity\FieldStorageConfig::loadByName(\Drupal::entityTypeManager()->getStorage('user')->getEntityTypeId(), 'user_picture');
	$form['profile_picture'] = array(
		'#type' => 'managed_file',
		'#title' => $field->label(),
		'#upload_validators' => array(
		   'file_validate_extensions' => array($field->getSetting('file_extensions')),
		   'file_validate_size' => array(\Drupal\Component\Utility\Bytes::toInt($field->getSetting('max_filesize'))),
		),
		'#theme' => 'image_widget',
		'#preview_image_style' => 'medium',
		'#upload_location' => $fieldStorage->getSetting('uri_scheme') . '://' . \Drupal::token()->replace($field->getSetting('file_directory')),
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
	$user = $this->getEntity();

	 $image = $form_state->getValue('profile_picture');
	 if(!empty($image)) {
		 $field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
		 $file = \Drupal\file\Entity\File::load($image[0]);
		 
		 file_validate_image_resolution($file, $field->getSetting('max_resolution'), $field->getSetting('min_resolution'));
		 $file->setPermanent();
		 $file->save();
		 
		 $user->set('user_picture', $file);
	 }
	 
	 $user->set('user_first_name', $form_state->getValue('first_name'));
	 $user->set('user_last_name', $form_state->getValue('last_name'));
	 $user->set('field_phone_number', $form_state->getValue('phone_number'));
	 $user->set('user_gender', $form_state->getValue('gender'));
	 
	 $user->save();
  }
  
  private function getEntity() {
	$uid = \Drupal::routeMatch()->getParameter('user');

	\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($uid);
	
	$user = \Drupal\user\Entity\User::load($uid);
	
	return($user);
  }
}
