<?php

namespace Drupal\food\Form\User;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Routing;
use Imbibe\Util\PhpHelper;

class OrderList extends ControllerBase {

    public function show() {
        $user_id = \Drupal::routeMatch()->getParameter('user');
        $orders = \Drupal\food\Core\OrderController::getOrdersByUserId($user_id);


        $userLastOrder = \Drupal\food\Core\OrderController::getOrdersByUserId(\Drupal::currentUser()->id(),[
            'pageSize' => 1,
            'conditionCallback' => function($query) {                
                $query->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled, '!=');            
                return($query);
            }
        ]);

        $user_last_order_id = 0;
        $user_last_order_review_id = 0;

        if(!empty($userLastOrder[0]) && !empty($userLastOrder[0]->order_details->items)){
            $user_last_order_id = $userLastOrder[0]->order_id;
            $user_last_order_review = \Drupal\food\Core\ReviewController::getCurrentUserReviewByOrderId($user_last_order_id);
            if(!empty($user_last_order_review) && isset($user_last_order_review[0]->rating_id)){
                $user_last_order_review_id = $user_last_order_review[0]->rating_id;
            }
        }

		\Drupal\food\Core\OrderController::assignEntityRestaurants($orders);
        foreach ($orders as $order) {
            $order->created_time = date("F j, Y, g:i a", strtotime($order->created_time));
            $order->confirmation_link = Url::fromRoute('food.cart.order.confirmation', ['order_id' => $order->order_id]);
            
            $user_review = \Drupal\food\Core\ReviewController::getCurrentUserReviewByOrderId($order->order_id);
            
            if(empty($user_review)){
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
                
                $order->review_link = Link::fromTextAndUrl(t('Add Review'), $addReviewUrl);        
            }
             $reorderUrl = Url::fromRoute('food.previous.order.redirect',['order_id' => $order->order_id]);
             $reorderUrl->setOptions([
                 'attributes' => [
                   'class' => ['btn btn-success'],
                   'role' => 'button',
                 ],
             ]);
             $order->reorder_link = Link::fromTextAndUrl(t('Repeat'), $reorderUrl);

            $orderRating = \Drupal\food\Core\ReviewController::getAverageRatingByOrderId($order->order_id);

            if(isset($orderRating['average_rating']) && $orderRating['average_rating']){
                $order->rating = round($orderRating['average_rating']);
            }
        }

        $build = array(
            '#markup' => '',
            '#theme' => 'food_user_order_list',
            'additionalData' => [
                'orders' => $orders,
            ],
            //'#attached' => ['library' => ['food/form.user.cartblock', 'food/form.user.addcartitemform']],
            '#attached' => [
                'library' => ['food/form.user.orderlist'],
                'drupalSettings' => [
                    'food' => [
                        'user' => [
                            'user_previous_order_cart_link' => Url::fromRoute('food.previous.order.redirect', ['order_id' => $user_last_order_id])->toString(),
                            'user_last_order_id' => $user_last_order_id,
                            'user_last_order_review_id' => $user_last_order_review_id,
                        ],
                    ]
                ]
            ],
        );
		
		$build['#cache']['max-age'] = 0;

        return ($build);
    }

}
