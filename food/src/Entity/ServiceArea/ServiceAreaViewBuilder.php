<?php

namespace Drupal\food\Entity\ServiceArea;

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
class ServiceAreaViewBuilder extends EntityViewBuilder {

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
	$service_area_id = \Drupal\food\Util::getServiceAreaIdFromUrl();
	$service_area = \Drupal\food\Core\ServiceAreaController::getServiceAreaById($service_area_id);

	if (is_numeric($service_area->image_fid)) {
		$picture = \Drupal\file\Entity\File::load($service_area->image_fid);
		if($picture) {
			$service_area->image_url = $picture->url();
		}
	}

	$build2 = array(
		'#markup' => '',
		'#theme' => 'food_service_area_page',
		'additionalData' => [
			'service_area' => $service_area,
		],
	);
	
	$build1 = parent::getBuildDefaults($entity, $view_mode);	
	$build = array_merge($build1, $build2);

	$build['restaurant_list'] = $this->restaurantList->page();
	$build['#attached']['drupalSettings']['food'] = [
		'cart' => [
			'search_params' => [
				'service_area_ids' => [$service_area->service_area_id],
			],
		]
	];

    return $build;
  }

}
