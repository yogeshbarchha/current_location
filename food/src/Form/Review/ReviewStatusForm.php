<?php

namespace Drupal\food\Form\Review;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;

class ReviewStatusForm extends FormBase {

    public function getFormId() {
        return 'food_review_status_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
      $review_id = \Drupal::routeMatch()->getParameter('review_id');

      $review = \Drupal\food\Core\ReviewController::getUserReviewById($review_id);

      if(empty($review)){
        $form['empty'] = array('#type' => 'item','#markup' => 'Review Not Found');
        return $form;
      }

      if($review[0]->status == \Drupal\food\Core\Review\ReviewStatus::Submitted){
        $status = \Drupal\food\Core\Review\ReviewStatus::Approved;
      }elseif($review[0]->status == \Drupal\food\Core\Review\ReviewStatus::Approved){
        $status = \Drupal\food\Core\Review\ReviewStatus::Disapproved;
      }elseif($review[0]->status == \Drupal\food\Core\Review\ReviewStatus::Disapproved){
        $status = \Drupal\food\Core\Review\ReviewStatus::Approved;
      }

      $form['display_status'] = array(
        '#type' => 'item',
        '#markup' => '<p class="close-now">Are you sure you want to update status</p>',
      );
      
      $form['review_status'] = array('#type' => 'hidden', '#value' => $status);

      $form['submit'] = array(
        '#type' => 'submit',
        '#value' => t('Update') ,
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
      $triggering_element = $form_state->getTriggeringElement();
      $review_id = \Drupal::routeMatch()->getParameter('review_id');
      
      if($triggering_element['#value'] == 'Update' && !empty($review_id)){
        $updated = db_update('user_review') // Table name no longer needs {}
                ->fields(array(
                  'status' => $form_state->getValue('review_status'),
                ))
                ->condition('rating_id', $review_id)
                ->execute();
        
        drupal_set_message(t('Status updated successfully'), 'status');        
      }

        $url = Url::fromRoute('food.order.review.manage');
        $form_state->setRedirectUrl($url);
    }

}
