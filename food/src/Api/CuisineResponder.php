<?php

namespace Drupal\food\Api;

abstract class CuisineResponder extends ApiResponderBase {

    public static function getAllCuisines() {
        $data = \Drupal\food\Core\CuisineController::getAllCuisines();

        return(array('success' => true, 'data' => $data));
    }

}
