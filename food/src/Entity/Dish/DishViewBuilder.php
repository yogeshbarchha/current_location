<?php

namespace Drupal\food\Entity\Dish;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\food\Form\Restaurant\RestaurantList;

/**
 * Provides a view controller for food_restaurant entity.
 *
 * @ingroup food
 */
class DishViewBuilder extends EntityViewBuilder {

  protected $restaurantList;

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Registry $theme_registry = NULL, RestaurantList $restaurantList = NULL) {
	parent::__construct($entity_type, $entity_manager, $language_manager, $theme_registry);
    $this->restaurantList = $restaurantList;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('theme.registry'),
	  RestaurantList::create($container)
    );
  }

  /**
   * Provides entity-specific defaults to the build process.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which the defaults should be provided.
   * @param string $view_mode
   *   The view mode that should be used.
   *
   * @return array
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
	$dish_id = \Drupal\food\Util::getDishIdFromUrl();
	$dish = \Drupal\food\Core\DishController::getDishById($dish_id);

	if (is_numeric($dish->image_fid)) {
		$picture = \Drupal\file\Entity\File::load($dish->image_fid);
		if($picture) {
			$dish->image_url = $picture->url();
		}
	}

	$build2 = array(
		'#markup' => '',
		'#theme' => 'food_dish_page',
		'additionalData' => [
			'dish' => $dish,
		],
	);
	
	$build1 = parent::getBuildDefaults($entity, $view_mode);	
	$build = array_merge($build1, $build2);

	$build['restaurant_list'] = $this->restaurantList->page();
	$build['#attached']['drupalSettings']['food'] = [
		'cart' => [
			'search_params' => [
				'dish_ids' => [$dish->dish_id],
			],
		]
	];

    return $build;
  }

}
