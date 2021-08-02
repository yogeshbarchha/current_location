<?php

namespace Drupal\food\Core;

abstract class RoleController {
    const Partner_Role_Name = 'partner';
    const Platform_Role_Name = 'platform';
    const Administrator_Role_Name = 'administrator';
    const Subuser_Role_Name = 'subuser';


	public static function getRoles () {
		$roles = &drupal_static('Food\User\RoleController::getRoles');
		if (isset($roles)) {
			return ($roles);
		}
		
		$cache = \Drupal::cache()->get('Food\User\RoleController::getRoles');
		if($cache) {
			$roles = $cache->data;
			return ($roles);
		}
		
		$roles = array();
		$tempRoles = user_role_names();
		
		$roles[RoleController::Partner_Role_Name] = $tempRoles[RoleController::Partner_Role_Name];
		$roles[RoleController::Platform_Role_Name] = $tempRoles[RoleController::Platform_Role_Name];
		$roles[RoleController::Administrator_Role_Name] = $tempRoles[RoleController::Administrator_Role_Name];

		\Drupal::cache()->set('Food\User\RoleController::getRoles', $roles);
		
		return ($roles);
	}

	public static function getPartnerRole () {
		$roles = RoleController::getRoles();
		return ($roles[RoleController::Partner_Role_Name]);
	}

	public static function getPlatformRole () {
		$roles = RoleController::getRoles();
		return ($roles[RoleController::Platform_Role_Name]);
	}

	public static function getAdministratorRole () {
		$roles = RoleController::getRoles();
		return ($roles[RoleController::Administrator_Role_Name]);
	}
}
