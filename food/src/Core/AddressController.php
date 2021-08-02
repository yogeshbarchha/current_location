<?php
namespace Drupal\food\Core;

abstract class AddressController extends ControllerBase {

    public static function getUserAddresses($user_id, $config = array()) {
		$query = db_select('food_user_address', 'fr')
					->fields('fr')
					->condition('owner_user_id', $user_id);
		
		$config['defaultSortField'] = 'address_id';
		$rows = ControllerBase::executeListQuery($query, $config);
        return($rows);
    }

    public static function getUserAddress($user_id, $address_id = NULL) {
		$query = db_select('food_user_address', 'fr')
			->fields('fr')
			->condition('owner_user_id', $user_id)
			->condition('address_id', $address_id);
					
		$row = ControllerBase::executeRowQuery($query);
		return($row);
    }
	
    public static function updateUserAddress ($address) {
		$address = self::prepareForUpdation('food_user_address', $address);
		if(isset($address['address_id'])) {
			db_update('food_user_address')
				->fields($address)
				->condition('owner_user_id', $address['owner_user_id'])
				->condition('address_id', $address['address_id'])
				->execute();
		} else {
            $address['created_time'] = \Imbibe\Util\TimeUtil::now();
			db_insert('food_user_address')
				->fields($address)
				->execute();
		}	   
	}
    
    public static function deleteUserAddress ($owner_user_id, $address_id) {
		db_delete('food_user_address')
			->condition('address_id', $address_id)
			->condition('owner_user_id', $owner_user_id)
			->execute();
	}
}
