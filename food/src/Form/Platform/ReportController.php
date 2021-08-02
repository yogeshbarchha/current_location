<?php

namespace Drupal\food\Form\Platform;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;

class ReportController extends ControllerBase {

    public function userList(Request $request) {
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Platform\UserSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_platform_user_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.platform.userlist';
        $build['#attached']['drupalSettings']['food'] = array(
            'userListCallbackUrl' => Url::fromRoute('food.platform.user.listcallback')->toString(),
        );

        return $build;
    }

    public function userListCallback(Request $request) {
        $build = array(
        );

        $header = array(
            array('data' => $this->t('User Name'), 'field' => 'user_name', 'sort' => 'desc'),
            array('data' => $this->t('Email Id'), 'field' => 'user_email'),
        
            array('data' => $this->t('Status'), 'field' => 'status'),
            array('data' => $this->t('Created time'), 'field' => 'created_time'),
            
            array('data' => '', 'field' => 'view_user_order'),
        );

        $rows = \Drupal\food\Core\OrderController::getUsersAndHisOrders([
                'conditionCallback' => function($query) use (&$request) {
                $order_id = $request->query->get('order_id');
                $user_name = $request->query->get('user_name');
                $user_email = $request->query->get('user_email');
                $user_phone= $request->query->get('user_phone');
                $user_address = $request->query->get('user_address');
                $created_time = $request->query->get('created_time');
                $order_amount = $request->query->get('order_amount');
                $delivery_mode = $request->query->get('delivery_mode');
                $restaurant_id = $request->query->get('restaurant_id');
                
               
                if (!empty($order_id)) {
                    $query = $query
                        ->condition('order_id', $order_id);
                }

                if (!empty($user_name)) {
                    $query = $query
                        ->condition('name', '%' . db_like($user_name) . '%', 'LIKE');
                }
                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id, '=');
                }
               if (!empty($user_email)) {
                    $query = $query
                        ->condition('mail', '%' . db_like($user_email) . '%', 'LIKE');
                }
                if (!empty($user_phone)) {
                    $query = $query
                        ->condition('user_phone', '%' . db_like($user_phone) . '%', 'LIKE');
                } 

                if (!empty($user_address)) {
                    $query = $query
                        ->condition('user_address', '%' . db_like($user_address) . '%', 'LIKE');
                }

                if (!empty($created_time)) {
                    $created_time = strtotime($created_time);

                    $created_time = substr($created_time, 0,5);

                    $query = $query
                        ->condition('created', db_like($created_time) . '%', 'LIKE');
                }

                if (!empty($order_amount)) {
                    $query = $query
                        ->condition('net_amount',$order_amount, '=');
                }

                if (!empty($delivery_mode)) {
                    $query = $query
                        ->condition('delivery_mode', $delivery_mode);
                }

                $query = $query->orderBy('created', 'DESC');


                return($query);
            }
        ]);

        foreach ($rows as $index => &$row) {
          $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($row->restaurant_id);
          $viewOrderUrl = Url::fromRoute('food.partner.order.detail', array('order_id' => $row->order_id));
            $viewOrderUrl->setOptions([
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
            $viewOrderLink = Link::fromTextAndUrl(t('View Detail'), $viewOrderUrl);
                 

             $viewUserOrderUrl = Url::fromRoute('food.user.order.list', array('user' => $row->user_id));
              if($row->user_id!=""){
            
                         $query = db_select('users_field_data', 'n')
          ->fields('n', array('name','mail','created'))
           ->condition('uid', $row->user_id)
          ->execute()
          ->fetchObject();
              }


             
             

            $viewUserOrderUrl->setOptions([
                'attributes' => [
                    'target' => '_blank'
                ]
            ]);

            $viewUserOrderLink = Link::fromTextAndUrl(t('View User Orders'), $viewUserOrderUrl);

            $row = array(
                'data' => array(
                    'user_name' => $query->name,
                    'user_address' => $row->user_address,
                    'net_amount'=>$row->net_amount,
                    'user_email' => $query->mail,
                    'user_phone' => $row->user_phone,
                    'status' => $row->ustatus ? 'Active' : 'Deactive', 
                    'restaurant_id'=> $restaurant->name,
                    'created_time' => date("F j, Y, g:i a",$query->created),
                    'view_order' => $viewOrderLink,
                    'view_user_order' => $viewUserOrderLink,
                ),
                'order' => $row
            );
        }

        //Generate the table.
        $build['table'] = array(
            '#theme' => 'food_platform_user_grid',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );

        //Finally add the pager.
        $build['pager'] = array(
            '#type' => 'pager'
        );

        $html = \Drupal::service('renderer')->renderPlain($build);
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('.food_platform_user_grid_container', $html));
        return ($response);
    }
        
} 

