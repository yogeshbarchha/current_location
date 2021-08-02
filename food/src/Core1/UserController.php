<?php

namespace Drupal\food\Core;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

abstract class UserController extends ControllerBase {

    public static function validateCurrentUserOrAdmin($uid) {
        $currentUserInterface = \Drupal::currentUser();
        if ($currentUserInterface->id() != $uid) {
            $currentUser = \Drupal\user\Entity\User::load($currentUserInterface->id());
            if (!$currentUser->hasPermission('administer users')) {
                throw new AccessDeniedHttpException('You are not authorized to access this page.');
            }
        }
    }

    public static function validateUserOrderAccess($order, $restaurant) {
        $currentUserInterface = \Drupal::currentUser();
        if ($currentUserInterface->id() == $order->user_id) {
			return (TRUE);
        }
        if ($currentUserInterface->id() == $restaurant->owner_user_id) {
			return (TRUE);
        }
		
		$account = \Drupal\user\Entity\User::load($currentUserInterface->id());
		$isPlatform = $account->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
		if($isPlatform) {
			return (TRUE);
		}
		
		throw new AccessDeniedHttpException('You are not authorized to access this order.');
    }

    public static function validatePartnerOrderAccess($order, $restaurant) {
        $currentUserInterface = \Drupal::currentUser();
		$account = \Drupal\user\Entity\User::load($currentUserInterface->id());

        if($account->hasRole(\Drupal\food\Core\RoleController::Subuser_Role_Name)){
        	return (TRUE);
        }

        if ($currentUserInterface->id() == $restaurant->owner_user_id) {
			return (TRUE);
        }
		
		$isPlatform = $account->hasRole(\Drupal\food\Core\RoleController::Platform_Role_Name);
		if($isPlatform) {
			return (TRUE);
		}
		
		throw new AccessDeniedHttpException('You are not authorized to access this order.');
    }

    //CAUTION: Invoke with care, a call to this function can be a serious security issue.
    //Do not use this functiin unless authorized by team leader in writing.
    public static function setCurrentUser($user) {
        //user_login_finalize does a lot of things, including creating a log entry every time. We do not need all of it, as a log entry on each authenticated API request would swell the log.
        //So need to use custom logic to set current user.
        //user_login_finalize($user);
        //Adapted from user_login_finalize logic.
        //We should try to use \Drupal\Core\Session\AccountSwitcherInterface::switchTo() and \Drupal\Core\Session\AccountSwitcherInterface::switchBack().
        \Drupal::currentUser()->setAccount($user);
    }
	
	public static function dispatchOtp($user_phone_number, $type = 1) {
		$otp = array(
			'otp_id' => uniqid(),
			'type' => $type,
			'phone_number' => $user_phone_number,
			'otp' => \Imbibe\Util\Globals::generateConfirmationCode(),
			'created_time' => \Imbibe\Util\TimeUtil::now(),
		);
		db_insert('food_otp')
			->fields($otp)
			->execute();
			
		\Drupal::moduleHandler()->invokeAll('food_dispatch_otp', array($otp));
		
		return ($otp);
	}

    public static function createUser($email, $password, $user_first_name = NULL, $user_last_name = NULL, $user_phone_number = NULL) {
        $existingUser = user_load_by_mail($email);
        if ($existingUser) {
			throw new \Exception('A user with the specified email already exists.');
        }

		if($user_phone_number != NULL) {
			$query = \Drupal::entityQuery('user');
			$existingUserIds = $query
				->condition('field_phone_number', $user_phone_number)
				->execute();
			if(count($existingUserIds) > 0) {
				throw new \Exception('A user has already registered with this phone number.');
			}
		}

        $user = \Drupal\user\Entity\User::create();

        $user->setUsername($email);
        $user->setPassword($password);
        $user->setEmail($email);
        if ($user_first_name != NULL) {
            $user->set('user_first_name', $user_first_name);
        }
        if ($user_last_name != NULL) {
            $user->set('user_last_name', $user_last_name);
        }
        if ($user_phone_number != NULL) {
            $user->set('field_phone_number', $user_phone_number);
        }

        $user->enforceIsNew();
        $user->activate();

        $user->save();

        return($user);
    }

    public static function ensureUser($email) {
        $user = user_load_by_mail($email);
        if (!$user) {
            $user = self::createUser($email, \Imbibe\Util\Globals::generateRandomString());
        }
		
		return($user);
    }

    public static function getUserAuthTokenByUserId($user_id) {
        $obj = db_select('food_user_auth_token', 'fuat')
            ->condition('user_id', $user_id)
            ->fields('fuat')
			->execute()
			->fetchObject();

		return($obj);
    }

    public static function getUserAuthTokenByToken($token) {
        $obj = db_select('food_user_auth_token', 'fuat')
            ->condition('token', $token)
            ->fields('fuat')
			->execute()
			->fetchObject();

		return($obj);
    }

    public static function ensureUserAuthToken($user_id) {
        $obj = self::getUserAuthTokenByUserId($user_id);

        if(!$obj) {
			db_insert('food_user_auth_token')
				->fields([
					'created_time' => \Imbibe\Util\TimeUtil::now(),
					'user_id' => $user_id,
					'token' => user_password(50),
				])
				->execute();
			$obj = self::getUserAuthTokenByUserId($user_id);
		}
		
		return($obj);
    }

    public static function getUserProfile($user_id) {
		$user = \Drupal\user\Entity\User::load($user_id);
		if (!empty($user->user_picture) && !$user->user_picture->isEmpty()) {
			$user_image_url = $user->user_picture->entity->url();
		} else {
			$user_image_url = NULL;
		}
		
		$profile = array(
			'user_id' => $user->id(),
			'username' => $user->getUsername(),
			'user_first_name' => $user->get('user_first_name')->value,
			'user_last_name' => $user->get('user_last_name')->value,
			'email' => $user->get('mail')->value,
			'image_url' => $user_image_url,
			'field_phone_number' => $user->get('field_phone_number')->value,
		);
		
		return($profile);
    }

    public static function updateUserProfile($user_id,
		$user_first_name = NULL,
		$user_last_name = NULL,
		$user_phone_number = NULL,
		$file = NULL) {
			
		$user = \Drupal\user\Entity\User::load($user_id);

		if ($user_first_name != NULL) {
			$user->set('user_first_name', $user_first_name);
		}
		if ($user_last_name != NULL) {
			$user->set('user_last_name', $user_last_name);
		}
		if ($user_phone_number != NULL) {
			$user->set('field_phone_number', $user_phone_number);
		}
		
		if($file != NULL) {
			$field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
			file_validate_image_resolution($file, $field->getSetting('max_resolution'), $field->getSetting('min_resolution'));
			$file->setPermanent();
			$file->save();

			$user->set('user_picture', $file);
		}

		$user->save();
    }

    public static function resetPassword($email) {
        $user = user_load_by_mail($email);
        if($user) {
			$langcode =  \Drupal::languageManager()->getCurrentLanguage()->getId();
			_user_mail_notify('password_reset', $user, $langcode);
		} else {
			throw new \Exception('A user with the specified email does not exist.');
		}
    }

    public static function createFileFromUpload($name) {
		$field = \Drupal\field\Entity\FieldConfig::loadByName('user', 'user', 'user_picture');
		$fieldStorage = \Drupal\field\Entity\FieldStorageConfig::loadByName(\Drupal::entityTypeManager()->getStorage('user')->getEntityTypeId(), 'user_picture');
		$validators = array(
		   'file_validate_extensions' => array($field->getSetting('file_extensions')),
		   'file_validate_size' => array(\Drupal\Component\Utility\Bytes::toInt($field->getSetting('max_filesize'))),
		);
		$upload_location = $fieldStorage->getSetting('uri_scheme') . '://' . \Drupal::token()->replace($field->getSetting('file_directory'));
		$upload_location .= '/' . $_FILES[$name]['name'];
		
		\Drupal::service('file_system')->moveUploadedFile($_FILES[$name]['tmp_name'], $upload_location);
		$file = \Drupal\file\Entity\File::Create([
			'uid' => \Drupal::currentUser()->id(),
			'status' => 0,
			'uri' => $upload_location,
		]);
		$file->save();
		
		return($file);
    }

}
