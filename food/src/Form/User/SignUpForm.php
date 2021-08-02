<?php

namespace Drupal\food\Form\User;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class SignUpForm extends FormBase {

  public function getFormId() {
    return 'food_user_signup_form';
  }

  //$user parameter below is actually user id from url.
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#placeholder' => $this->t('Email'),
      '#required' => TRUE,
    );
    $form['first_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
    );
    $form['last_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
    );
   $form['phone_number'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Mobile Number'),
      '#required' => TRUE,
    );
    $form['password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Password'),
      '#required' => TRUE,
    );
    $form['confirm_password'] = array(
      '#type' => 'password',
      '#title' => $this->t('Confirm Password'),
      '#required' => TRUE,
    );

  /*  $form['user_role'] = array(
      '#type' => 'select',
      '#title' => $this->t('Sign Up As:'),
      '#options' => [
        'partner' => $this->t('Parner'),
        'authenticated' => $this->t('User'),
      ],
      '#required' => TRUE,
    ); */

    $form['actions']['sign_in'] = array(
      '#type' => 'link',
      '#title' => t('Sign-in'),
      '#url' => Url::fromRoute('user.login'),
      '#attributes' => [
        'class' => ['use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode([
          'width' => 800,
        ]),
      ],
    );


    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Submit'),
      '#attributes' => array(
        'class' => array(//'use-ajax-submit',
        ),
      ),
    );

    $form['#food_form_submit_callback'] = [];

    $form['#attached']['library'][] = 'core/drupal.ajax';
    $form['#attached']['library'][] = 'core/jquery.form';

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*$response = new AjaxResponse();
        if($form_state->getValue('password') != $form_state->getValue('confirm_password')) {
      $response->addCommand(new HtmlCommand('input[name=confirm_password]', $this->t('Password and confirm password do not match!')));
    }

    return ($response);*/

    if ($form_state->getValue('password') != $form_state->getValue('confirm_password')) {
      $form_state->setErrorByName('confirm_password',
        $this->t('Password and confirm password do not match!'));
    }

    $email = $form_state->getValue('email');
    if (!empty($email)) {
      $user = user_load_by_mail($form_state->getValue('email'));
      if ($user) {
        $form_state->setErrorByName('email',
          $this->t('A user with specified email already exists!'));
      }
    }
    
    $user_phone_number = $form_state->getValue('phone_number');
    if($user_phone_number != NULL) {
      $query = \Drupal::entityQuery('user');
      $existingUserIds = $query
        ->condition('field_phone_number', $user_phone_number)
        ->execute();
      if(count($existingUserIds) > 0) {
         $form_state->setErrorByName('phone_number','A user has already registered with this phone number.');
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

 //   $role = $form_state->getValue('user_role');
  $role ='authenticated';
    switch ($role) {
      case 'partner':
        $user = User::create();

        //Mandatory settings
        $user->setPassword($form_state->getValue('password'));
        $user->enforceIsNew();
        $user->setEmail($form_state->getValue('email'));

        //This username must be unique and accept only a-Z,0-9, - _ @ .
        $user->setUsername($form_state->getValue('email'));

        //Optional settings
        $language = 'en';
        $user->set("init", 'email');
        $user->set("langcode", $language);
        $user->set("preferred_langcode", $language);
        $user->set("preferred_admin_langcode", $language);

        // Assign user role.
        $user->addRole($form_state->getValue('user_role'));
        $user->activate();

        //Save user
        $user->save();

        // Notify user via mail.
        _user_mail_notify('register_no_approval_required', $user);

        // show messages.
        drupal_set_message($this->t('Registration successful. You are now logged in via link sent to your Email.'));
        break;
      case 'authenticated':
        $user = \Drupal\food\Core\UserController::createUser($form_state->getValue('email'),
          $form_state->getValue('password'),
          $form_state->getValue('first_name'),
          $form_state->getValue('last_name'),$form_state->getValue('phone_number'));
        user_login_finalize($user);
        break;
    }


    if (is_array($form['#food_form_submit_callback'])) {
      foreach ($form['#food_form_submit_callback'] as $callback) {
        call_user_func_array($callback, array(&$user, &$form, $form_state));
      }
    }


    if (isset($_REQUEST['destination'])) {
      $url = Url::fromUri('internal:' . $_REQUEST['destination']);
    }
    else {
      $url = Url::fromRoute('<front>');
    }
    $form_state->setRedirectUrl($url);
  }

}
