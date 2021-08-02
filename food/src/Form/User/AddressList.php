<?php

namespace Drupal\food\Form\User;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Link;

class AddressList extends ControllerBase {

    public function show($user) {
        //Heading.
        $build = array(
            //'#markup' => 'My restaurants',
        );

        $build['container'] = array(
            'add_button' => array(
                '#type' => 'link',
                '#title' => 'Add Address',
                '#url' => Url::fromRoute('food.user.address.add', ['user' => $user]),
                '#attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ],
            )
        );

        $header = array(
            array('data' => $this->t('Name'), 'field' => 'name', 'sort' => 'asc'),
            array('data' => $this->t('Address Line 1'), 'field' => 'address_line1'),
            array('data' => $this->t('Building/House No.'), 'field' => 'address_line2'),
            array('data' => $this->t('City'), 'field' => 'city'),
            array('data' => $this->t('State'), 'field' => 'state'),
            array('data' => $this->t('Type'), 'field' => 'type'),
            array('data' => $this->t('Phone Number'), 'field' => 'phone_number'),
            array('data' => $this->t('Postal Code'), 'field' => 'postal_code'),
            array('data' => $this->t('')),
            array('data' => $this->t('')),
        );

        $rows = \Drupal\food\Core\AddressController::getUserAddresses($user, ['header' => $header]);

        foreach ($rows as &$row) {
            $url = Url::fromRoute('food.user.address.edit', ['user' => $user, 'address_id' => $row->address_id]);
            $url->setOptions([
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
            $edit_link = Link::fromTextAndUrl(t('Edit'), $url);

            $delete_url = Url::fromRoute('food.user.address.delete', ['user' => $user, 'address_id' => $row->address_id]);
            $delete_url->setOptions([
                'query' => ['destination' => $_SERVER['REQUEST_URI']],
                'attributes' => [
                    'class' => ['use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => Json::encode([
                        'width' => 700,
                    ]),
                ]
            ]);
            $delete_link = Link::fromTextAndUrl(t('Delete'), $delete_url);
            if($row->type=='0'){
                $row->type ="Home";
            }else{
               $row->type ="Office";
            }


            $row = array(
                'data' => array(
                    'name' => $row->contact_name,
                    'address_line1' => $row->address_line1,
                    'address_line2' => $row->address_line2,
                    'city' => $row->city,
                    'state' => $row->state,
                    'Type' => $row->type,
                    'phone_number' => $row->phone_number,
                    'postal_code' => $row->postal_code,
                    'edit_link' => $edit_link->toString(),
                    'delete_link' => $delete_link->toString(),
                ),
            );
        }

        //Generate the table.
        $build['table'] = array(
            '#theme' => 'table',
            '#header' => $header,
            '#rows' => $rows,
        );

        //Finally add the pager.
        $build['pager'] = array(
            '#type' => 'pager'
        );
		
		$build['#cache']['max-age'] = 0;

        return ($build);
    }

}
