<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class StatementSearchForm extends FormBase {

    public function getFormId() {        
        return 'food_partner_statement_search_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state, $restaurant_id = NULL) {
        $rows = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants();
        $restaurantOptions = array('' => $this->t('Select'));
        foreach ($rows as &$row) {
            $restaurantOptions[$row->restaurant_id] = $row->name;
        }
        
        $form['order_id'] = array(
            '#type' => 'number',
            '#title' => $this->t('Order Id'),
            '#options' => $restaurantOptions,
        );
        
        $form['restaurant_id'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Restaurant'),
            '#options' => $restaurantOptions,
        );


        $form['duration'] = array(
            '#type' => 'select',
            '#title' => $this->t('Select Time'),
            '#options' => array(
				'' => t('All data'),
				'currentWeek' => t('Current week'),
				'lastWeek' => t('Last week'),
				'currentMonth' => t('Current month'),
				'lastMonth' => t('Last month'),
				'currentYear' => t('Current year'),
				'lastYear' => t('Last year'),
				'custom' => t('Custom'),
			),
        );
        $form['start_date'] = array(
            '#type' => 'date',
            '#title' => t('Start Date'),
            '#prefix' =>'<div id="statement_header">',
			'#states' => array(
				'invisible' => array(
					'#edit-duration' => array('!value' => 'custom'),
				),
			),
        );
        $form['end_date'] = array(
            '#type' => 'date',
            '#title' => t('End Date'),
            '#suffix' =>'</div>',
			'#states' => array(
				'invisible' => array(
					'#edit-duration' => array('!value' => 'custom'),
				),
			),
        );
        
        $form['submit'] = array(
            '#type' => 'button',
            '#value' => t('Search'),
            '#button_type' => 'primary',
        );
		
        return ($form);
    }

    public function validateForm(array &$form, FormStateInterface $form_state) {
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
    }

}
