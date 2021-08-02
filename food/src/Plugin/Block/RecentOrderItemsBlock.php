<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;

/**
 * Provides a 'RecentOrderItemsBlock' Block.
 *
 * @Block(
 *   id = "food_recent_order_items_block",
 *   admin_label = @Translation("Recent Order Items Block"),
 *   category = @Translation("Food"),
 * )
 */
class RecentOrderItemsBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$recent_order_items = \Drupal\food\Core\OrderController::getRecentlyOrderedItems();

        $build['#theme'] = 'food_recent_order_items_block';
        $build['recent_order_items'] = $recent_order_items;
        $build['#cache']['max-age'] = 0;
		
		return ($build);
	}
}
