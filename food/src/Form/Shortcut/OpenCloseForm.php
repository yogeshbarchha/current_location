<?php

namespace Drupal\food\Form\Shortcut;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;
use Drupal\food\Core\RestaurantController;
use Drupal\food\Core\RoleController;
use Symfony\Component\HttpFoundation\Request;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CssCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;



class OpenCloseForm extends FormBase {

    public function getFormId() {
        return 'food_open_close_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurants = NULL) {
        
        if(empty($restaurants)){
            $form['empty'] = array(
                '#type' => 'item',
                '#markup' => '<p>No Restaurants Found</p>',
            );

            return $form;
        }
        
        $weekDays = [
          'monday',
          'tuesday',
          'wednesday',
          'thursday',
          'friday',
          'saturday',
          'sunday',
        ];


        if (empty($form_state->getValue('restaurant'))) {
            $default_restaurant = key($restaurants);
        }else{
            $default_restaurant = $form_state->getValue('restaurant');            
        }

        $entity = \Drupal\food\Core\RestaurantController::getRestaurantById($default_restaurant);

        $form['status_message'] = array(
          '#type' => 'markup',
          '#markup' => '<div id="openclose-message-wrapper"></div>',
        );

        $form['restaurant'] = array(
            '#type' => 'select',
            '#title' => $this->t('Restaurant'),
            '#options' => $restaurants,
            '#required' => TRUE,
            '#default_value' => $default_restaurant,
            '#ajax' => array(
                'callback' => '::changeOpeningClosingAjax',
                'wrapper' => 'timing_field_wrapper',
                'method' => 'replace',
            ),
        );

        $timings = $entity != NULL ? $entity->timings : NULL;
        $open_timings = $timings != NULL ? $timings->open_timings : NULL;
        $delivery_timings = $timings != NULL ? $timings->delivery_timings : NULL;
        

        $form['timings_info'] = array(
            '#type' => 'fieldset',
            '#title' => $this->t('Restaurant Timings'),
            '#prefix' => '<div id="timing_field_wrapper">',
            '#suffix' => '</div>',
        );
        
        $form['timings_info']['timings_table'] = array(
            '#type' => 'table',
            '#header' => array(
            $this->t(''),
            $this->t('Open Time'),
            $this->t('Close Time'),
            $this->t(''),
            $this->t('Delivery Start Time'),
            $this->t('Delivery End Time'),
            $this->t(''),
            ),
        );

        foreach ($weekDays as $weekDayIndex => $weekDay) {
          $form['timings_info']['timings_table'][$weekDayIndex]['day'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t(ucwords($weekDay)),
            '#attributes' => array(
              'class' => array(
                'weekday_name',
              ),
            ),
            '#value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? 1 : '',
          );

          $form['timings_info']['timings_table'][$weekDayIndex]['open_start_time'] = array(
            '#type' => 'textfield',
            '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
              'readonly' => 'readonly',
              'class' => array('open_start_time', 'timepicker'),
            ) : array(
              'readonly' => 'readonly',
              'disabled' => 'disabled',
              'class' => array('open_start_time', 'timepicker'),
            ),
            '#value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? $open_timings[$weekDay]->start_time : '',
          );

          $form['timings_info']['timings_table'][$weekDayIndex]['open_end_time'] = array(
            '#type' => 'textfield',
            '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
              'readonly' => 'readonly',
              'class' => array('open_end_time', 'timepicker'),
            ) : array(
              'readonly' => 'readonly',
              'disabled' => 'disabled',
              'class' => array('open_end_time', 'timepicker'),
            ),
            '#value' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? $open_timings[$weekDay]->end_time : '',
          );
          $form['timings_info']['timings_table'][$weekDayIndex]['open_time_apply_btn'] = array(
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => 'Apply to All',
            '#attributes' => ($open_timings != NULL && isset($open_timings[$weekDay])) ? array(
              'class' => array(
                'open_time_apply_btn',
                'btn btn-info',
              ),
            ) : array(
              'class' => array('open_time_apply_btn', 'btn btn-info'),
              'style' => array('display:none'),
            ),
          );

          $form['timings_info']['timings_table'][$weekDayIndex]['del_start_time'] = array(
            '#type' => 'textfield',
            '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
              'readonly' => 'readonly',
              'class' => array('del_start_time', 'timepicker'),
            ) : array(
              'readonly' => 'readonly',
              'disabled' => 'disabled',
              'class' => array('del_start_time', 'timepicker'),
            ),
            '#value' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? $delivery_timings[$weekDay]->start_time : '',
          );
          $form['timings_info']['timings_table'][$weekDayIndex]['del_end_time'] = array(
            '#type' => 'textfield',
            '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
              'readonly' => 'readonly',
              'class' => array('del_end_time', 'timepicker'),
            ) : array(
              'readonly' => 'readonly',
              'disabled' => 'disabled',
              'class' => array('del_end_time', 'timepicker'),
            ),
            '#value' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? $delivery_timings[$weekDay]->end_time : '',
          );
          $form['timings_info']['timings_table'][$weekDayIndex]['del_time_apply_btn'] = array(
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => 'Apply to All',
            '#attributes' => ($delivery_timings != NULL && isset($delivery_timings[$weekDay])) ? array(
              'class' => array(
                'del_time_apply_btn',
                'btn btn-info',
              ),
            ) : array(
              'class' => array('del_time_apply_btn', 'btn btn-info'),
              'style' => array('display:none'),
            ),
          );
        }


        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => t('Update'),
            '#button_type' => 'primary',
            '#ajax' => array(
              'callback' => '::promptCallback',
              'wrapper' => 'openclose-message-wrapper',
            ),

        );

        $form['#attached']['library'][] = 'food/form.shortcut.opencloseform';
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        
    }

    public function changeOpeningClosingAjax(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'food/form.shortcut.opencloseform';
        return $form['timings_info'];
    }

    public function promptCallback(array &$form, FormStateInterface $form_state) {
    	  $form['#attached']['library'][] = 'food/form.shortcut.opencloseform';
        $values = $form_state->getUserInput();
        $timingsValue = $form_state->getUserInput();
        $ajax_response = new AjaxResponse();
        $error = FALSE;
        $text  = '';
        $weekDays = array('monday','tuesday','wednesday','thursday','friday','saturday','sunday');


        if(!empty($values['restaurant']) && is_numeric($values['restaurant'])){
          if(isset($values['timings_table']) && !empty($values['timings_table'])){
            foreach ($values['timings_table'] as $index => $timingValue) {
              if($timingValue['day']){
                if(empty($timingValue['open_start_time'])){
                  $error = TRUE;
                  $text .= '<div class="alert alert-danger alert-dismissable">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  Please select Open Time for '.$weekDays[$index].'.
                  </div>'; 
                }elseif(empty($timingValue['open_end_time'])){
                  $error = TRUE;
                  $text .= '<div class="alert alert-danger alert-dismissable">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  Please select Close Time for '.$weekDays[$index].'.
                  </div>';
                }elseif(empty($timingValue['del_start_time'])){
                  $error = TRUE;
                  $text .= '<div class="alert alert-danger alert-dismissable">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  Please select Delivery start timing for '.$weekDays[$index].'.
                  </div>';
                }elseif(empty($timingValue['del_end_time'])){
                  $error = TRUE;
                  $text .= '<div class="alert alert-danger alert-dismissable">
                  <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                  Please select Delivery end timing for '.$weekDays[$index].'.
                  </div>';
                }
              }
            }
          }
        }else{
          $error = TRUE;
          $text .= '<div class="alert alert-danger alert-dismissable">
                   <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                   Restaurant Not Found.
                   </div>';
        }


        if(!$error){

          $entity = \Drupal::entityTypeManager()->getStorage('food_restaurant')->load($values['restaurant']);
          $timings = new \Drupal\food\Core\Restaurant\RestaurantTimings();
          $timings->open_timings = new \Imbibe\Collections\Dictionary();
          $timings->delivery_timings = new \Imbibe\Collections\Dictionary();

          foreach ($timingsValue['timings_table'] as $index => $timingValue) {
            if ($timingValue['day'] != 0) {
              $timeRange = new \Drupal\food\Core\DateTime\TimeRange();
              $timeRange->start_time = $timingValue['open_start_time'];
              $timeRange->end_time = $timingValue['open_end_time'];
              $timings->open_timings[$weekDays[$index]] = $timeRange;

              $timeRange = new \Drupal\food\Core\DateTime\TimeRange();
              $timeRange->start_time = $timingValue['del_start_time'];
              $timeRange->end_time = $timingValue['del_end_time'];
              $timings->delivery_timings[$weekDays[$index]] = $timeRange;
            }
          }

          $entity->set('timings', json_encode($timings));
          $entity->setChangedTime(REQUEST_TIME);
          $entity->save();

          $text .= '<div class="alert alert-success alert-dismissable">
                 <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                 Timings Updated Successfully.
                 </div>';
        }       
        	
      $ajax_response->addCommand(new HtmlCommand('#openclose-message-wrapper', $text));	
      return $ajax_response;
    }

}
