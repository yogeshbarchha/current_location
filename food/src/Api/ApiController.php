<?php

namespace Drupal\food\Api;

use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Controller\ControllerBase;

class ApiController extends ControllerBase {

    /**
     * Display the markup.
     *
     * @return array
     */
    public function execute() {
        $response = new Response();

        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Expires', 'Wed, 29 Jan 1975 04:15:00 GMT');
        $response->headers->set('Last-Modified', gmdate("D, d M Y H:i:s") . " GMT");
        $response->headers->set('Cache-Control', 'max-age=0, no-cache, no-store, must-revalidate');
        $response->headers->set('Pragma', 'no-cache');

        try {
            if ($_SERVER['REQUEST_METHOD'] != 'POST') {
                throw new \Exception('Invalid request.');
            }

            $op = $_GET['op'];
            if (empty($op)) {
                throw new \Exception('No operation specified.');
            }
            $data = NULL;
            switch ($op) {
                case 'App.About':
                    $data = InfoResponder::getAboutInfo();
                    break;

                case 'Restaurant.Search':
                    $data = RestaurantResponder::searchRestaurants();
                    break;

                case 'Restaurant.GetRestaurantMenus':
                    $data = RestaurantResponder::getRestaurantMenus();
                    break;

                case 'Restaurant.GetRouletteDiscounts':
                    $data = RestaurantResponder::getRouletteDiscounts();
                    break;

                case 'Restaurant.GetRestaurantOrderPlacementOptions':
                    $data = RestaurantResponder::getRestaurantOrderPlacementOptions();
                    break;

                case 'Restaurant.IsAddressDeliverable':
                    $data = RestaurantResponder::isAddressDeliverable();
                    break;

                case 'Cuisine.GetAllCuisines':
                    $data = CuisineResponder::getAllCuisines();
                    break;

                case 'User.Ping':
                    $data = UserResponder::ping();
                    break;

                case 'User.Register':
                    $data = UserResponder::registerUser();
                    break;

                case 'User.GenerateRegistrationOtp':
                    $data = UserResponder::generateRegistrationOtp();
                    break;

                case 'User.GetUserAddresses':
                    $data = UserResponder::getUserAddresses();
                    break;

                case 'User.UpdateAddress':
                    $data = UserResponder::updateAddress();
                    break;

                case 'User.DeleteAddress':
                    $data = UserResponder::deleteAddress();
                    break;
                
                case 'User.UpdateUserProfile':
                    $data = UserResponder::updateUserProfile();
                    break;

                case 'User.ResetPassword':
                    $data = UserResponder::resetPassword();
                    break;
                
                case 'User.GetOrders':
                    $data = OrderResponder::getUserOrders();
                    break;

                case 'Order.Breakup':
                    $data = OrderResponder::breakupOrder();
                    break;

                case 'Order.Place':
                    $data = OrderResponder::placeOrder();
                    break;

                default:
					$parts = explode('.', $op);
					$instance = \Drupal\food\Util::getAddOnModuleClassInstance($parts[0], 'Api\\ApiController', TRUE);
					if(!empty($instance)) {
						$data = $instance->execute($op);
					}
					
					if($data == NULL) {
						throw new \Exception('Operation not supported.');
					}
            }

            $response->setContent(json_encode($data));
        } catch (\Exception $ex) {
            //\Drupal::logger('food')->error($ex->getMessage());
            watchdog_exception('food', $ex);
            $response->setContent(json_encode(array(
                'success' => false,
                'message' => 'An error has occurred:- ' . $ex->getMessage(),
            )));
        }

        return $response;
    }

}
