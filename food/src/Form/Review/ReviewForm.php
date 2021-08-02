<?php

/**
 * @file
 * Contains \Drupal\food\Form\Review\ReviewstepOneForm.
 */

namespace Drupal\food\Form\Review;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\AppendCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormBase;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ReviewForm extends FormBase
 {

  /**
   * Multi steps of the form.
   *
   * @var \Drupal\ms_ajax_form_example\Step\StepInterface
   */
  protected $step;

  protected $total;

   /**
   * {@inheritdoc}
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory) {
    $this->step = 1;
    $this->tempStore = $temp_store_factory->get('review_question_data');
  }

  // Uses Symfony's ContainerInterface to declare dependency to be passed to constructor
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('user.private_tempstore')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormID() {
    return 'review_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $orderRating = \Drupal\food\Core\ReviewController::getAverageRatingByOrderId($order_id);
    $userpoint = \Drupal\food\Core\ReviewController::getCurrentUserReviewByOrderId($order_id);
    if(isset($userpoint[0]->review_details) && !empty($userpoint[0]->review_details)){
      $review_details = json_decode($userpoint[0]->review_details);
    }
    $questions = \Drupal\food\Core\ReviewController::getAllavtiveQuestions();
    $this->total = count($questions);
    
    $form['wrapper-messages'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'messages-wrapper',
      ],
    ];

    $form['wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'form-wrapper',
      ],
    ];

    if(!empty($questions) && count($questions) > 0){
      foreach ($questions as $key => $value) {
        if($this->step == $key+1){
          $form['wrapper']['question'] = array('#type' => 'hidden','#value' => $value->question_id);
          
          $form['wrapper']['answer'] = array(
            '#type' => 'radios',
            '#title' => $this->t($value->question_name),
            '#options' => array(1 => 'Yes', 0 => 'No'),
            '#default_value' => isset($review_details->{$value->question_id}) ? $review_details->{$value->question_id} : 1, 
          ); 
        }
      }

    }else{
      $form['wrapper']['empty'] = array('#markup' => 'Review Not Found.');
      return $form;
    }


    if($this->step != 1){

      $form['wrapper']['previous'] = array(
        '#type' => 'submit',
        '#value' => t('Previous'),
        '#button_type' => 'primary',
        '#ajax' => array(
          'callback' => '::loadStep',
          'wrapper' => 'review-form-wrapper',
        ),
      );

    }

    if($this->step != $this->total + 1){

      $form['wrapper']['next'] = array(
        '#type' => 'submit',
        '#value' => t('Next'),
        '#button_type' => 'primary',
        '#ajax' => array(
          'callback' => '::loadStep',
          'wrapper' => 'review-form-wrapper',
        ),
      );

    }

    if($this->step == $this->total + 1){

      $form['wrapper']['rating'] = array(
        '#type' => 'hidden',
        '#attributes' => array(
        'id' => 'rating_star',
        'orderID' => $order_id,
        'point' => isset($userpoint[0]->total_points) ? $userpoint[0]->total_points : '',
        ),
      );

      $form['wrapper']['rating_wrapper'] = array(
        '#type' => 'item',
        '#markup' => '<div class="overall-rating">
        (Average Rating <span id="avgrat">'.$orderRating["average_rating"].'</span> Based on <span id="totalrat">'.$orderRating["rating_number"].'</span>  rating)
        </div>',
      );


      $form['wrapper']['comment'] = array(
        '#type' => 'textarea',
        '#title' => t('Comment'),
        '#default_value' => isset($review_details->comment) ? $review_details->comment : '',
      );

      $form['wrapper']['submit'] = array(
        '#type' => 'submit',
        '#value' => 'Submit',
        '#ajax' => array(
          'callback' => [$this, 'loadStep'],
          'wrapper' => 'form-wrapper',
          'effect' => 'fade',
        ),
      );

    }

    $form['#attached']['library'][] = 'food/form.review';
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $values = $form_state->getValues();

    if($triggering_element['#value'] == 'Submit'){
      if(isset($values['rating']) && !$values['rating']){
        $form_state->setErrorByName('rating', 'Please Select Rating.');
      }
      if(isset($values['comment']) && !$values['comment']){
        $form_state->setErrorByName('comment', 'Please add comment.');
      }      
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $order_id = \Drupal::routeMatch()->getParameter('order_id');
    $values = $form_state->getValues();
    $response = new AjaxResponse();

    if(isset($values['question']) && !is_array($this->tempStore->get('question'))){
      $this->tempStore->set('question', array($values['question'] => $values['answer']));
    }else{
      $storage = $this->tempStore->get('question');
      $storage[$values['question']] = $values['answer'];
      $this->tempStore->set('question', $storage);
    }

    if($triggering_element['#value'] == 'Next'){
      $this->step++;
    }elseif($triggering_element['#value'] == 'Previous'){
      $this->step--;
    }else{
      if(!empty($order_id) && !empty($values['rating'])){
        if(isset($values['comment']) && !empty($values['comment'])){
          $storage = $this->tempStore->get('question');
          $storage['comment'] = $values['comment'];
          $this->tempStore->set('question', $storage);     
        }
        $ratingNum = 1;
        $prevRatingResult = \Drupal\food\Core\ReviewController::getCurrentUserReviewByOrderId($order_id);
        if(count($prevRatingResult) > 0){
          $ratingPoints = $values['rating'];

          $query = db_update('user_review') // Table name no longer needs {}
           ->fields(array(
           'total_points' => $ratingPoints,
           'modified' => REQUEST_TIME,
           'review_details' => json_encode(array_filter($this->tempStore->get('question'), 'strlen')),
           ))
           ->condition('order_id', $order_id)
           ->condition('user_id', \Drupal::currentUser()->id())
           ->execute();

           drupal_set_message('Review updated successfully');
           $response->addCommand(new CloseModalDialogCommand());
           $response->addCommand(new RedirectCommand(\Drupal::request()->get('destination')));
           return $response;
        
        }else{
          $query = db_insert('user_review') // Table name no longer needs {}
           ->fields(array(
           'order_id' => $order_id,
           'rating_number' => $ratingNum,
           'total_points' => $values['rating'],
           'created' => REQUEST_TIME,
           'modified' => REQUEST_TIME,
           'user_id' => \Drupal::currentUser()->id(),
           'review_details' => json_encode(array_filter($this->tempStore->get('question'), 'strlen')),
           ))
           ->execute();

           drupal_set_message('Review Added successfully');
           $response->addCommand(new CloseModalDialogCommand());
           $response->addCommand(new RedirectCommand(\Drupal::request()->get('destination')));
           return $response;
        }
      }
    }

    $form_state->setRebuild(TRUE);
    
  }

  public static function loadStep(array &$form, FormStateInterface $form_state) {
    $triggering_element = $form_state->getTriggeringElement();
    $response = new AjaxResponse();
    $messages = drupal_get_messages();

    if (!empty($messages)) {
      // Form did not validate, get messages and render them.
      $messages = [
        '#theme' => 'status_messages',
        '#message_list' => $messages,
        '#status_headings' => [
          'status' => t('Status message'),
          'error' => t('Error message'),
          'warning' => t('Warning message'),
        ],
      ];
      $response->addCommand(new HtmlCommand('#messages-wrapper', $messages));
      $messages = array();
    }else {
      // Remove messages.
      $response->addCommand(new HtmlCommand('#messages-wrapper', ''));
    }

    if($triggering_element['#value'] == 'Submit' && empty($form_state->getErrors())){
      $response->addCommand(new HtmlCommand('#messages-wrapper', '<p>Thank you for your feedback.</p>'));
      $response->addCommand(new HtmlCommand('#form-wrapper', ''));
      return $response;      
    }

    // Update Form.
    $response->addCommand(new HtmlCommand('#form-wrapper',
      $form['wrapper']));

    return $response;
  }
}
