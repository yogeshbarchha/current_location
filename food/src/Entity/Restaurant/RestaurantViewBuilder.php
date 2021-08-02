<?php

namespace Drupal\food\Entity\Restaurant;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Theme\Registry;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\food\Form\Restaurant\RestaurantMenu;

/**
 * Provides a view controller for food_restaurant entity.
 *
 * @ingroup food
 */
class RestaurantViewBuilder extends EntityViewBuilder {

  protected $restaurantMenu;

  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, Registry $theme_registry = NULL, RestaurantMenu $restaurantMenu = NULL) {
	parent::__construct($entity_type, $entity_manager, $language_manager, $theme_registry);
    $this->restaurantMenu = $restaurantMenu;
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
	  RestaurantMenu::create($container)
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
	$build1 = parent::getBuildDefaults($entity, $view_mode);
	$build2 = $this->restaurantMenu->search();
	
	$build = array_merge($build1, $build2);

    return $build;
  }

}
