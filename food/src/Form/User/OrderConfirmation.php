<?php

namespace Drupal\food\Form\User;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Routing;
use Imbibe\Util\PhpHelper;
use Drupal\user\Entity\User;
use Drupal\food\Core\RoleController;

class OrderConfirmation extends ControllerBase {

public function show() {
        $currentUser = User::load(\Drupal::currentUser()->id());
        $isAdmin = $currentUser->hasRole(RoleController::Administrator_Role_Name);
        $isPartner = $currentUser->hasRole(RoleController::Partner_Role_Name);
        $order_id = \Drupal::routeMatch()->getParameter('order_id');
        
        $order = \Drupal\food\Core\OrderController::getOrderByOrderId($order_id);
        $order->restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($order->restaurant_id);

        \Drupal\food\Core\UserController::validateUserOrderAccess($order, $order->restaurant);

        if($order->restaurant->owner_user_id == \Drupal::currentUser()->id()){
                $this->updateAdjusmentNotification($order_id, $order->restaurant->restaurant_id, TRUE);
        }

        if($isAdmin){
            $this->updateCanelOrderNotification($order, 'admin');
            $this->updateVendorSubuserOrderNotification($order, 'admin');
        }elseif($isPartner && $order->restaurant->owner_user_id == \Drupal::currentUser()->id()){
            $this->updateCanelOrderNotification($order, 'owner');
            $this->updateVendorSubuserOrderNotification($order, 'owner');
        }

        if($order->status == \Drupal\food\Core\Order\OrderStatus::Submitted) {
            $orderCancelCommentUrl = Url::fromRoute('food.user.order.cancel.comment.add', ['restaurant_id' => $order->restaurant_id, 'order_id' => $order->order_id]);
            $orderCancelCommentUrl->setOptions([
                'attributes' => [
                  'class' => ['use-ajax'],
                  'data-dialog-type' => 'modal',
                  'data-dialog-options' => Json::encode([
                    'width' => 700,
                  ]),
                  'role' => 'button',
                ],
            ]);

            $order->orderCancellationLink = Link::fromTextAndUrl(t('Cancel Order'), $orderCancelCommentUrl)->toString();

            // $orderCancellationUrl = Url::fromRoute('food.cart.order.cancel', ['restaurant_id' => $order->restaurant_id, 'order_id' => $order->order_id]);
            // $orderCancellationUrl->setOptions([
            //  'attributes' => [
            //      'class' => ['food-order-cancel-button'],
            //  ]
            // ]);
            // $orderCancellationLink = Link::fromTextAndUrl($this->t('Cancel Order'), $orderCancellationUrl);
            // $order->orderCancellationLink = $orderCancellationLink->toString();

            $addReviewUrl = Url::fromRoute('food.review.add.form',['order_id' => $order->order_id]);
            $addReviewUrl->setOptions([
                'query' => ['destination' => $_SERVER['REQUEST_URI']],
                'attributes' => [
                  'class' => ['use-ajax','btn btn-success'],
                  'data-dialog-type' => 'modal',
                  'data-dialog-options' => Json::encode([
                    'width' => 700,
                  ]),
                  'role' => 'button',
                ],
            ]);
            $order->orderReviewLink = Link::fromTextAndUrl(t('Add Review'), $addReviewUrl)->toString();
        }

        $orderPageLink = Url::fromRoute('food.user.order.list',['user' => \Drupal::currentUser()->id()]);
        $orderPageLink->setOptions([
            'attributes' => ['target' => '_blank'],
        ]);
        $order->orderPageLink = Link::fromTextAndUrl(t('My Order'), $orderPageLink)->toString();
        
        $build = array(
            '#markup' => '',
            '#theme' => 'food_user_order_confirmation',
            'additionalData' => [
                'order' => $order,
            ],
            //'#attached' => ['library' => ['food/form.user.cartblock', 'food/form.user.addcartitemform']],
        );
        
        return ($build);
    }

    public function updateAdjusmentNotification($order_id, $restaurant_id, $status = FALSE){
        if(!db_field_exists('food_order_charge','notification')){
            return;
        }
        if($order_id && $restaurant_id && $status){
            $entity = array(
                'notification' => 0,
            );

            db_update('food_order_charge')
            ->fields($entity)
            ->condition('restaurant_id', $restaurant_id)
            ->condition('order_id', $order_id)
            ->condition('charge_type', \Drupal\food\Core\Order\OrderChargeType::Adjustment)
            ->execute();                
        }
    }

    public function updateCanelOrderNotification($order, $user = NULL){
        if(!db_field_exists('food_order','notification')){
            return;
        }
        if(!empty($order) && $user != NULL){
            if(isset($order->notification) && $order->notification != NULL){
                $notification = json_decode($order->notification);
                if(isset($notification->customer_cancel_order->{$user})){
                    $notification->customer_cancel_order->{$user} = 0;
                    db_update('food_order')
                    ->fields(array('notification' => json_encode($notification)))
                    ->condition('order_id', $order->order_id)
                    ->execute();
                }
            }
        }
    }

    public function updateVendorSubuserOrderNotification($order, $user = NULL){
        if(!db_field_exists('food_order','notification')){
            return;
        }
        if(!empty($order) && $user != NULL){
            if(isset($order->notification) && $order->notification != NULL){
                $notification = json_decode($order->notification);
                if(isset($notification->vendor_subuser_cancel_order->{$user})){
                    $notification->vendor_subuser_cancel_order->{$user} = 0;
                    db_update('food_order')
                    ->fields(array('notification' => json_encode($notification)))
                    ->condition('order_id', $order->order_id)
                    ->execute();
                }
            }
        }
    }   
}
