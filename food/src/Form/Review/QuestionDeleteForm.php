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

class QuestionDeleteForm extends FormBase {

    public function getFormId() {
        return 'food_question_delete_form';
    }

    //$user parameter below is actually user id from url.
    public function buildForm(array $form, FormStateInterface $form_state, $question_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;

            $form['message'] = array(
                '#type' => 'item',
                '#markup' => '<p class="close-now">Are you sure you want to delete the question.</p>',
            );

            $form['submit'] = array(
                '#type' => 'submit',
                '#value' => $this->t('Delete'),
                '#attributes' => array(
                    'class' => array(
                      'use-ajax-submit',
                    ),
                ),
            );

        }else{
            $form['empty'] = array(
                '#type' => 'item',
                '#markup' => '<p>Question Not Found.</p>'
            );
        }

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $message = '';
        $question_id = \Drupal::routeMatch()->getParameter('question_id');
        $op = $form_state->getTriggeringElement()['#value']->__toString();

        if($op == 'Delete' && $question_id != NULL){
          $entity = $this->getEntity(TRUE);
          if (isset($entity['question_id'])) {
            db_delete('review_questions')
                ->condition('question_id', $question_id)
                ->execute();
            
            $message = '<div class="alert alert-success alert-dismissable">
                 <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                 Question deleted successfully...
                 </div>'; 
            }            
        }

        $build = \Drupal\food\Form\Review\ReviewController::questionManage();
        $html = \Drupal::service('renderer')->renderPlain($build);
            
        $response = new AjaxResponse();
        $response->addCommand(new CloseModalDialogCommand());
        $response->addCommand(new ReplaceCommand('#review-question-manage-table', $html));
        $response->addCommand(new BeforeCommand('#review-question-manage-table',$message));
        $form_state->setResponse($response);
    }

    private function getEntity($createDefault = TRUE) {
        $question_id = \Drupal::routeMatch()->getParameter('question_id');
		
		$entity = NULL;
		if($question_id != NULL) {
			$entity = \Drupal\food\Core\ReviewController::getQuestion($question_id);
			$entity = (array) $entity;
		} else {

		}

        return($entity);
    }

}
