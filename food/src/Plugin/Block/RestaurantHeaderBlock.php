<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'RestaurantHeaderBlock' Block.
 *
 * @Block(
 *   id = "food_restaurant_header_block",
 *   admin_label = @Translation("Restaurant Header Block"),
 *   category = @Translation("Food"),
 * )
 */
class RestaurantHeaderBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
        if (empty($restaurant_id)) {
            $cart = \Drupal\food\Core\CartController::getCurrentCart();
            $restaurant_id = $cart->restaurant_id;
        }
        $restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);

        $dealUrl = Url::fromRoute('food.restaurant.deals', ['restaurant_id' => $restaurant->restaurant_id]);
        $dealUrl->setOptions([
            'attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                    'width' => 700,
                ]),
            ]
        ]);
        
        \Drupal\food\Core\ControllerBase::assignImageUrl($restaurant);
        $dealLink = Link::fromTextAndUrl(' ', $dealUrl);
        $restaurant->dealLink = $dealLink->toString();

        $build['#cache']['max-age'] = 0;

        $build['#restaurant'] = $restaurant;
        $build['#theme'] = 'food_restaurant_header_block';
        $build['#attached']['library'][] = 'food/form.restaurant.restaurantheaderblock';

        return ($build);
    }

}
