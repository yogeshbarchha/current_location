<?php

namespace Drupal\food\Form\User;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\ContentEntityForm;
use Imbibe\Util\PhpHelper;

class SignUpRestaurentForm extends FormBase {

  public function getFormId() {
    return 'food_restaurent_signup_form';
  }

  //$user parameter below is actually user id from url.
  public function buildForm(array $form, FormStateInterface $form_state) {
  
   $form['restaurent_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Restaurent Name'),
       '#placeholder' => $this->t('Restaurent Name'),
      '#required' => TRUE,
    );
   $form['zip_code'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Zip Code'),
       '#placeholder' => $this->t('Zip Code'),
      '#required' => TRUE,
    );
  $form['Contact_name'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Contact Name'),
      '#placeholder' => $this->t('Contact Name'),
      '#required' => TRUE,
    );

    $form['email'] = array(
      '#type' => 'email',
      '#title' => $this->t('Contact Email'),
      '#placeholder' => $this->t('Contact Email'),
      '#required' => TRUE,
    );

      $form['owner_cell_phone'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Owner's Cell Phone"),
      '#placeholder' => $this->t("Owner's Cell Phone"),
      '#required' => TRUE,
    );
     $form['restaurant_phone'] = array(
      '#type' => 'textfield',
      '#title' => $this->t("Restaurant Phone"),
      '#placeholder' => $this->t("Restaurant Phone"),
      '#required' => TRUE,
    );
    $form['do_you_offer_delivery'] = array(
      '#type' => 'radios',
      '#title' => $this->t("Do you offer delivery? "),
     '#default_value' => 1,
     '#options' => array(1 => $this->t('Yes'), 0 => $this->t('No')),
    );

    // $form['password'] = array(
    //   '#type' => 'password',
    //   '#title' => $this->t('Password'),
    //   '#required' => TRUE,
    // );
    // $form['confirm_password'] = array(
     //  '#type' => 'password',
    //   '#title' => $this->t('Confirm Password'),
    //   '#required' => TRUE,
   //  );

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

    // if ($form_state->getValue('password') != $form_state->getValue('confirm_password')) {
    //   $form_state->setErrorByName('confirm_password',
    //     $this->t('Password and confirm password do not match!'));
    // }
    
    if(!preg_match('/^[0-9]{10}+$/', $form_state->getValue('restaurant_phone'))){
        $form_state->setErrorByName('restaurant_phone', $this->t('Restaurant Phone number should be 10 digit.'));
    }
    
    if(!preg_match('/^[0-9]{10}+$/', $form_state->getValue('owner_cell_phone'))){
        $form_state->setErrorByName('restaurant_phone', $this->t('Owner Cell Phone number should be 10 digit.'));
    }

    $email = $form_state->getValue('email');
    if (!empty($email)) {
      $user = user_load_by_mail($form_state->getValue('email'));
      if ($user) {
        $form_state->setErrorByName('email',
          $this->t('A user with specified email already exists!'));
      }
    }
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

 
        $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
        $user = \Drupal\user\Entity\User::create();

        //Mandatory settings
        //$user->setPassword($form_state->getValue('password'));
        //$pass = user_password();
        //$user->set('pass', $pass);
        $user->enforceIsNew();
        $user->setEmail($form_state->getValue('email'));

        //This username must be unique and accept only a-Z,0-9, - _ @ .
        $user->setUsername($form_state->getValue('email'));

        //Optional settings
        //$user->setValue('pass',user_password());
        $user->set("init", $form_state->getValue('email'));
        $user->set("langcode", $language);
        $user->set("preferred_langcode", $language);
        $user->set("preferred_admin_langcode", $language);
        $user->activate();

        // Assign user role.
        $user->addRole('partner');

        //Save user
        $user->save();

       _user_mail_notify("register_no_approval_required", $user);

        $restaurant = $this->create_restaurant($form_state, $user);

        $new_restaurant_id = db_query('SELECT MAX(restaurant_id) FROM {food_restaurant}')->fetchField();

        $query = \Drupal::database()->insert('restaurant_activation_status');
        $query->fields(
        array(
        'restaurant_id' => $new_restaurant_id,
        'owner_id' => $user->id(),
        'status' => 0,
        'processed_by' => 0,
        )
        )->execute();    


        // show messages.
        drupal_set_message($this->t('Thanks ('.$user->get('name')->value.') for joining us.A confirmation mail has been sent to you, please check your mail and follow steps to generate your password.Please contact FoodOnDeal at 8885181475 in case you did not receive the email.'));
        $form_state->setRedirect('<front>');
      


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


  public function create_restaurant($form_state,$user){
      
      $values = array();

      $order_types = new \Drupal\food\Core\Restaurant\OrderTypeSettings();
      $order_types->delivery_settings = new \Drupal\food\Core\Restaurant\DeliverySettings();
      $order_types->delivery_settings->enabled = 0;
      $order_types->delivery_settings->estimated_delivery_time_minutes = 0;
      $order_types->delivery_settings->minimum_order_amount = 0;
      $order_types->delivery_settings->delivery_charges_amount = 0;
      $order_types->pickup_settings = new \Drupal\food\Core\Restaurant\PickupSettings();
      $order_types->pickup_settings->enabled = 0;
      $order_types->pickup_settings->estimated_pickup_time_minutes = 0;

      $orderContactDetail = new \Drupal\food\Core\Restaurant\OrderContactDetails();
      $orderContactDetail->email = $form_state->getValue('email');
          
      $values['created_time'] = \Imbibe\Util\TimeUtil::now();
      $values['owner_user_id'] = $user->get('uid')->value;
      $values['name'] = $form_state->getValue('restaurent_name');
      $values['postal_code'] = $form_state->getValue('zip_code');
      $values['email'] = $form_state->getValue('email');
      $values['order_contact_details'] = json_encode($orderContactDetail);
      $values['phone_number'] = $form_state->getValue('restaurant_phone');
      $values['status'] = 0;
      $values['address_line1'] = 'NA';
      $values['city'] = 'NA';
      $values['state'] = 'NA';
      $values['country'] = 'NA';
      $values['latitude'] = 0;
      $values['longitude'] = 0;
      $values['order_types'] = json_encode($order_types);
      $values['tax_pct'] = 0;
      
      $new = \Drupal::entityTypeManager()->getStorage('food_restaurant')->create($values);
      $new->save();

      return $new;

  }

}
