<?php

namespace Drupal\food\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a block with a simple text.
 *
 * @Block(
 *   id = "restaurant_dropdown_filter_block",
 *   admin_label = @Translation("Restaurant Dropdown Filter Block"),
 * )
 */
class RestaurantDropdownFilter extends BlockBase {
  /**
   * {@inheritdoc}
   */
  public function build() {
    $output = '';
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $restaurants = \Drupal\food\Core\RestaurantController::getCurrentUserRestaurants([
            'pageSize' => 0,
            'conditionCallback' => function($query) {
                $query = $query->condition('fr.status', 1);
                return($query);
            }
            ]);

    $output .= '<dl id="restaurant-dropdown-filter" class="dropdown">    
                <dt>
                <a>
                  <span class="hida">All Restaurant</span>
                  <i class="fa fa-caret-down"></i>  
                  <p class="multiSel"></p>  
                </a>
                </dt>
              
                <dd>
                    <div class="mutliSelect">
                        <ul><li><input type="checkbox" value="All" />Select All</li>';
    if(!empty($restaurants)){
      foreach ($restaurants as $key => $value) {
        $output .='<li><input title="'.$value->name.'" type="checkbox" value="'.$value->restaurant_id.'"/>'.$value->name.'</li>';              
      }
    }

    $output .='</ul>
             </div>
          </dd>
        </dl>';
    
    $output .= '<div class="row">
                <ul class="nav nav-tabs" id="dashboadfilterlink">
                  <li><a href="/partner/report/dashboard" value="0">Active</a></li>
                  <li><a href="/partner/report/dashboard/complete" value="10">Completed</a></li>
                  <li><a href="/partner/report/dashboard/cancel" value="100">Cancelled</a></li>
                  <li><a href="/partner/report/dashboard/schedule" value="100">Scheduled</a></li>
                </ul>
                </div>';

    return array(
      '#children' => $output,
      '#attached' => array('library' => 'food/dashboard.restaurantdropdownfilterblock')
    );
  }

  /**
   * {@inheritdoc}
   */
 protected function blockAccess(AccountInterface $account) {
   return \Drupal\food\Access\User\UserController::restaurantReportDashboardAccess($account);
  }
  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['restaurant_dropdown_filter_settings'] = $form_state->getValue('restaurant_dropdown_filter_settings');
  }
}