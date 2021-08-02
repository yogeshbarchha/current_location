<?php

namespace Drupal\food\Form\Restaurant;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;

class RestaurantList extends ControllerBase {

    public function page() {
		$block = \Drupal\block\Entity\Block::load('restaurantfilterform');
		$restaurant_filter_form_html = \Drupal::entityTypeManager()
			->getViewBuilder('block')
			->view($block);
		
		$userLastOrder = \Drupal\food\Core\OrderController::getOrdersByUserId(\Drupal::currentUser()->id(),[
            'pageSize' => 1,
            'conditionCallback' => function($query) {                
                $query->condition('fo.status', \Drupal\food\Core\Order\OrderStatus::Cancelled, '!=');            
                return($query);
            }
        ]);

        if(!empty($userLastOrder) && isset($userLastOrder[0])){
            \Drupal\food\Core\OrderController::assignEntityRestaurants($userLastOrder);

            foreach ($userLastOrder as $order) {
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
                $order->reorder_link = Link::fromTextAndUrl(t('Reorder'), $reorderUrl);


                $orderRating = \Drupal\food\Core\ReviewController::getAverageRatingByOrderId($order->order_id);

                if(isset($orderRating['average_rating']) && $orderRating['average_rating']){
                    $order->rating = round($orderRating['average_rating']);
                }                
            }            
        }
			
		$build = array(
			'#markup' => '',
			'#restaurant_filter_form_html' => $restaurant_filter_form_html,
			'#user_last_order' => $userLastOrder,
			'#theme' => 'food_restaurant_search_page',
		);

		$build['#attached']['library'][] = 'food/form.restaurant.restaurantlist';
        $build['#attached']['drupalSettings']['food'] = array(
			'restaurantPerformSearchUrl' => Url::fromRoute('food.search.restaurants.perform')->toString(),
        );
		$build['#cache']['max-age'] = 0;
		
        return ($build);  
    }

    public function search() {
        $search_params = \Imbibe\Json\JsonHelper::deserializeObject($_GET['search_params'], '\Drupal\food\Core\Cart\SearchParams');
        $restaurants = \Drupal\food\Core\RestaurantController::searchRestaurantsBySearchParams($search_params);
		
		foreach($restaurants as $restaurant) {
    		$rating = array('average_rating' => 0,'rating_number' => 0);
    		$total_points = 0;
			$reviews = \Drupal\food\Core\ReviewController::getReviewByRestaurant($restaurant->restaurant_id);

	    	if(!empty($reviews)){
		    	foreach ($reviews as $review) {
		    		$total_points += $review->total_points;
		    	}
		    	$rating['average_rating'] = round($total_points / count($reviews));
		    	$rating['rating_number'] = count($reviews);
	    	}

            $dealUrl = Url::fromRoute('food.restaurant.deals', ['restaurant_id' => $restaurant->restaurant_id]);
            $dealUrl->setOptions([
                'attributes' => [
                    'class' => ['use-ajax', 'deals-tag'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);			
            $dealLink = Link::fromTextAndUrl($this->t('Deals'), $dealUrl);
			$restaurant->dealLink = $dealLink->toString();
			$restaurant->rating = $rating;
                        if(!empty($restaurant->image_fid)){
                        $file = \Drupal\file\Entity\File::load($restaurant->image_fid);
		        $path = $file->getFileUri();
		        $url = \Drupal\image\Entity\ImageStyle::load('restaurant_list')->buildUrl($path);
		        $restaurant->image_url =  $url;
                       }
		}

		$build = array(
			'restaurants' => $restaurants,
			'#markup' => '',
			'#theme' => 'food_restaurant_search_list',
		);
		
		$response = new AjaxResponse();
		$response->addCommand(new HtmlCommand('#restaurant-search-result', $build));
		
		if(count($restaurants) > 0) {
			$message = $this->t('We found the following restaurants.');
		} else {
			$message = $this->t('No food place open this time near this address, please filter your search.');
		}
		$response->addCommand(new HtmlCommand('#restaurant-search-message', $message));

        return ($response);
    }
	
}
