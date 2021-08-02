<?php

namespace Drupal\food\Form\Partner;

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

    public function dashboard(Request $request = NULL) {
        $build['#attached']['library'][] = 'food/form.partner.report.dashboardfilter';
        $table1 = array(
            //'#markup' => $this->t('Pending Orders'),
        );
        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Delivery Mode'), 'field' => 'delivery_mode'),
            array('data' => $this->t('Client Address'), 'field' => 'user_address'),
            array('data' => $this->t('Client Phone'), 'field' => 'user_phone'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'restaurant_name'),
            array('data' => $this->t('Restaurant Contact'), 'field' => 'restaurant_contact'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('')),
        );

        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $header,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_ids = $request->get('restaurant_ids');
                if(!in_array('All', $restaurant_ids) && !empty($restaurant_ids)) {
                    $query = $query->condition('fo.restaurant_id', $restaurant_ids ,'IN');
                }
                $one_day_old = strtotime(date("d-m-Y H:i:s", strtotime("- 1 day"))); 
                $query = $query->condition('fo.created_time', ($one_day_old*1000), '>=');
                $query = $query->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Submitted);
                $query = $query->orderBy('fo.order_id', 'DESC');

                return($query);
            }
        ]);
        
        $order_time = time();
        foreach ($rows as $key => $value) {
            $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($value->restaurant_id);
            if($value->delivery_mode == 1){
                $default_time = !empty($restaurant->order_types->delivery_settings->estimated_delivery_time_minutes) ? $restaurant->order_types->delivery_settings->estimated_delivery_time_minutes : 45;
            }elseif($value->delivery_mode == 2){
                $default_time = !empty($restaurant->order_types->pickup_settings->estimated_pickup_time_minutes) ? $restaurant->order_types->pickup_settings->estimated_pickup_time_minutes : 45;
            }
            $schedule_date = strtotime($value->order_details->schedule_date . $value->order_details->schedule_time);
            $schedule_time = $schedule_date - $default_time * 60;
            $current_time = time();
            if($schedule_time > $current_time) {
                unset($rows[$key]);
            }
        }

        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        foreach ($rows as $index => &$row) {
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
            $viewOrderLink = Link::fromTextAndUrl(t('View order'), $viewOrderUrl);

            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                    'delivery_mode' => $row->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? "Delivery" : "Pickup",                    
                    'user_address' => $row->user_address,
                    'user_phone' => $row->user_phone,
                    'amount' => $row->order_details->breakup->net_amount,
                    'restaurant_name' => $row->restaurant->name,
                    'restaurant_contact' => $row->restaurant->phone_number,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    '' => $viewOrderLink->toString(),
                ),
                'order' => $row
            );
        }
        $table1['table'] = array(
            '#theme' => 'food_partner_order_grid_2',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );
        $table1['pager'] = array(
            '#type' => 'pager'
        );

        $header = [
            'orders' => '',
        ];

        $build['table'] = array(
            '#type' => 'table',
            '#header' => $header,
            '#attributes' => array(
                'class' => array('food-partner-dashboard'),
            ),
            '#prefix' => '<div id="partner-order-list">',
            '#suffix' => '</div>',
        );

        $build['table'][0]['orders'] = $table1;

        $build['#attached']['library'][] = 'food/form.partner.report.dashboard';
        $build['#attached']['drupalSettings']['food'] = array(
            'dashboardOrderStatus' => 'active',
            'dashboardRefreshUrl' => Url::fromRoute('food.partner.report.dashboardrefresh')->toString(),
        );

        return $build;
    }

    public function dashboardCompleted(Request $request = NULL) {
        $build['#attached']['library'][] = 'food/form.partner.report.dashboardfilter';

        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
              array('data' => $this->t('Delivery Mode'), 'field' => 'delivery_mode'),
            array('data' => $this->t('Client Address'), 'field' => 'user_address'),
            array('data' => $this->t('Client Phone'), 'field' => 'user_phone'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'restaurant_name'),
            array('data' => $this->t('Restaurant Contact'), 'field' => 'restaurant_contact'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('')),
        );
        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $header,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_ids = $request->get('restaurant_ids');
                if(!in_array('All', $restaurant_ids) && !empty($restaurant_ids)) {
                    $query = $query->condition('fo.restaurant_id', $restaurant_ids ,'IN');
                }
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);

                $query = $query->orderBy('fo.order_id', 'DESC');

                return($query);
            }
        ]);

        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        foreach ($rows as $index => &$row) {
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
            $viewOrderLink = Link::fromTextAndUrl(t('View order'), $viewOrderUrl);

            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                     'delivery_mode' => $row->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? "Delivery" : "Pickup", 
                    'user_address' => $row->user_address,
                    'user_phone' => $row->user_phone,
                    'amount' => $row->order_details->breakup->net_amount,
                    'restaurant_name' => $row->restaurant->name,
                    'restaurant_contact' => $row->restaurant->phone_number,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    '' => $viewOrderLink->toString(),
                ),
                'order' => $row
            );
        }
        $table1['table'] = array(
            '#theme' => 'food_partner_order_grid',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );
        $table1['pager'] = array(
            '#type' => 'pager'
        );

        $header = [
            'orders' => '',
        ];

        $build['table'] = array(
            '#type' => 'table',
            '#header' => $header,
            '#attributes' => array(
                'class' => array('food-partner-dashboard'),
            ),
            '#prefix' => '<div id="partner-order-list">',
            '#suffix' => '</div>',
        );



        $build['table'][0]['orders'] = $table1;
        $build['#attached']['drupalSettings']['food'] = array(
            'dashboardOrderStatus' => 'complete',
            'completeOrderRefreshUrl' => Url::fromRoute('food.partner.report.completeorderrefresh')->toString(),
        );

        return $build;
    }

    public function dashboardCancelled(Request $request = NULL) {
        $build['#attached']['library'][] = 'food/form.partner.report.dashboardfilter';

        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Delivery Mode'), 'field' => 'delivery_mode'),
            array('data' => $this->t('Client Address'), 'field' => 'user_address'),
            array('data' => $this->t('Client Phone'), 'field' => 'user_phone'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'restaurant_name'),
            array('data' => $this->t('Restaurant Contact'), 'field' => 'restaurant_contact'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('')),
        );
        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $header,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_ids = $request->get('restaurant_ids');
                if(!in_array('All', $restaurant_ids) && !empty($restaurant_ids)) {
                    $query = $query->condition('fo.restaurant_id', $restaurant_ids ,'IN');
                }
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled);

                $query = $query->orderBy('fo.order_id', 'DESC');

                return($query);
            }
        ]);

        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        foreach ($rows as $index => &$row) {
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
            $viewOrderLink = Link::fromTextAndUrl(t('View order'), $viewOrderUrl);

            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                    'delivery_mode' => $row->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? "Delivery" : "Pickup", 
                    'user_address' => $row->user_address,
                    'user_phone' => $row->user_phone,
                    'amount' => $row->order_details->breakup->net_amount,
                    'restaurant_name' => $row->restaurant->name,
                    'restaurant_contact' => $row->restaurant->phone_number,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    '' => $viewOrderLink->toString(),
                ),
                'order' => $row
            );
        }
        $table1['table'] = array(
            '#theme' => 'food_partner_order_grid',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );
        $table1['pager'] = array(
            '#type' => 'pager'
        );

        $header = [
            'orders' => '',
        ];

        $build['table'] = array(
            '#type' => 'table',
            '#header' => $header,
            '#attributes' => array(
                'class' => array('food-partner-dashboard'),
            ),
            '#prefix' => '<div id="partner-order-list">',
            '#suffix' => '</div>',
        );



        $build['table'][0]['orders'] = $table1;
        $build['#attached']['drupalSettings']['food'] = array(
            'dashboardOrderStatus' => 'cancel',
            'cancelOrderRefreshUrl' => Url::fromRoute('food.partner.report.cancelorderrefresh')->toString(),
        );

        return $build;
    }

    public function dashboardScheduled(Request $request = NULL){
        $build['#attached']['library'][] = 'food/form.partner.report.dashboardfilter';
        $table1 = array(
           // '#markup' => $this->t('Pending Orders'),
        );
        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Delivery Mode'), 'field' => 'delivery_mode'),
            array('data' => $this->t('Client Address'), 'field' => 'user_address'),
            array('data' => $this->t('Client Phone'), 'field' => 'user_phone'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'restaurant_name'),
            array('data' => $this->t('Restaurant Contact'), 'field' => 'restaurant_contact'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('')),
        );
        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $header,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_ids = $request->get('restaurant_ids');
                if(!in_array('All', $restaurant_ids) && !empty($restaurant_ids)) {
                    $query = $query->condition('fo.restaurant_id', $restaurant_ids ,'IN');
                }
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Submitted);

                $query = $query->orderBy('fo.order_id', 'DESC');

                return($query);
            }
        ]);
        
        $order_time = time();
        foreach ($rows as $key => $value) {
            $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($value->restaurant_id);
            if($value->delivery_mode == 1){
                $default_time = !empty($restaurant->order_types->delivery_settings->estimated_delivery_time_minutes) ? $restaurant->order_types->delivery_settings->estimated_delivery_time_minutes : 45;
            }elseif($value->delivery_mode == 2){
                $default_time = !empty($restaurant->order_types->pickup_settings->estimated_pickup_time_minutes) ? $restaurant->order_types->pickup_settings->estimated_pickup_time_minutes : 45;
            }
            $schedule_date = strtotime($value->order_details->schedule_date . $value->order_details->schedule_time);
            $schedule_time = $schedule_date - $default_time * 60;
            $current_time = time();
            if(($schedule_time < $current_time) || (empty($value->order_details->schedule_date) && empty($value->order_details->schedule_date))) {
                unset($rows[$key]);
            }
        }

        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        foreach ($rows as $index => &$row) {
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
            $viewOrderLink = Link::fromTextAndUrl(t('View order'), $viewOrderUrl);

            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                    'delivery_mode' => $row->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? "Delivery" : "Pickup", 
                    'user_address' => $row->user_address,
                    'user_phone' => $row->user_phone,
                    'amount' => $row->order_details->breakup->net_amount,
                    'restaurant_name' => $row->restaurant->name,
                    'restaurant_contact' => $row->restaurant->phone_number,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    '' => $viewOrderLink->toString(),
                ),
                'order' => $row
            );
        }
        $table1['table'] = array(
            '#theme' => 'food_partner_order_grid_2',
            '#header' => $header,
            '#rows' => $rows,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );
        // $table1['pager'] = array(
        //     '#type' => 'pager'
        // );

        $header = [
            'orders' => '',
        ];

        $build['table'] = array(
            '#type' => 'table',
            '#header' => $header,
            '#attributes' => array(
                'class' => array('food-partner-dashboard'),
            ),
            '#prefix' => '<div id="partner-order-list">',
            '#suffix' => '</div>',
        );

        $build['table'][0]['orders'] = $table1;

        $build['#attached']['library'][] = 'food/form.partner.report.schedule';
        $build['#attached']['drupalSettings']['food'] = array(
            'dashboardOrderStatus' => 'scheduled',
            'scheduleRefreshUrl' => Url::fromRoute('food.partner.report.schedulerefresh')->toString(),
        );

        return $build;
    }

    public function refreshDashboard(Request $request = NULL) {
       $build = $this->dashboard($request);
        $html = \Drupal::service('renderer')->renderPlain($build);

        $lastPendingOrders = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
                'pageSize' => 1,
                'conditionCallback' => function($query) {
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Submitted);

                return($query);
            }
        ]);

        if (count($lastPendingOrders) == 1 &&
            $lastPendingOrders[0]->order_id != \Drupal\food\Core\OrderController::getLastPendingOrderId()) {
            //\Drupal\food\Core\OrderController::setLastPendingOrderId($row->order_id);
            setcookie('food_partner_play_beep', 'yes', strtotime('+1 day'), "/");
        } else {
            setcookie('food_partner_play_beep', 'no', strtotime('+1 day'), "/");
        }

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#partner-order-list', $html));
        return ($response);
    }

    /*
     * Refresh completed orders on filter change.
    */
    public function refreshdashboardCompleted(Request $request = NULL) {
        // print "<pre>";print_r($request);die();
        $build = $this->dashboardCompleted($request);
        $html = \Drupal::service('renderer')->renderPlain($build);

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#partner-order-list', $html));
        return ($response);
    }
    /*
     * Refresh cancle orders on filter change.
    */
    public function refreshdashboardCancelled(Request $request = NULL) {
        // print "<pre>";print_r($request);die();
        $build = $this->dashboardCancelled($request);
        $html = \Drupal::service('renderer')->renderPlain($build);

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#partner-order-list', $html));
        return ($response);
    }

    public function refreshScheduled(Request $request = NULL) {
       $build = $this->dashboardScheduled($request);
        $html = \Drupal::service('renderer')->renderPlain($build);

        $response = new AjaxResponse();
        $response->addCommand(new ReplaceCommand('#partner-order-list', $html));
        return ($response);
    }

    public function liveOrders(Request $request) {
        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders();
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\LiveOrderSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_order_grid_container user_order_history">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.report.liveorders';
        $build['#attached']['drupalSettings']['food'] = array(
            'liveOrdersCallbackUrl' => Url::fromRoute('food.partner.report.liveorderscallback')->toString(),
        );

        return $build;
    }

    public function liveOrdersCallback(Request $request) {
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
        $build = array(
        );

        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Client Address'), 'field' => 'user_address'),
            array('data' => $this->t('Client Phone'), 'field' => 'user_phone'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Status'), 'field' => 'status'),
            array('data' => $this->t('Delivery Mode'), 'field' => 'delivery_mode'),
            array('data' => $this->t('Payment Mode'), 'field' => 'payment_mode'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'restaurant_name'),
            array('data' => $this->t('Restaurant Contact'), 'field' => 'restaurant_contact'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('Adjusted Amount'), 'field' => 'adjustment'),
            array('data' => $this->t(''), 'field' => 'processed_by'),
            array('data' => $this->t(''), 'field' => 'add_chargeback'),
            array('data' => $this->t(''), 'field' => 'add_adjustment'),
        );

        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = $request->query->get('order_id');
                $user_name = $request->query->get('user_name');
                $user_phone = $request->query->get('user_phone');
                $user_address = $request->query->get('user_address');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                $order_amount = $request->query->get('order_amount');
                $delivery_mode = $request->query->get('delivery_mode');
                $payment_mode = $request->query->get('payment_mode');              
                $order_statuses = $request->query->get('order_statuses');
               
                //var_dump($order_status);
                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                if (!empty($order_id)) {
                    $query = $query
                        ->condition('order_id', $order_id);
                }
                
                if (!empty($user_name)) {
                    $query = $query
                        ->condition('user_name', '%' . db_like($user_name) . '%', 'LIKE');
                }
                
                if (!empty($user_phone)) {
                    $query = $query
                        ->condition('user_phone', '%' . db_like($user_phone) . '%', 'LIKE');
                }
                
                if (!empty($user_address)) {
                    $query = $query
                        ->condition('user_address', '%' . db_like($user_address) . '%', 'LIKE');
                }
                
                if (!empty($start_date)) {
                    $start_date = strtotime($start_date);                   
                    $query = $query
                        ->condition('created_time', $start_date * 1000, '>=');
                }
                
                if (!empty($end_date)) {
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    $query = $query
                        ->condition('created_time', $end_date * 1000, '<');
                }
                
                if (!empty($order_amount)) {
                    $query = $query
                        ->condition('net_amount', $order_amount, '>=');
                }
                
                if (!empty($delivery_mode)) {
                    $query = $query
                        ->condition('delivery_mode', $delivery_mode);
                }
                
                if (!empty($payment_mode)) {
                    $query = $query
                        ->condition('payment_mode', $payment_mode);
                }
                
                if (!empty($order_statuses) && count($order_statuses) > 0) {
                    $query = $query
                        ->condition('status', $order_statuses, 'IN');
                }

                return($query);
            }
        ]);

        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);

        $uids = [];
        foreach($rows as $row) {
            if(!empty($row->processed_by) && !in_array($row->processed_by, $uids)) {
                $uids[] = $row->processed_by;
            }
        }
        $users = \Drupal\user\Entity\User::loadMultiple($uids);
        
        foreach ($rows as $index => &$row) {
            $adjustment_total = 0;

            $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }
                    
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
            \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);

            if(!empty($adjustment_rows)){
                foreach ($adjustment_rows as $key => $value) {
                   $adjustment_total += round($value->amount, 2);
                }
            }


            $user = !empty($row->processed_by) ? $users[$row->processed_by] : NULL;

            $processedBy = '';
            $order_status_classes = [];
            if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed) {
                $order_status_classes[] = 'order-status-confirmed';
                $order_status = $this->t('Confirmed');
            } elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled) {
                $order_status_classes[] = 'order-status-cancelled';
                $order_status = $this->t('Cancelled');
            } else {
                $order_status_classes[] = 'order-status-submitted';
                $order_status = $this->t('Pending');
            }
            
            $order_processor_classes = [];
            if($user != NULL) {
                if($user->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name)) {
                    $order_processor_classes[] = 'order-processor-platform';
                } elseif($user->hasRole(\Drupal\food\Core\RoleController::Partner_Role_Name)) {
                    $order_processor_classes[] = 'order-processor-partner';
                } else {
                    $order_processor_classes[] = 'order-processor-user';
                }               
            }
            $order_status = new FormattableMarkup('<div class="' . implode(" ", $order_status_classes) . '">@status</div>', ['@status' => $order_status != NULL ? $order_status : $this->t('&nbsp;&nbsp;')]);
            $processedBy = new FormattableMarkup('<div class="' . implode(" ", $order_processor_classes) . '">@name</div>', ['@name' => $user != NULL ? $user->getDisplayName() : $this->t('&nbsp;&nbsp;')]);

            $chargeBackUrl = Url::fromRoute('food.partner.chargeback.add', ['restaurant_id' => $row->restaurant_id, 'order_id' => $row->order_id, 'user_id' => $row->user_id]);
            $chargeBackUrl->setOptions([
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
            $chargeBackLink = Link::fromTextAndUrl(t('Add Charge back'), $chargeBackUrl);
            
            $adjustmentUrl = Url::fromRoute('food.partner.adjustment.add', ['restaurant_id' => $row->restaurant_id, 'order_id' => $row->order_id, 'user_id' => $row->user_id]);
            $adjustmentUrl->setOptions([
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
            $adjustmentLink = Link::fromTextAndUrl(t('Add Adjustment'), $adjustmentUrl);
            
            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'user_address' => $row->user_address,
                    'user_phone' => $row->user_phone,
                    'amount' => $row->order_details->breakup->net_amount,
                    'status' => $order_status,
                    'delivery_mode' => $row->delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery ? "Delivery" : "Pickup",
                    'payment_mode' => $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? "Cash On delivery" : "Card",
                    'restaurant_name' => $row->restaurant->name,
                    'restaurant_contact' => $row->restaurant->phone_number,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    'adjustment' => $adjustment_total,
                    'processed_by' => $processedBy,
                    'add_chargeback' => '',//$chargeBackLink->toString(),
                    'add_adjustment' => ($isAdmin && ($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed || $row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled)) ? $adjustmentLink->toString() : '',
                ),
                'order' => $row
            );
        }
       $build['pager1'] = array(
            '#type' => 'pager'
        );
        //Generate the table.
        $build['table'] = array(
            '#theme' => 'food_partner_order_grid',
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
        $response->addCommand(new HtmlCommand('.food_partner_order_grid_container', $html));
        return ($response);
    }

    public function statement(Request $request) {
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\StatementSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_order_statement_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.report.statement';
        $build['#attached']['drupalSettings']['food'] = array(
            'statementCallbackUrl' => Url::fromRoute('food.partner.report.statementcallback')->toString(),
        );

        return $build;
    }

    public function statementCallback(Request $request) {
        $build = array(
        );

        $order_header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Sub total'), 'field' => 'items_total_amount'),
            array('data' => $this->t('Discount by me'), 'field' => 'restaurant_discount_amount'),
            array('data' => $this->t('Discount by FOD'), 'field' => 'platform_discount_amount'),
            array('data' => $this->t('Tip'), 'field' => 'tip_amount'),
            array('data' => $this->t('Tax'), 'field' => 'tax_amount'),
            array('data' => $this->t('Amount'), 'field' => 'net_amount'),
            array('data' => $this->t('Adjustment'), 'field' => 'adjustment'),
            array('data' => $this->t('FOD Commission'), 'field' => 'platform_commission_amount'),
            array('data' => $this->t('CC Processing Fee'), 'field' => 'payment_mode_processing_fee_amount'),
            array('data' => $this->t('Debit/Credit'), 'field' => 'debit_credit'),
        );

        $cancel_order_header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Sub total'), 'field' => 'items_total_amount'),
            array('data' => $this->t('Discount by me'), 'field' => 'restaurant_discount_amount'),
            array('data' => $this->t('Discount by FOD'), 'field' => 'platform_discount_amount'),
            array('data' => $this->t('Tip'), 'field' => 'tip_amount'),
            array('data' => $this->t('Tax'), 'field' => 'tax_amount'),
            array('data' => $this->t('Amount'), 'field' => 'net_amount'),
            array('data' => $this->t('Comment'), 'field' => 'comment'),
        );        

        $deposit_header = array(
            array('data' => $this->t('Deposit Id'), 'field' => 'did'),
            array('data' => $this->t('Restaurant'), 'field' => 'restaurant_id'),
            array('data' => $this->t('Transaction Id'), 'field' => 'transaction_id'),
            array('data' => $this->t('Date'), 'field' => 'deposit_date'),
            // array('data' => $this->t('Comment'), 'field' => 'comment'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
        );

        $mulval = 1;
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        if(!$currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name)) {
            $mulval = -1 ;
        }
        $cancel_order_row = array();
        $summary_row = array();
        $restaurant_ids = array();
        $count = 1;
        $count_total = 1;
        $order_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $order_header,
            'pageSize' => 10,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = $request->query->get('order_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                
                $query->distinct('fo.order_id');
                $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                $or = db_or();
                $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                $query = $query->condition($or);
                $query = $query
                    ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');

                
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('fo.restaurant_id', $restaurant_id);
                    }

                    if (!empty($order_id)) {
                        $query = $query
                            ->condition('fo.order_id', $order_id);
                    }                    

                
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('fo.created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }
                
                return($query);
            }
        ]);

        $cancel_order_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $cancel_order_header,
            'pageSize' => 10,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = $request->query->get('order_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled);
                
                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                if (!empty($order_id)) {
                    $query = $query
                        ->condition('order_id', $order_id);
                }                    

                
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }

                return($query);
            }
        ]);

        $summary_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $order_header,
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = $request->query->get('order_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                
                $query->distinct('fo.order_id');
                $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                $or = db_or();
                $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                $query = $query->condition($or);
                $query = $query
                    ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');


                if(!empty($order_id) && (!empty($restaurant_id) || empty($restaurant_id))){
                        $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
                        if(!empty($order) && $order->restaurant_id){
                            if((!empty($restaurant_id) && $restaurant_id == $order->restaurant_id) || (empty($restaurant_id))){
                                $query = $query->condition('fo.restaurant_id', $order->restaurant_id);                                
                            }else{
                                $query = $query->condition('fo.restaurant_id', 0);
                            }
                        }
                }else{
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('fo.restaurant_id', $restaurant_id);
                    }

                    if (!empty($order_id)) {
                        $query = $query
                            ->condition('fo.order_id', $order_id);
                    }                    
                }

                
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('fo.created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }
            
                return($query);
            }
        ]);

/******************Total Summary Start ******************/
        $summary_rows_total = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $order_header,
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = '';
                $start_date = '';
                $end_date = '';
                
                $query->distinct('fo.order_id');
                $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                $or = db_or();
                $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                $query = $query->condition($or);
                $query = $query
                    ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');

             if(!empty($order_id) && (!empty($restaurant_id) || empty($restaurant_id))){
                        $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
                        if(!empty($order) && $order->restaurant_id){
                            if((!empty($restaurant_id) && $restaurant_id == $order->restaurant_id) || (empty($restaurant_id))){
                                $query = $query->condition('fo.restaurant_id', $order->restaurant_id);                                
                            }else{
                                $query = $query->condition('fo.restaurant_id', 0);
                            }
                        }
                }else{
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('fo.restaurant_id', $restaurant_id);
                    }

                   
                }

                
                
            
                return($query);
            }
        ]);


         $total_order_header_till_now = new \stdClass();
        $total_order_header_till_now->count = "Count";
        $total_order_header_till_now->gross_subtotal = "Subtotal";
        $total_order_header_till_now->total_cash = "Total Cash";
        $total_order_header_till_now->total_cc = "Total CC";
        $total_order_header_till_now->total_tip = "Tip";
        $total_order_header_till_now->total_tax = "Tax";
        $total_order_header_till_now->net_sales = "Net Sales";
        $total_order_header_till_now->partner_discount = "Vendor Discount";
        $total_order_header_till_now->platform_discount = "FOD Discount";
        $total_order_header_till_now->total_commission = "FOD Commission";
        $total_order_header_till_now->total_processing_fee_amount = "CC Processing Fee ";
        $total_order_header_till_now->total_adjustment = "Total Adjustment";
        $total_order_header_till_now->total_debit_credit = "Total Debit Credit ";
        $total_order_row_till_now = new \stdClass();        
        $total_order_row_till_now->count_total = $total_order_row_till_now->gross_subtotal = $total_order_row_till_now->partner_discount = $total_order_row_till_now->platform_discount = $total_order_row_till_now->total_tip = $total_order_row_till_now->total_tax = $total_order_row_till_now->net_sales = $total_order_row_till_now->total_cc = $total_order_row_till_now->total_cash = $total_order_row_till_now->total_commission = $total_order_row_till_now->total_credit_or_debit = $total_order_row_till_now->total_processing_fee_amount = $total_order_row_till_now->total_debit_credit = $total_order_row_till_now->total_adjustment = 0;
          
        foreach ($summary_rows_total as $index => &$row) {
            $restaurant_ids[] = $row->restaurant_id;
            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $summary_adjustment_total_till_now = 0;

               $summary_adjustment_rows_till_now = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    $start_date = $request->query->get('start_date');
                    $end_date = $request->query->get('end_date');
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }

                  
                    
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
       
            if(!empty($summary_adjustment_rows_till_now)){
                foreach ($summary_adjustment_rows_till_now as $key => $value) {
                   $summary_adjustment_total_till_now += round($value->amount, 2);
                }
            }

            $total_order_row_till_now->total_adjustment = $total_order_row_till_now->total_adjustment + $summary_adjustment_total_till_now ;
            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $summary_adjustment_total_till_now){   
                $deb_credit = $summary_adjustment_total_till_now;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $summary_adjustment_total_till_now + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $summary_adjustment_total_till_now + $deb_credit;
                }
            }

            $total_order_row_till_now->count_total = $count_total;

            if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                $total_order_row_till_now->gross_subtotal = round($total_order_row_till_now->gross_subtotal + $row->order_details->breakup->items_total_amount, 2);
                $total_order_row_till_now->partner_discount = round($total_order_row_till_now->partner_discount + $row->order_details->breakup->restaurant_discount_amount, 2);
                $total_order_row_till_now->platform_discount = round($total_order_row_till_now->platform_discount + $row->order_details->breakup->platform_discount_amount, 2);
                $total_order_row_till_now->total_tip = round($total_order_row_till_now->total_tip + $row->order_details->breakup->tip_amount, 2);
                $total_order_row_till_now->total_tax = round($total_order_row_till_now->total_tax + $row->order_details->breakup->tax_amount, 2);
                $total_order_row_till_now->net_sales = round($total_order_row_till_now->net_sales + $row->order_details->breakup->net_amount, 2);
                
                if($row->payment_mode == 'Card'){
                    $total_order_row_till_now->total_cc = round($total_order_row_till_now->total_cc + $row->order_details->breakup->net_amount, 2);                
                }
                
                if($row->payment_mode == 'Cash on Delivery'){
                    $total_order_row_till_now->total_cash = round($total_order_row_till_now->total_cash + $row->order_details->breakup->net_amount, 2);
                }

                $total_order_row_till_now->total_commission = round($total_order_row_till_now->total_commission + $row->order_details->breakup->platform_commission_amount, 2);
                $total_order_row_till_now->total_credit_or_debit = round($total_order_row_till_now->total_credit_or_debit + $row->order_details->breakup->net_amount_due, 2);
                $total_order_row_till_now->total_processing_fee_amount = round($total_order_row_till_now->total_processing_fee_amount + $row->order_details->breakup->payment_mode_processing_fee_amount,2);
            }            
            
            $total_order_row_till_now->total_debit_credit = round($total_order_row_till_now->total_debit_credit + $deb_credit, 2);
            $count_total++;             

        }

        $total_order_row_till_now->total_debit_credit = ($total_order_row_till_now->total_debit_credit * ($mulval)); 


        /***********************Total Summary end ********************/

        $cancel_order_total_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $cancel_order_header,
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $order_id = $request->query->get('order_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                
                $query = $query->fields('fo',array('order_details'));
                $query = $query
                    ->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled);
                
                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                if (!empty($order_id)) {
                    $query = $query
                        ->condition('order_id', $order_id);
                }                    

                
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }

                return($query);
            }
        ]);


        /*-------------------------Order Rows Start------------------------------*/
        
        \Drupal\food\Core\OrderController::assignEntityRestaurants($order_rows);
        foreach ($order_rows as $index => &$row) {
            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';         

            $adjustment_total = 0;

            $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    $start_date = $request->query->get('start_date');
                    $end_date = $request->query->get('end_date');
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }

                    if (!empty($start_date) && !empty($end_date)) {
                        $start_date = strtotime($start_date);
                        $end_date = strtotime($end_date);
                        $end_date = strtotime('+1 day', $end_date);
                        
                        $query = $query
                            ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                    }
                    
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
            \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);

            if(!empty($adjustment_rows)){
                foreach ($adjustment_rows as $key => $value) {
                   $adjustment_total += round($value->amount, 2);
                }
            }

            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $adjustment_total){   
                $deb_credit = $adjustment_total;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $adjustment_total + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $adjustment_total + $deb_credit;
                }
            }                  
                         
            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                    'items_total_amount' => round($row->order_details->breakup->items_total_amount,2),
                    'restaurant_discount_amount' => round($row->order_details->breakup->restaurant_discount_amount,2),
                    'platform_discount_amount' => round($row->order_details->breakup->platform_discount_amount,2),
                    'tip_amount' => round($row->order_details->breakup->tip_amount,2),
                    'tax_amount' => round($row->order_details->breakup->tax_amount,2),
                    'net_amount' => round($row->order_details->breakup->net_amount,2),
                    'adjustment' => $adjustment_total,
                    'platform_commission_amount' => round($row->order_details->breakup->platform_commission_amount,2),
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    'payment_mode_processing_fee_amount'=>round($row->order_details->breakup->payment_mode_processing_fee_amount,2),
                    'debit_credit'=>($deb_credit)*($mulval),
                ),
                'order' => $row
            );

        }

        /*-------------------------Order Rows End--------------------------*/


        /*------------------------Summary Rows Start-=---------------------*/

        $total_order_header = new \stdClass();
        $total_order_header->count = "Count";
        $total_order_header->gross_subtotal = "Subtotal";
        $total_order_header->total_cash = "Net Cash";
        $total_order_header->total_cc = "Net CC";
        $total_order_header->total_tip = "Tip";
        $total_order_header->total_tax = "Tax";
        $total_order_header->net_sales = "Net Sales";
        $total_order_header->total_adjustment = "Total Adjustment";
        $total_order_header->partner_discount = "Vendor Discount";
        $total_order_header->platform_discount = "FOD Discount";
        $total_order_header->total_commission = "FOD Commission";
        $total_order_header->total_processing_fee_amount = "CC Processing Fee ";
        $total_order_header->total_debit_credit = "Total Debit Credit ";
        $total_order_row = new \stdClass();        
        $total_order_row->count = $total_order_row->gross_subtotal = $total_order_row->partner_discount = $total_order_row->platform_discount = $total_order_row->total_tip = $total_order_row->total_tax = $total_order_row->net_sales = $total_order_row->total_cc = $total_order_row->total_cash = $total_order_row->total_commission = $total_order_row->total_credit_or_debit = $total_order_row->total_processing_fee_amount = $total_order_row->total_debit_credit = $total_order_row->total_adjustment=0;

        foreach ($summary_rows as $index => &$row) {
            $restaurant_ids[] = $row->restaurant_id;
            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';

            $summary_adjustment_total = 0;

            $summary_adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    $start_date = $request->query->get('start_date');
                    $end_date = $request->query->get('end_date');
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }

                    if (!empty($start_date) && !empty($end_date)) {
                        $start_date = strtotime($start_date);
                        $end_date = strtotime($end_date);
                        $end_date = strtotime('+1 day', $end_date);
                        
                        $query = $query
                            ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                    }
                    
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);

            if(!empty($summary_adjustment_rows)){
                foreach ($summary_adjustment_rows as $key => $value) {
                   $summary_adjustment_total += round($value->amount, 2);
                }
            }
             $total_order_row->total_adjustment=  $total_order_row->total_adjustment +  $summary_adjustment_total;
            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $summary_adjustment_total){   
                $deb_credit = $summary_adjustment_total;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $summary_adjustment_total + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $summary_adjustment_total + $deb_credit;
                }
            }

            $total_order_row->count = $count;

            if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                $total_order_row->gross_subtotal = round($total_order_row->gross_subtotal + $row->order_details->breakup->items_total_amount, 2);
                $total_order_row->partner_discount = round($total_order_row->partner_discount + $row->order_details->breakup->restaurant_discount_amount, 2);
                $total_order_row->platform_discount = round($total_order_row->platform_discount + $row->order_details->breakup->platform_discount_amount, 2);
                $total_order_row->total_tip = round($total_order_row->total_tip + $row->order_details->breakup->tip_amount, 2);
                $total_order_row->total_tax = round($total_order_row->total_tax + $row->order_details->breakup->tax_amount, 2);
                $total_order_row->net_sales = round($total_order_row->net_sales + $row->order_details->breakup->net_amount, 2);
                
                if($row->payment_mode == 'Card'){
                    $total_order_row->total_cc = round($total_order_row->total_cc + $row->order_details->breakup->net_amount, 2);                
                }
                
                if($row->payment_mode == 'Cash on Delivery'){
                    $total_order_row->total_cash = round($total_order_row->total_cash + $row->order_details->breakup->net_amount, 2);
                }

                $total_order_row->total_commission = round($total_order_row->total_commission + $row->order_details->breakup->platform_commission_amount, 2);
                $total_order_row->total_credit_or_debit = round($total_order_row->total_credit_or_debit + $row->order_details->breakup->net_amount_due, 2);
                $total_order_row->total_processing_fee_amount = round($total_order_row->total_processing_fee_amount + $row->order_details->breakup->payment_mode_processing_fee_amount,2);
            }            
            
            $total_order_row->total_debit_credit = round($total_order_row->total_debit_credit + $deb_credit, 2);
            $count++;             

        }

        $total_order_row->total_debit_credit = ($total_order_row->total_debit_credit * ($mulval)); 


        /*-----------------------Summary Rows Start-------------------------*/


        /*---------------------Deposit Rows Start------------------------*/

        $deposit_rows = \Drupal\food\Core\OrderController::getCurrentPartnerDeposit($restaurant_ids);
        if(!empty($deposit_rows)){
            foreach ($deposit_rows as $index => &$row) {
                $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($row->restaurant_id);
                $row = array(
                    'data' => array(
                        'did' => $row->did,
                        'restaurant_id' => $restaurant->name,
                        'transaction_id' => $row->transaction_id,
                        'deposit_date' => date('d/m/Y', $row->deposit_date),
                        'amount' => round($row->amount, 2),
                        ),
                    );
            }
        }

        /*----------------------Deposit Rows End-------------------------*/


        /*---------------------Cancel Order Rows Start---------------------*/
        
        foreach ($cancel_order_rows as $index => &$row) {

            $cancel_order_row[] = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                    'payment_mode' => $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card',
                    'items_total_amount' => round($row->order_details->breakup->items_total_amount,2),
                    'restaurant_discount_amount' => round($row->order_details->breakup->restaurant_discount_amount,2),
                    'platform_discount_amount' => round($row->order_details->breakup->platform_discount_amount,2),
                    'tip_amount' => round($row->order_details->breakup->tip_amount,2),
                    'tax_amount' => round($row->order_details->breakup->tax_amount,2),
                    'net_amount' => round($row->order_details->breakup->net_amount,2),
                    'comment' => $row->order_details->cancel_comment
                ),
                'order' => $row,
            );

        }

        /*---------------------Cancel Order Rows End---------------------*/

        
        //Generate the table.
        $build['table'] = array(
            '#theme' => 'food_partner_order_statement_grid',
            '#order_header' => $order_header,
            '#order_rows' => $order_rows,
            '#total_order_header' => $total_order_header,
            '#total_order_row' => $total_order_row,
            '#total_order_header_till_now' => $total_order_header_till_now,
            '#total_order_row_till_now' => $total_order_row_till_now,
            '#deposit_header' => $deposit_header,
            '#deposit_row' => $deposit_rows,
            '#cancel_order_header' => $cancel_order_header,
            '#cancel_order_row' => $cancel_order_row,
            '#cancel_order_total_rows' => $cancel_order_total_rows,
            '#pager' => ['#type' => 'pager','#element' => 0],
            '#pager1' => ['#type' => 'pager','#element' => 1],
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );

        $html = \Drupal::service('renderer')->renderPlain($build);
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('.food_partner_order_statement_grid_container', $html));
        return ($response);
    }

    public function financialstatement(Request $request){

      $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\FinancialSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_order_statement_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.report.financial';
        $build['#attached']['drupalSettings']['food'] = array(
            'statementCallbackUrl' => Url::fromRoute('food.partner.report.financialstatementCallback')->toString(),
        );
 

        return $build;
    } 

    public function financialstatementCallback(Request $request) {
        $flag = FALSE;
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $mulval = 1;
        if(!$currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name)) {
            $mulval = -1 ;
        }
        $build = array(
        );

        $order_header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Type'), 'field' => 'payment_mode'),
            array('data' => $this->t('Sub total'), 'field' => 'items_total_amount'),
            array('data' => $this->t('Discount by me'), 'field' => 'restaurant_discount_amount'),
            array('data' => $this->t('Discount by FOD'), 'field' => 'platform_discount_amount'),
            array('data' => $this->t('Tip'), 'field' => 'tip_amount'),
            array('data' => $this->t('Tax'), 'field' => 'tax_amount'),
            array('data' => $this->t('Amount'), 'field' => 'net_amount'),
            array('data' => $this->t('Adjustment'), 'field' => 'adjustment'),
            array('data' => $this->t('CC Processing Fee'), 'field' => 'payment_mode_processing_fee_amount'),
            array('data' => $this->t('FOD Commission'), 'field' => 'platform_commission_amount'),
            array('data' => $this->t('Order Time'), 'field' => 'created_time_formatted'),
        );
        
        $adjustment_header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'name'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Description'), 'field' => 'description'),
            array('data' => $this->t('Transaction Date'), 'field' => 'transaction_date'),
        );
        
        $chargeback_header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'name'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Description'), 'field' => 'description'),
            array('data' => $this->t('Transaction Date'), 'field' => 'transaction_date'),
        );
           
        $order_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'header' => $order_header,
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request,&$flag) {
                $restaurant_id = $request->query->get('restaurant_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');
                
                $query->distinct('fo.order_id');
                $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                $or = db_or();
                $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                $query = $query->condition($or);
                $query = $query
                    ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');

                
                    if (!empty($restaurant_id)) {
                        $flag = TRUE;
                        $query = $query
                            ->condition('fo.restaurant_id', $restaurant_id);
                    }                    

                
                if (!empty($start_date) && !empty($end_date)) {
                    $flag = TRUE;
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('fo.created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }
                
                return($query);
            }
        ]);
        
        $total_order_header = new \stdClass();
        $total_order_header->count = "Count";
        $total_order_header->gross_subtotal = "Subtotal";
        $total_order_header->total_cash = "Net Cash";
        $total_order_header->total_cc = "Net CC";
        $total_order_header->total_tip = " Tip";
        $total_order_header->total_tax = " Tax";
        $total_order_header->net_sales = " Net Sales";
        $total_order_header->total_adjust = "Total Adjustment";
        $total_order_header->partner_discount = "Vendor Discount";
        $total_order_header->platform_discount = "FOD Discount";
        $total_order_header->total_commission = "FOD Commission";
        $total_order_header->total_processing_fee_amount = " CC Processing Fee ";
        $total_order_header->total_debit_credit = "Total Debit Credit ";
        $total_order_header->total_deposit = "Total Deposit";
        
        $total_order_row = new \stdClass();
        $total_order_row->count = $total_order_row->gross_subtotal = $total_order_row->partner_discount = $total_order_row->platform_discount = $total_order_row->total_tip = $total_order_row->total_tax = $total_order_row->net_sales = $total_order_row->total_cc = $total_order_row->total_cash = $total_order_row->total_commission = $total_order_row->total_credit_or_debit = $total_order_row->total_processing_fee_amount = $total_order_row->total_debit_credit = $total_order_row->total_adjust = 0;
        $restaurant_ids = array();
        $depositor_total = 0;
        $count = 1;
        \Drupal\food\Core\OrderController::assignEntityRestaurants($order_rows);

        foreach ($order_rows as $index => &$row) {
            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';
            $restaurant_ids[] = $row->restaurant_id;
            $adjustment_total = 0;

            $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    $start_date = $request->query->get('start_date');
                    $end_date = $request->query->get('end_date');
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }

                    if (!empty($start_date) && !empty($end_date)) {
                        $start_date = strtotime($start_date);
                        $end_date = strtotime($end_date);
                        $end_date = strtotime('+1 day', $end_date);
                        
                        $query = $query
                            ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                    }
                    
                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
            \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);

            if(!empty($adjustment_rows)){
                foreach ($adjustment_rows as $key => $value) {
                   $adjustment_total += round($value->amount, 2);
                }
            }
             $total_order_row->total_adjust =$total_order_row->total_adjust +  $adjustment_total;
            
            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $adjustment_total){   
                $deb_credit = $adjustment_total;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $adjustment_total + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $adjustment_total + $deb_credit;
                }
            }

            $total_order_row->count = $count;

            if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                $total_order_row->gross_subtotal = round($total_order_row->gross_subtotal + $row->order_details->breakup->items_total_amount, 2);
                $total_order_row->partner_discount = round($total_order_row->partner_discount + $row->order_details->breakup->restaurant_discount_amount, 2);
                $total_order_row->platform_discount = round($total_order_row->platform_discount + $row->order_details->breakup->platform_discount_amount, 2);
                $total_order_row->total_tip = round($total_order_row->total_tip + $row->order_details->breakup->tip_amount, 2);
                $total_order_row->total_tax = round($total_order_row->total_tax + $row->order_details->breakup->tax_amount, 2);
                $total_order_row->net_sales = round($total_order_row->net_sales + $row->order_details->breakup->net_amount, 2);
                
                if($row->payment_mode == 'Card'){
                    $total_order_row->total_cc = round($total_order_row->total_cc + $row->order_details->breakup->net_amount, 2);                
                }
                
                if($row->payment_mode == 'Cash on Delivery'){
                    $total_order_row->total_cash = round($total_order_row->total_cash + $row->order_details->breakup->net_amount, 2);
                }

                $total_order_row->total_commission = round($total_order_row->total_commission + $row->order_details->breakup->platform_commission_amount, 2);
                $total_order_row->total_credit_or_debit = round($total_order_row->total_credit_or_debit + $row->order_details->breakup->net_amount_due, 2);
                $total_order_row->total_processing_fee_amount = round($total_order_row->total_processing_fee_amount + $row->order_details->breakup->payment_mode_processing_fee_amount,2);
            }            
            
            $total_order_row->total_debit_credit = round($total_order_row->total_debit_credit + $deb_credit, 2);
            $count++;
            
            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'payment_mode' => $row->payment_mode,
                    'items_total_amount' => round($row->order_details->breakup->items_total_amount,2),
                    'restaurant_discount_amount' => round($row->order_details->breakup->restaurant_discount_amount,2),
                    'platform_discount_amount' => round($row->order_details->breakup->platform_discount_amount,2),
                    'tip_amount' => round($row->order_details->breakup->tip_amount,2),
                    'tax_amount' => round($row->order_details->breakup->tax_amount,2),
                    'net_amount' => round($row->order_details->breakup->net_amount,2),
                    'adjustment' => $adjustment_total,
                    'payment_mode_processing_fee_amount'=>round($row->order_details->breakup->payment_mode_processing_fee_amount,2),
                    'platform_commission_amount' => round($row->order_details->breakup->platform_commission_amount,2),
                    'created_time_formatted' => $row->derived_fields->created_time_formatted,
                ),
                'order' => $row
            );
        }        
                   
            $total_order_row->total_debit_credit = ($total_order_row->total_debit_credit*($mulval)); 

        $deposit_rows = \Drupal\food\Core\OrderController::getCurrentPartnerDeposit($restaurant_ids);

        if(!empty($deposit_rows)){
            foreach ($deposit_rows as $key => $value) {
                $depositor_total += $value->amount;
            }
        }        
        
        $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'header' => $adjustment_header,
                'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');

                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }
                
                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }

                return($query);
            }
        ]);
        \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);
        foreach ($adjustment_rows as $index => &$row) {

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'name' =>  $row->restaurant->name,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'transaction_date' => $row->transaction_date,
                ),
                'order' => $row
            );
        }
        
        $chargeback_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Chargeback, [
                'header' => $chargeback_header,
                'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                $start_date = $request->query->get('start_date');
                $end_date = $request->query->get('end_date');

                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                if (!empty($start_date) && !empty($end_date)) {
                    $start_date = strtotime($start_date);
                    $end_date = strtotime($end_date);
                    $end_date = strtotime('+1 day', $end_date);
                    
                    $query = $query
                        ->condition('created_time', array($start_date * 1000, $end_date * 1000), 'BETWEEN');
                }
                
                return($query);
            }
        ]);
        \Drupal\food\Core\OrderController::assignEntityRestaurants($chargeback_rows);
        foreach ($chargeback_rows as $index => &$row) {

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'name' =>  $row->restaurant->name,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'transaction_date' => $row->transaction_date,
                ),
                'order' => $row
            );
        }
       
        //Generate the table.
               $total_order_row->start_date = $request->query->get('start_date');
                $total_order_row->end_date = $request->query->get('end_date');
            $restaurant_id = $request->query->get('restaurant_id');
         $restaurant_name = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
         $total_order_row->restaurant_name = $restaurant_name->name;
         $total_order_row->current_time = date('Y-m-d H:i:sa');
        
        if(!empty($restaurant_id) && !empty($order_rows)){
            $build['table'] = array(
                '#theme' => 'food_partner_finance_order_statement_grid',
                '#order_header' => $order_header,
                '#order_rows' => $order_rows,
                '#total_order_header' => $total_order_header,
                '#total_order_row' => $total_order_row,
                '#depositor_total' => $depositor_total,
                '#attributes' => array(
                    'class' => 'food-entity-list-table',
                ),
            );
            
        }else{
            $build['empty'] = array(
                '#markup' => $flag ? 'No Record Found!' : 'Search Record.',
            );
        }

        $html = \Drupal::service('renderer')->renderPlain($build);

        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('.food_partner_order_statement_grid_container', $html));

        return ($response);
    }


    public function currentBalance(Request $request){
        $rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders();
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\CurrentBalanceSearchForm');


        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_current_balance_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.report.currentbalance';
        $build['#attached']['drupalSettings']['food'] = array(
            'currentBalanceCallbackUrl' => Url::fromRoute('food.partner.report.currentbalancecallback')->toString(),
        );

        return $build;
    }

    public function currentBalanceCallback(Request $request) {
        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
        $mulval = 1;
        if(!$isAdmin) {
            $mulval = -1 ;
        }
        $build = array();

        $total_deposit_header = array(
            array('data' => $this->t('Date'), 'field' => 'deposit_date'),
            array('data' => $this->t('Transaction Id'), 'field' => 'transaction_id'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Comment'), 'field' => 'comment'),
            array('data' => $this->t('Depositer'), 'field' => 'depositor_uid'),
        );
        
        $order_rows = \Drupal\food\Core\OrderController::getCurrentPartnerOrders([
            'pageSize' => 0,
            'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');
                
                $query->distinct('fo.order_id');
                $query->leftJoin('food_order_charge','foc','fo.order_id = foc.order_id');
                $or = db_or();
                $or->condition('foc.charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment);
                $or->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Confirmed);
                $query = $query->condition($or);
                $query = $query
                    ->condition('fo.status', array(\Drupal\food\Core\Order\OrderStatus::Confirmed, \Drupal\food\Core\Order\OrderStatus::Cancelled), 'IN');


                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('fo.restaurant_id', $restaurant_id);
                    }                    
            
                return($query);
            }
        ]);

        $total_deposit_row = [];
        $account_number = '';
        $depositor_total = 0;
        
        if (!empty($request->query->get('restaurant_id'))) {
            $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($request->query->get('restaurant_id'));
            $account_number = $restaurant->settlement_payment_settings->wire_transfer_payment_settings->account_number;
            $deposits = \Drupal\food\Core\OrderController::getCurrentPartnerDeposit($request->query->get('restaurant_id'));
            if(!empty($deposits)){
                foreach ($deposits as $key => $value) {
                    $depositor_total += $value->amount;
                    $depositor_object = \Drupal\user\Entity\User::load($value->depositor_uid);
                    $total_deposit_row[$key] = array(
                        'data' => array(
                            'deposit_date' => date('d/m/Y', $value->deposit_date),
                            'transaction_id' => $value->transaction_id,
                            'amount' => round($value->amount, 2),
                            'comment' => $value->comment,
                            'depositor_uid' => $depositor_object->getUsername(),
                        ),
                    );
                }
            }
        }


        $total_order_row = [];
        $count = 1;
        $disable = '';
        $total = 0;
        \Drupal\food\Core\OrderController::assignEntityRestaurants($order_rows);
        foreach ($order_rows as $index => &$row) {
            $row->payment_mode = $row->payment_mode == \Drupal\food\Core\Restaurant\PaymentMode::CashOnDelivery ? 'Cash on Delivery' : 'Card';
            $adjustment_total = 0;

            $adjustment_rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'pageSize' => 0,
                'conditionCallback' => function($query) use (&$request, &$row) {
                    $restaurant_id = $request->query->get('restaurant_id');
                    
                    if (!empty($restaurant_id)) {
                        $query = $query
                            ->condition('restaurant_id', $restaurant_id);
                    }

                    $query = $query
                            ->condition('order_id', $row->order_id);

                    return($query);
                }
            ]);
            \Drupal\food\Core\OrderController::assignEntityRestaurants($adjustment_rows);

            if(!empty($adjustment_rows)){
                foreach ($adjustment_rows as $key => $value) {
                   $adjustment_total += round($value->amount, 2);
                }
            }

            if($row->status == \Drupal\food\Core\Order\OrderStatus::Cancelled && $adjustment_total){   
                $deb_credit = $adjustment_total;
            }elseif($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed && $row->payment_mode == 'Card'){
                $deb_credit =   round(((($row->order_details->breakup->platform_commission_amount+$row->order_details->breakup->payment_mode_processing_fee_amount)-$row->order_details->breakup->platform_discount_amount)-$row->order_details->breakup->net_amount),2);
                $deb_credit = $adjustment_total + $deb_credit;
            }else{
                if($row->status == \Drupal\food\Core\Order\OrderStatus::Confirmed){
                    $deb_credit =   round(($row->order_details->breakup->platform_commission_amount-$row->order_details->breakup->platform_discount_amount),2);
                    $deb_credit = $adjustment_total + $deb_credit;
                }
            }  

            $total = round($total + $deb_credit, 2);
        }
            $total = ($total*($mulval));
        
        if($total >= 0){
           $total = bcsub($total, $depositor_total,2);
        }elseif($total <= 0){
            $total = bcadd($total, $depositor_total,2);
        }

        $disable = 'deposit_amount';
        if($total >= 0){
            $disable = 'disabled';
        }

            $adminDepositUrl = Url::fromRoute('food.partner.account.deposit', array('restaurant_id' => $request->query->get('restaurant_id')));
                    $adminDepositUrl->setOptions([
                        'attributes' => [
                            'class' => ['use-ajax','btn','btn-primary', $disable],
                            'data-dialog-type' => 'modal',
                            'data-dialog-options' => Json::encode([
                                'width' => 700,
                            ]),
                        ]
                    ]);
            $adminDepositLink = Link::fromTextAndUrl(t('Deposit '.$total.' into account'), $adminDepositUrl);            
        
        $build['content']['table'] = array(
            '#theme' => 'food_partner_balance_grid',
            '#header' => $total_deposit_header,
            '#rows' => $total_deposit_row,
            '#total' => $total,
            '#depositLink' => $adminDepositLink->toString(),
            '#account_number' => $account_number,
            '#attributes' => array(
                'class' => 'food-entity-list-table',
            ),
        );

        //Finally add the pager.
        // $build['content']['pager'] = array(
        //     '#type' => 'pager'
        // );

        $html = \Drupal::service('renderer')->renderPlain($build['content']);
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('.food_partner_current_balance_grid_container', $html));
        return ($response);
    }

    public function orderDetail() {
        $order_id = \Drupal::routeMatch()->getParameter('order_id');
        $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);

        $order->restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);
        \Drupal\food\Core\UserController::validatePartnerOrderAccess($order, $order->restaurant);


        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $ispartner = $currentUser->hasRole(\Drupal\food\Core\RoleController::Partner_Role_Name);

        $build = array(
            '#markup' => '',
            '#theme' => 'food_partner_order_detail',
            'additionalData' => [
                'order' => $order,
                'currentUserHasPartnerRole' => $ispartner,
            ],
            //'#attached' => ['library' => ['food/form.user.cartblock', 'food/form.user.addcartitemform']],
        );

        return ($build);
    }
    
    public function chargeBack(Request $request) {
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\ChargeBackSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_order_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.chargeback';
        $build['#attached']['drupalSettings']['food'] = array(
            'chargebackCallbackUrl' => Url::fromRoute('food.partner.chargebackcallback')->toString(),
        );

        return $build;
    }
    
    public function chargeBackCallback(Request $request) {
        $build = array(
        );

        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'name'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Description'), 'field' => 'description'),
            array('data' => $this->t('Transaction Date'), 'field' => 'transaction_date'),
        );

        $rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Chargeback, [
                'header' => $header,
                'conditionCallback' => function($query) use (&$request) {
                $restaurant_id = $request->query->get('restaurant_id');

                if (!empty($restaurant_id)) {
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                return($query);
            }
        ]);
        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        
        foreach ($rows as $index => &$row) {

            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'name' =>  $row->restaurant->name,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'transaction_date' => $row->transaction_date,
                ),
                'order' => $row
            );
        }

        //Generate the table.
        $build['table'] = array(
            '#theme' => 'food_partner_order_grid',
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
        $response->addCommand(new HtmlCommand('.food_partner_order_grid_container', $html));
        return ($response);
    }

    public function adjustment(Request $request) {
        $build['search_form'] = \Drupal::formBuilder()->getForm('Drupal\food\Form\Partner\AdjustmentSearchForm');
        $build['report_container'] = array(
            '#type' => 'markup',
            '#markup' => '<div class="food_partner_order_grid_container">&nbsp;</div>',
        );

        $build['#attached']['library'][] = 'food/form.partner.adjustment';
        $build['#attached']['drupalSettings']['food'] = array(
            'adjustmentCallbackUrl' => Url::fromRoute('food.partner.adjustmentcallback')->toString(),
        );

        return $build;
    }
    
    public function adjustmentCallback(Request $request) {
        $flag = FALSE;
        $build = array(
        );

        $header = array(
            array('data' => $this->t('Order Id'), 'field' => 'order_id', 'sort' => 'desc'),
            array('data' => $this->t('Restaurant Name'), 'field' => 'name'),
            array('data' => $this->t('Amount'), 'field' => 'amount'),
            array('data' => $this->t('Description'), 'field' => 'description'),
            array('data' => $this->t('Transaction Date'), 'field' => 'transaction_date'),
        );

        $rows = \Drupal\food\Core\OrderController::getOrderCharges(\Drupal\food\Core\Order\OrderChargeType::Adjustment, [
                'header' => $header,
                'conditionCallback' => function($query) use (&$request,&$flag) {
                $restaurant_id = $request->query->get('restaurant_id');

                if (!empty($restaurant_id)) {
                    $flag = TRUE;
                    $query = $query
                        ->condition('restaurant_id', $restaurant_id);
                }

                return($query);
            }
        ]);
        \Drupal\food\Core\OrderController::assignEntityRestaurants($rows);
        
        foreach ($rows as $index => &$row) {
            
           $order = \Drupal\food\Core\OrderController::getOrderByOrderId($row->order_id);
          
      
           $row->order_details =$order->order_details;
            $row = array(
                'data' => array(
                    'order_id' => $row->order_id,
                    'name' =>  $row->restaurant->name,
                    'amount' => $row->amount,
                    'description' => $row->description,
                    'transaction_date' => $row->transaction_date,
                ),
                'order' => $row
            );
        }
        
        if(!empty($rows)){

            //Generate the table.
            $build['table'] = array(
                '#theme' => 'food_partner_order_grid',
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
        
        }else{
            $build['empty'] = array(
                    '#markup' => $flag ? 'No Record Found!' : 'Search Record.',
            );
        }

        $html = \Drupal::service('renderer')->renderPlain($build);
        $response = new AjaxResponse();
        $response->addCommand(new HtmlCommand('.food_partner_order_grid_container', $html));
        return ($response);
    }
        
} 
