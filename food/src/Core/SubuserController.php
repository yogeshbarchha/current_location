<?php

namespace Drupal\food\Core;

use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Imbibe\Util\PhpHelper;


abstract class SubuserController extends ControllerBase {

    public static function getSubuserPermission($uid) {
   	  if(empty($uid)){
   	  	$uid = \Drupal::currentUser()->id();
   	  }
        
      $db = \Drupal::database();
      $query = $db->select('subuser_permission','sp');
      $query->fields('sp');
      $query->condition('uid', $uid);
      $result = $query->execute()->fetchAssoc();
      
      return $result;
    }

    public static function getSubuserRestaurantsIds($uid) {
   	  if(empty($uid)){
   	  	$uid = \Drupal::currentUser()->id();
   	  }
        
      $db = \Drupal::database();
      $query = $db->select('user__field_restaurant_assign','ura');
      $query->fields('ura',array('field_restaurant_assign_target_id'));
      $query->condition('bundle', 'user');
      $query->condition('entity_id', $uid);
      $result = $query->execute()->fetchAll();
      $restaurant_ids = array();

      if(!empty($result)){
	      foreach ($result as $key => $value) {
	      	$restaurant_ids[] = $value->field_restaurant_assign_target_id;
	      }      	
      }     
      
      return $restaurant_ids;
    }

    public static function getRestaurantOwner($restaurant_id) {
   	  if(empty($uid)){
   	  	$uid = \Drupal::currentUser()->id();
   	  }
        
      $db = \Drupal::database();
      $query = $db->select('food_restaurant','fr');
      $query->fields('fr',array('owner_user_id'));
      $query->condition('restaurant_id', $restaurant_id);
      $result = $query->execute()->fetchAssoc();
      $owner_id = 0;
      
      if(!empty($result['owner_user_id'])){
         $owner_id = $result['owner_user_id'];
      }

      return $owner_id;
    }

    public static function getRestaurantSubuserIds($restaurant_owner_id) {
   	  if(empty($restaurant_owner_id)){
   	  	$restaurant_owner_id = \Drupal::currentUser()->id();
   	  }
        
      $db = \Drupal::database();
      $query = $db->select('user__field_subuser','fs');
      $query->fields('fs',array('field_subuser_target_id'));
      $query->condition('entity_id', $restaurant_owner_id);
      $result = $query->execute()->fetchAll();
      $subuser_ids = array();

      if(!empty($result)){
	      foreach ($result as $key => $value) {
	      	$subuser_ids[] = $value->field_subuser_target_id;
	      }      	
      } 

      return $subuser_ids;
    }

    public static function addRestaurantSubuserPermission($uid, $permission) {
      $query = \Drupal::database()->insert('subuser_permission');
        $query->fields(
        array(
        'uid' => $uid,
        'permission' => $permission,
        )
        )->execute();
    }

    public function updateRestaurantSubuserPermission($uid, $permission){
      $query = \Drupal::database()->update('subuser_permission');
      $query->fields([
      'permission' => $permission,
      ]);
      $query->condition('uid', $uid);
      $query->execute();
    }
}