<?php

namespace Drupal\food\Access\User;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Controller\ControllerBase;
use Imbibe\Util\PhpHelper;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Component\Serialization\Json;
/**
 * Builds an example page.
 */
class UserController extends ControllerBase {

  /**
   * Checks access for a specific request.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   */
  public function restaurantListAccess(AccountInterface $account) {
    // Check permissions and combine that with any custom access checking needed. Pass forward
    // parameters from the route and/or request as needed.
   $user = \Drupal\user\Entity\User::load($account->id());

    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food view restaurant list')) {
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food view restaurant list')) {
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantEditAccess(AccountInterface $account, $food_restaurant){

    $user = \Drupal\user\Entity\User::load($account->id());
    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food edit restaurant')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($food_restaurant);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food edit restaurant')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      if(!empty($subuser_permission) && $subuser_permission['permission'] == 'full' && in_array($food_restaurant, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantMenuListAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food view restaurant menu list')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food view restaurant menu list')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles) && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantMenuAddAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food add restaurant menu')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food add restaurant menu')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles) && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantMenuDeleteAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food delete restaurant menu')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food delete restaurant menu')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      if(!empty($subuser_permission) && $subuser_permission['permission'] == 'full' && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantMenuSectionOperationAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant section operation')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant section operation')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      if(!empty($subuser_permission) && $subuser_permission['permission'] == 'full' && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantCuisineOperationAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant cuisine operation')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant cuisine operation')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      if(!empty($subuser_permission) && $subuser_permission['permission'] == 'full' && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantOrderOperationAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant order operation')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant order operation')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles) && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantReportDashboardAccess(AccountInterface $account){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant report dashboard access')) {
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant report dashboard access')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantReportStatmentAccess(AccountInterface $account){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant view report statement')) {
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant view report statement')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  // public function restaurantAccountDepositAccess(AccountInterface $account, $restaurant_id){

  //   $user = \Drupal\user\Entity\User::load($account->id());
    
  //   if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant view report statement')) {
  //     return AccessResult::allowed();
  //   }
  //   if($user->hasRole('subuser') && $user->hasPermission('food restaurant view report statement')) {
  //     $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
  //     $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
  //     $subuser_roles = array('full');
  //     if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles) && in_array($restaurant_id, $subuser_restaurants)){
  //       return AccessResult::allowed();        
  //     }
  //   }
  //   return AccessResult::forbidden(); 
  // }

  public function restaurantOrderDetailAccess(AccountInterface $account, $order_id){
    $user = \Drupal\user\Entity\User::load($account->id());
    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant view order detail')) {
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant view order detail')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full','limited');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantOrderAdjustmentAccess(AccountInterface $account){

    $user = \Drupal\user\Entity\User::load($account->id());
    
    if(($user->hasRole('partner') || $user->hasRole('administrator')) && $user->hasPermission('food restaurant view order adjustment')) {
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant view order adjustment')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

  public function restaurantAddOrderAdjustmentAccess(AccountInterface $account, $restaurant_id){

    $user = \Drupal\user\Entity\User::load($account->id());

    
    if($user->hasRole('administrator') && $user->hasPermission('food restaurant add order adjustment')) {
      $owner_id = \Drupal\food\Core\SubuserController::getRestaurantOwner($restaurant_id);
      if($user->hasRole('partner') && $user->id() != $owner_id){
        return AccessResult::forbidden();    
      }
      return AccessResult::allowed();
    }
    if($user->hasRole('subuser') && $user->hasPermission('food restaurant add order adjustment')) {
      $subuser_restaurants = \Drupal\food\Core\SubuserController::getSubuserRestaurantsIds();
      $subuser_permission = \Drupal\food\Core\SubuserController::getSubuserPermission($account->id());
      $subuser_roles = array('full');
      if(!empty($subuser_permission) && in_array($subuser_permission['permission'], $subuser_roles) && in_array($restaurant_id, $subuser_restaurants)){
        return AccessResult::allowed();        
      }
    }
    return AccessResult::forbidden();
  }

}
