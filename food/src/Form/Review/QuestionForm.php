<?php

namespace Drupal\food\Form\Review;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\BeforeCommand;

class QuestionForm extends FormBase {

    public function getFormId() {
        return 'food_review_question_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $question_id = NULL) {
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}        

        $form['#prefix'] = '<div id="question-form-wrapper">';
        $form['#suffix'] = '</div>';

        $form['status'] = array(
            '#type' => 'checkbox',
            '#title' => t('Active'),
            '#default_value' => $entity != NULL ? $entity->status : 0,
        );

        $form['question'] = array(
            '#type' => 'textfield',
            '#title' => t('Question'),
            '#required' => TRUE,
			'#default_value' => $entity != NULL ? $entity->question_name : '',
       );

        $form['position'] = array(
            '#type' => 'select',
            '#title' => t('Position'),
			'#default_value' => $entity != NULL ? $entity->question_positionID : 0,
            '#options' => array(0,1,2,3,4,5,6,7,8,9,10),
        );

        $form['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Submit'),
            '#button_type' => 'primary',
            '#attributes' => array(
                'class' => array(
                  'use-ajax-submit',
                ),
            ),
        );

        // $form['#attached']['library'][] = 'core/drupal.ajax';
        // $form['#attached']['library'][] = 'core/jquery.form';

        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $response = new AjaxResponse();
        $message = '';
        $entity = $this->getEntity();

        $entity = array_merge($entity, array(
            'question_name' => $form_state->getValue('question'),
            'question_positionID' => $form_state->getValue('position'),
            'status' => $form_state->getValue('status'),
        ));

        if (isset($entity['question_id'])) {
            db_update('review_questions')
                ->fields($entity)
                ->condition('question_id', $entity['question_id'])
                ->execute();

            $message = '<div class="alert alert-success alert-dismissable">
                 <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                 Question updated successfully...
                 </div>';
            
        } else {
            $question_id = db_insert('review_questions')
                ->fields($entity)
                ->execute();

            $message = '<div class="alert alert-success alert-dismissable">
                 <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                 Question added successfully...
                 </div>';  
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
			if ($createDefault) {
				$entity = array(
				);
			}
		}

        return($entity);
    }

}
