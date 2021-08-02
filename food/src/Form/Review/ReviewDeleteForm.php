<?php

namespace Drupal\food\Form\Review;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\BeforeCommand;

class ReviewDeleteForm extends FormBase {

    public function getFormId() {
        return 'food_review_delete_form';
    }

    //$user parameter below is actually user id from url.
    public function buildForm(array $form, FormStateInterface $form_state) {
	$review_id = \Drupal::routeMatch()->getParameter('review_id');

      $review = \Drupal\food\Core\ReviewController::getUserReviewById($review_id);

      if(empty($review)){
        $form['empty'] = array('#type' => 'item','#markup' => 'Review Not Found');
        return $form;
      }

    

      $form['display_status'] = array(
        '#type' => 'item',
        '#markup' => '<p class="close-now">Are you sure you want to Delete Review</p>',
      );
      
      

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Delete') ,
        '#attributes' => array(
                    'class' => array(
                      'use-ajax-submit',
                    ),
                ),
      );

      $form['cancel'] = array(
        '#type' => 'submit',
        '#value' => t('Cancel') ,
      );
      
      return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {

    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $message = '';
        $triggering_element = $form_state->getTriggeringElement();
        $review_id = \Drupal::routeMatch()->getParameter('review_id');
      
      if($triggering_element['#value'] == 'Delete' && !empty($review_id)){
        $updated = db_delete('user_review') // Table name no longer needs {}
               
                ->condition('rating_id', $review_id)
                ->execute();
        
       $message = '<div class="alert alert-success alert-dismissable">
                 <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                 Question deleted successfully...
                 </div>'; 
      }

         $build = \Drupal\food\Form\Review\ReviewController::reviewManage();
        $html = \Drupal::service('renderer')->renderPlain($build);
            
        $response = new AjaxResponse();
        $response->addCommand(new CloseModalDialogCommand());
        $response->addCommand(new ReplaceCommand('#review-question-manage-table', $html));
        $response->addCommand(new BeforeCommand('#review-question-manage-table',$message));
        $form_state->setResponse($response);

    
    }



}
