<?php

namespace Drupal\food\Api;

abstract class UserResponder extends ApiResponderBase {

    public static function ping() {
        $user = parent::initializeAuthenticatedRequest();

        $user_id = \Drupal::currentUser()->id();
		$profile = \Drupal\food\Core\UserController::getUserProfile($user_id);

		return(array('success' => true, 'data' => $profile));
    }

    public static function getUserAddresses() {
        parent::initializeAuthenticatedRequest();

        $user_id = \Drupal::currentUser()->id();
        $user_addresses = \Drupal\food\Core\AddressController::getUserAddresses($user_id);

        return(array('success' => true, 'data' => $user_addresses));
    }

    public static function updateAddress() {
        parent::initializeAuthenticatedRequest();

        $address = \Imbibe\Json\JsonHelper::deserializeObject($_POST['address'], '\Drupal\food\Core\Location\Address');
        $address->owner_user_id = \Drupal::currentUser()->id();

        \Drupal\food\Core\AddressController::updateUserAddress($address);

        return(array('success' => true));
    }

    public static function deleteAddress() {
        parent::initializeAuthenticatedRequest();

        $owner_user_id = \Drupal::currentUser()->id();
        $address_id = $_POST['address_id'];

        $data = \Drupal\food\Core\AddressController::deleteUserAddress($owner_user_id, $address_id);

        return(array('success' => true, 'message' => 'Address deleted successfully...'));
    }

    public static function registerUser() {
		$user_phone_number = NULL;
		
		if(isset($_POST['email'])) {
			$email = $_POST['email'];
		} else {
			$user_phone_number = $_POST['user_phone_number'];
			$otp_id = $_POST['otp_id'];
			$otp = $_POST['otp'];
			
			$otpObj = db_select('food_otp', 'fo')
				->fields('fo')
				->condition('otp_id', $otp_id)
				->execute()
				->fetchObject();
			
			if(empty($otpObj)) {
				throw new \Exception('Invalid OTP.');
			}
			
			if($otpObj->phone_number != $user_phone_number) {
				throw new \Exception('Invalid OTP / Phone Number.');
			}
			
			if($otpObj->otp != $otp) {
				throw new \Exception('Invalid OTP.');
			}
			
			$email = $user_phone_number . '@' . $_SERVER['SERVER_NAME'];
		}
		
        $password = $_POST['password'];
        $user_first_name = $_POST['user_first_name'];
        $user_last_name = $_POST['user_last_name'];

		$user = \Drupal\food\Core\UserController::createUser($email, $password, $user_first_name, $user_last_name, $user_phone_number);
		$profile = \Drupal\food\Core\UserController::getUserProfile($user->id());
		$profile['token'] = $password;

		return(array('success' => true, 'data' => $profile));
    }

    public static function generateRegistrationOtp() {
        $user_phone_number = $_POST['user_phone_number'];
		$otp = \Drupal\food\Core\UserController::dispatchOtp($user_phone_number);
		return(array('success' => true, 'data' => ['otp_id' => $otp['otp_id']]));
    }

    public static function updateUserProfile() {
        parent::initializeAuthenticatedRequest();
        $user_id = \Drupal::currentUser()->id();

        $user_first_name = $_POST['user_first_name'];
        $user_last_name = $_POST['user_last_name'];
        $user_phone_number = self::getPostVariable('user_phone_number', TRUE);
		
		$file = NULL;
		if(isset($_FILES['user_picture'])) {
			$file = \Drupal\food\Core\UserController::createFileFromUpload('user_picture');
		}
		
		\Drupal\food\Core\UserController::updateUserProfile($user_id, $user_first_name, $user_last_name, $user_phone_number, $file);
		return(array('success' => true));
    }

    public static function resetPassword() {
		$email = $_POST['email'];
		
        if (isset($email)) {
            $resetLink = \Drupal\food\Core\UserController::resetPassword($email);

            return(array('success' => true));
        }
    }

}
