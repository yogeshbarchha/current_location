<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Imbibe\Util\PhpHelper;
use Drupal\Component\Serialization\Json;

/**
 * Provides a 'TopBannerBlock' Block.
 *
 * @Block(
 *   id = "food_top_banner_block",
 *   admin_label = @Translation("Top Banner Block"),
 *   category = @Translation("Food"),
 * )
 */
class TopBannerBlock extends BlockBase {

    /**
     * {@inheritdoc}
     */
    public function build() {
        $build = [];

		$platform_settings = \Drupal\food\Core\PlatformController::getPlatformSettings();

        $build['#banner_url'] = $platform_settings->derived_settings->top_banner_url;
        $build['#restaurant_name_image_url'] = $platform_settings->derived_settings->restaurant_name_image_url;

        $build['#theme'] = 'food_top_banner_block';
		//$build['#cache']['max-age'] = 0;

        return ($build);
    }

}
