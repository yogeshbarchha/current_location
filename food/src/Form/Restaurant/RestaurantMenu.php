<?php

namespace Drupal\food\Form\Restaurant;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Routing;
use Imbibe\Util\PhpHelper;
use Drupal\image\Entity\ImageStyle;

class RestaurantMenu extends ControllerBase {

    public function search() {

        $platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();

        $reviews_res = \Drupal\food\Core\ReviewController::getReviewByRestaurant($restaurant_id);
   
            if(!empty($reviews_res)){
                foreach ($reviews_res as $review) {
                    $total_points += $review->total_points;
                }
                $rating['average_rating'] = round($total_points / count($reviews_res));
                $rating['rating_number'] = count($reviews_res);
            }
  
        $reviews = \Drupal\food\Core\ReviewController::getReviewByRestaurant($restaurant_id);
           $questions = \Drupal\food\Core\ReviewController::getAllavtiveQuestions();
            $questions_arr = [];
           foreach ($questions as $key => $value) {

           $questions_arr[$key] =   $value->question_id ;
                
           }


          $percent_answer =[];
           $questions_per= [];
             foreach ($reviews_res as $key => $revalue) {
                
                foreach (json_decode($revalue->review_details) as $key1 => $val) {
                    if(in_array( $key1 ,$questions_arr)){
                        if($val==1){
                        $questions_per[]= $key1;  

                }
                }
             }
             }
            $result_answer = [] ;



             foreach (array_count_values($questions_per) as $key => $value) {
                 $questions = \Drupal\food\Core\ReviewController::getavtiveQuestionsname($key);

                
               $result_answer[$questions->question_name]=  round($value/(count($reviews_res)) *100);           


                 } 
            


            foreach ($reviews as &$review) {

               
            $review->review_details = json_decode($review->review_details);
           

             $user = \Drupal\user\Entity\User::load($review->user_id);
            if($user){
                if(!$user->user_picture->isEmpty()){
                    $review->user_picture = $user->user_picture->view('thumbnail');                    
                }else{
                    $review->user_picture = '';
                }
            }
            if($review->user_id == \Drupal::currentUser()->id()){
                $review->name = 'My Review';                
            }

            $order = \Drupal\food\Core\OrderController::getOrderByOrderId($review->order_id);
            $review->order = $order;
        }

        $data = \Drupal\food\Core\MenuController::getRestaurantMenusWithSectionsAndItems($restaurant_id);
        $restaurant = $data['restaurant'];

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

        $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
        $isPlatform = $currentUser->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
        //Drupal doesn't allow a specific user cache context. So currently we only render edit link for platform roles.
        $allowEditingItem = $isPlatform; //|| $restaurant->owner_user_id == $currentUser->id();

        $restaurant_entity = \Drupal\food\Entity\Restaurant::load($restaurant_id);

        if (isset($restaurant_entity->field_restaurant_menu_images)) {
            $temp_values = $restaurant_entity->field_restaurant_menu_images->getValue();
            $fids = [];
            foreach ($temp_values as $temp_value) {
                $fids[] = $temp_value['target_id'];
            }

            $images = \Drupal\file\Entity\File::loadMultiple($fids);
            $restaurant->menu_images = [];
            $restaurant->menu_images_thumbnail = [];
             foreach ($images as $image) {
               $path= $image->getFileUri();
                $url = ImageStyle::load('menu_slider')->buildUrl($path);
                $url_thumbnail = ImageStyle::load('menu_slider_thumbmail')->buildUrl($path);

                $restaurant->menu_images[] = [
                    'url' => $url,
                ];
               $restaurant->menu_images_thumbnail[] = [
                    'url' => $url_thumbnail,
                ];
            }
        }

        if (isset($restaurant_entity->field_about_section_images)) {
            $temp_values = $restaurant_entity->field_about_section_images->getValue();
            $fids = [];
            foreach ($temp_values as $temp_value) {
                $fids[] = $temp_value['target_id'];
            }

            $images = \Drupal\file\Entity\File::loadMultiple($fids);
            $restaurant->about_section_images = [];
            foreach ($images as $image) {
                $restaurant->about_section_images[] = [
                    'url' => $image->url(),
                ];
            }
        }

        if (isset($restaurant_entity->field_restaurant_logo)) {
            $temp_logo_values = $restaurant_entity->field_restaurant_logo->getValue();
            
            $images_logo = \Drupal\file\Entity\File::load($temp_logo_values[0]['target_id']);
            if(isset($images_logo)){
          $restaurant->logo_section_images =$images_logo->url();
           }else{
          $restaurant->logo_section_images="";
           }
        }

        $cart = \Drupal\food\Core\CartController::getCurrentCart();

        $search_latitude = PhpHelper::getNestedValue($cart, ['search_params', 'latitude']);
        $search_longitude = PhpHelper::getNestedValue($cart, ['search_params', 'longitude']);
        if(!empty($search_latitude) && !empty($search_longitude)) {
            $restaurant->distance = \Drupal\food\Util::getDistanceBetweenCoordinates($search_latitude, $search_longitude, $restaurant->latitude, $restaurant->longitude);
            $restaurant->distance = round($restaurant->distance, 3, PHP_ROUND_HALF_UP);
        }

        $updateCart = FALSE;
        if (empty($cart->restaurant_id) || ($cart->restaurant_id != $restaurant_id && empty($cart->order_details->items))) {
            $cart->restaurant_id = $restaurant_id;
            $cart->order_details->restaurant_id = $restaurant_id;
            $updateCart = TRUE;
        }

        $delivery_mode = $this->getEffectiveDefaultDeliveryMode($restaurant, $cart);
        if ($cart->order_details->delivery_mode != $delivery_mode) {
            $cart->order_details->delivery_mode = $delivery_mode;
            $updateCart = TRUE;
        }
        $restaurant_discount_pct = PhpHelper::getNestedValue($cart, ['order_details', 'breakup', 'restaurant_discount_pct']);
        if ($restaurant_discount_pct != 0) {
            if (empty($cart->order_details->breakup)) {
                $cart->order_details->breakup = new \Drupal\food\Core\Order\OrderBreakup();
            }

            $cart->order_details->breakup->restaurant_discount_pct = 0;
            $updateCart = TRUE;
        }

        if ($updateCart) {
            \Drupal\food\Core\CartController::updateCart($cart);
        }

        $restaurant_menu_items = $data['restaurant_menu_items'];
      

        foreach ($restaurant_menu_items as $restaurant_menu_item) {
            $addCartItemUrl = Url::fromRoute('food.cart.item.add', ['restaurant_menu_item_id' => $restaurant_menu_item->restaurant_menu_item_id]);
            $addCartItemUrl->setOptions([
                'attributes' => [
                    'class' => ['use-ajax', 'food-add-cart-item-link'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
         /* if(($restaurant_menu_item->image_fid !=123456)|| ($restaurant_menu_item->image_fid !=2361) ){
             $images_menu = \Drupal\file\Entity\File::load($restaurant_menu_item->image_fid);
             $path= $images_menu->getFileUri();
             $restaurant_menu_item->image_url = ImageStyle::load('menuitem')->buildUrl($path);
            } */
            $addCartItemLink = Link::fromTextAndUrl($restaurant_menu_item->name, $addCartItemUrl);
            $restaurant_menu_item->add_cart_link = $addCartItemLink->toString();

            if ($allowEditingItem) {
                $editUrl = Url::fromRoute('food.partner.restaurant.menu.section.item.edit', [
                        'restaurant_id' => $restaurant_menu_item->restaurant_id,
                        'menu_id' => $restaurant_menu_item->menu_id,
                        'restaurant_menu_id' => $restaurant_menu_item->restaurant_menu_id,
                        'restaurant_menu_section_id' => $restaurant_menu_item->restaurant_menu_section_id,
                        'restaurant_menu_item_id' => $restaurant_menu_item->restaurant_menu_item_id
                ]);
                $editUrl->setOptions([
                    'attributes' => [
                        'class' => ['use-ajax'],
                        'data-dialog-type' => 'modal',
                        'data-dialog-options' => Json::encode([
                            'width' => 700,
                        ]),
                        'data-food-skip-add-item' => 'true',
                    ]
                ]);
                $editLink = Link::fromTextAndUrl($this->t('Edit'), $editUrl);
                $restaurant_menu_item->edit_link = $editLink->toString();
            }
        }
        $rouletteDiscounts = \Drupal\food\Core\RestaurantController::getRestaurantRouletteDiscounts($restaurant_id);
        if (empty($rouletteDiscounts) ||
            PhpHelper::getNestedValue($platform_settings, ['order_settings', 'disable_platform_deals'], FALSE) == TRUE ||
            PhpHelper::getNestedValue($restaurant, ['platform_settings', 'disable_platform_deals'], FALSE) == TRUE
        ) {
            $restaurantMenuCheckoutUrl = Url::fromRoute('food.cart.deliveryoptions');
        } else {
            $restaurantMenuCheckoutUrl = Url::fromRoute('food.cart.roulette');
        }

        if (\Drupal::currentUser()->id() == 0) {
            $tempUrl = Url::fromRoute('user.login',[]);
            $tempUrl->setOptions([
                'query' => ['destination' => $restaurantMenuCheckoutUrl->toString()],
                'attributes' => [
                    'class' => ['use-ajax', 'btn', 'btn-default', 'btn-primary', 'btn-checkout'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                    'role' => 'button',
                ]
            ]);
            $restaurantMenuCheckoutUrl = $tempUrl;
        } else {
            $restaurantMenuCheckoutUrl->setOptions([
                'attributes' => [
                    'class' => ['btn', 'btn-default', 'btn-primary', 'btn-checkout'],
                    'role' => 'button',
                ]
            ]);
        }
        $restaurantMenuCheckoutLink = Link::fromTextAndUrl('Checkout', $restaurantMenuCheckoutUrl);

        $user_cart_block_html = \Drupal\food\Form\Cart\CartController::getCurrentCartHtml(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]);

       $build = array(
            'user_cart_block_html' => $user_cart_block_html,
            '#markup' => '',
            '#theme' => 'food_restaurant_menu_list',
            'additionalData' => [
                'restaurant' => $data['restaurant'],
                'restaurant_menus' => $data['restaurant_menus'],
                'restaurant_menu_sections' => $data['restaurant_menu_sections'],
                'restaurant_menu_items' => $data['restaurant_menu_items'],
                'restaurantMenuCheckoutLink' => $restaurantMenuCheckoutLink->toString(),
                'restaurant_review' => $reviews,
                'ave_rating_res' => $rating['average_rating'],
                'number_rating_res' => $rating['rating_number'],
                'result_answer' => $result_answer,
            ],
            '#attached' => [
            
                 'library' => ['food/form.restaurant.restaurantmenu','food/form.menu.manupagemap'],

                'drupalSettings' => [
                    'food' => [
                        'restaurant' => [
                            'name' => $restaurant->name,
                            'restaurant_id' => $restaurant->restaurant_id,
                            'latitude' => $restaurant->latitude,
                            'longitude' => $restaurant->longitude,
                            'switchRestaurantUrl' => Url::fromRoute('food.cart.switchrestaurant', ['restaurant_id' => $restaurant->restaurant_id])->toString(),
                        ]
                    ]
                ]
            ],
        );
        //$build['#attached']['http_header'][] = ['Cache-Control', 'no-cache, no-store, must-revalidate', TRUE];
        //$build['#cache']['max-age'] = 0;
        $build['#cache']['contexts'] = [
            'user.roles:' . \Drupal\food\Core\RoleController::Platform_Role_Name
        ];

        return ($build);
    }

    private function getEffectiveDefaultDeliveryMode($restaurant, $cart) {
        $cart_delivery_mode = PhpHelper::getNestedValue($cart, ['order_details', 'delivery_mode']);
        $search_params_delivery_mode = PhpHelper::getNestedValue($cart, ['search_params', 'delivery_mode']);
        $deliveryDeliveryModeEnabled = PhpHelper::getNestedValue($restaurant, ['order_types', 'delivery_settings', 'enabled'], FALSE);
        $pickupDeliveryModeEnabled = PhpHelper::getNestedValue($restaurant, ['order_types', 'pickup_settings', 'enabled'], FALSE);

        $delivery_mode = $cart_delivery_mode;
        if (empty($delivery_mode)) {
            $delivery_mode = $search_params_delivery_mode;
        }
        if ($delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Pickup) {
            if (!$pickupDeliveryModeEnabled) {
                $delivery_mode = NULL;
            }
        } elseif ($delivery_mode == \Drupal\food\Core\Restaurant\DeliveryMode::Delivery) {
            if (!$deliveryDeliveryModeEnabled) {
                $delivery_mode = NULL;
            }
        }

        if (empty($delivery_mode)) {
            if ($pickupDeliveryModeEnabled) {
                $delivery_mode = \Drupal\food\Core\Restaurant\DeliveryMode::Pickup;
            } else if ($deliveryDeliveryModeEnabled) {
                $delivery_mode = \Drupal\food\Core\Restaurant\DeliveryMode::Delivery;
            }
        }

        return($delivery_mode);
    }

}
