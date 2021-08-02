<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Imbibe\Util\PhpHelper;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a 'FeaturedCuisineBlock' Block.
 *
 * @Block(
 *   id = "food_featured_cuisine_block",
 *   admin_label = @Translation("Featured Cuisine Block"),
 *   category = @Translation("Food"),
 * )
 */
class FeaturedCuisineBlock extends BlockBase {

	/**
	* {@inheritdoc}
	*/
	public function build() {
		$cuisines = \Drupal\food\Core\CuisineController::getFeaturedCuisines();
		\Drupal\food\Core\CuisineController::assignImageUrls($cuisines);
		
		$cart = \Drupal\food\Core\CartController::getCurrentCart(NULL, ['autoCreate' => FALSE]);
		$search_params = PhpHelper::getNestedValue($cart, ['search_params']);
		if(empty($search_params)) {
			$search_params = new \Drupal\food\Core\Cart\SearchParams();
		}
		foreach($cuisines as $cuisine) {
                         $file = File::load($cuisine->image_fid);
	   		 $path = $file->getFileUri();
	  		 $url = ImageStyle::load('cuisines')->buildUrl($path);
      			 $cuisine->image_url =  $url;
			 $search_params->cuisine_ids = [$cuisine->cuisine_id];
			 $cuisine->search_url = Url::fromRoute('food.search.restaurants.page')->toString() . '?search_params=' . json_encode($search_params);
		}

        $build['#theme'] = 'food_featured_cuisine_block';
        $build['cuisines'] = $cuisines;
        $build['#cache']['max-age'] = 0;
		
		return ($build);
	}
}
