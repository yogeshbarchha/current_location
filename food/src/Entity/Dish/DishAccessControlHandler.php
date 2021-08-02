<?php

namespace Drupal\food\Entity\Dish;

use Drupal\Core\Access\AccessResult;
use \Drupal\user\Access\RoleAccessCheck;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Access controller for the Dish entity.
 */
class DishAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   *
   * Link the activities to the roles. checkAccess is called with the
   * $operation as defined in the routing.yml file.
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
	$routeProvider = \Drupal::service('router.route_provider');
	$roleCheckService = \Drupal::service('access_check.user.role');
	
    switch ($operation) {
      case 'view':
		//Access to entity.food_dish.canonical is controlled by permission and not by role.
		return AccessResult::allowedIfHasPermission($account, 'access content');

      case 'edit':
        return $roleCheckService->access($routeProvider->getRouteByName('entity.food_dish.edit_form'), $account);

      case 'delete':
        return $roleCheckService->access($routeProvider->getRouteByName('entity.food_dish.delete_form'), $account);
    }
    return AccessResult::allowed();
  }

  /**
   * {@inheritdoc}
   *
   * Separate from the checkAccess because the entity does not yet exist, it
   * will be created during the 'add' process.
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
	$routeProvider = \Drupal::service('router.route_provider');
	$roleCheckService = \Drupal::service('access_check.user.role');

	return $roleCheckService->access($routeProvider->getRouteByName('food.platform.dish.add'), $account);
  }

}
