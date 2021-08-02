<?php

namespace Drupal\food\Form\Cart;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Imbibe\Util\PhpHelper;

class AddCartItemForm extends FormBase {

  public function getFormId() {
    return 'food_add_cart_item_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $restaurant_menu_item_id = \Drupal::routeMatch()
      ->getParameter('restaurant_menu_item_id');
    $restaurant_menu_item = \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);

    $currencySymbol = \Drupal\food\Core\PlatformController::getPlatformSettings()->derived_settings->currency_symbol;
    $form['#title'] = Markup::create($restaurant_menu_item->name . "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;{$currencySymbol}{$restaurant_menu_item->price}");

    $sizes = PhpHelper::getNestedValue($restaurant_menu_item,
      ['variations', 'sizes']);
    if ($sizes != NULL && count($sizes) > 0) {
      $sizeOptions = array();
      $defaultSizeOption = NULL;
      foreach ($sizes as $index => $size) {
        $sizeOptions[$index] = $size->name . ' (' . $currencySymbol . $size->price . ')';
        if ($size->is_default) {
          $defaultSizeOption = $index;
        }
      }
      $form['size'] = array(
        '#type' => 'radios',
        '#title' => $this->t('Size'),
        '#options' => $sizeOptions,
        '#attributes' => array(
          'class' => array(
            'cart_item_size',
          ),
        ),
        '#default_value' => $defaultSizeOption,
      );
    }

    $form['quantity_container'] = array(
      '#type' => 'container',
      '#attributes' => array(
        'class' => array(
          'cart_item_quantity_container',
        ),
      ),
      '#prefix' => '<div class="cart_item_quantity_field_wrapper">',
      '#suffix' => '</div>',
    );
    $form['quantity_container']['label'] = array(
      '#type' => 'label',
      '#title' => $this->t('Quantity'),

    );
    $form['quantity_container']['quantity_down'] = array(
      '#type' => 'button',
      '#value' => '-',
      '#attributes' => array(
        'class' => array(
          'cart_item_quantity_down',
        ),
      ),
    );
    $form['quantity_container']['quantity'] = array(
      '#type' => 'number',
      '#default_value' => 1,
      '#min' => '1',
      '#max' => '1000',
      '#attributes' => array(
        'class' => array(
          'cart_item_quantity',
        ),
      ),
    );
    $form['quantity_container']['quantity_up'] = array(
      '#type' => 'button',
      '#value' => '+',
      '#attributes' => array(
        'class' => array(
          'cart_item_quantity_up',
        ),
      ),
    );

    $categories = PhpHelper::getNestedValue($restaurant_menu_item,
      ['variations', 'categories']);
    if ($categories != NULL && count($categories) > 0) {
      foreach ($categories as $index => $category) {
        $categoryOptions = array();

        $type = NULL;
        switch ($category->display_type) {
          case 'checkbox':
            $type = 'checkboxes';
            break;
          case 'dropdown':
            $type = 'select';
            if ($category->required != TRUE) {
              $categoryOptions[\Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE] = $this->t('No thanks');
            }
            break;
          case 'radio':
            $type = 'radios';
            if ($category->required != TRUE) {
              $categoryOptions[\Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE] = $this->t('No thanks');
            }
            break;
        }

        $defaultCategoryOption = NULL;
        foreach ($category->options as $index2 => $categoryOption) {
          if (PhpHelper::getNestedValue($categoryOption, ['is_price_pct'],
            FALSE)) {
            $categoryOptions[$index2] = $categoryOption->name . ' (' . $categoryOption->price . '%)';
          }
          else {
            $categoryOptions[$index2] = $categoryOption->name . ' (' . $currencySymbol . $categoryOption->price . ')';
          }
          if ($categoryOption->is_default) {
            $defaultCategoryOption = $index;
          }
        }

        switch ($category->display_type) {
          case 'checkbox':
            if ($defaultCategoryOption != NULL) {
              $defaultCategoryOption = [$defaultCategoryOption => $defaultCategoryOption];
            }
            break;
        }

        $form['category' . $index] = array(
          '#type' => $type,
          '#title' => $category->name,
          '#options' => $categoryOptions,
          '#attributes' => array(
            'class' => array(
              'cart_item_category' . $index,
            ),
          ),
          '#default_value' => (array) $defaultCategoryOption,
        );
      }
    }

    $form['special_instructions'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('Special Instructions'),
      //'#suffix' => $this->t('Additional charges may apply.'),
    );

    $form['add_cart'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add to Cart'),
      '#attributes' => array(
        'class' => array(
          'add_cart_button',
          'use-ajax-submit',
        ),
      ),
    );

    $form['#attached']['library'][] = 'food/form.user.addcartitemform';
    $form['#attached']['drupalSettings']['food'] = array(
      'restaurant' => array(
        'menu' => array(
          'currentItem' => $restaurant_menu_item,
        ),
      ),
    );

    return ($form);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $restaurant_menu_item_id = \Drupal::routeMatch()
      ->getParameter('restaurant_menu_item_id');
    $cart = \Drupal\food\Core\CartController::getCurrentCart();

    if (empty($cart->order_details)) {
      $cart->order_details = new \Drupal\food\Core\Order\Order();
    }
    $cart->order_details->restaurant_id = $cart->restaurant_id;

    if (empty($cart->order_details->items)) {
      $cart->order_details->items = array();
    }

    $currentItem = new \Drupal\food\Core\Order\OrderItem();
    $currentItem->restaurant_menu_item_id = $restaurant_menu_item_id;
    $currentItem->quantity = intval($form_state->getValue('quantity'));

    $sizeIndex = $form_state->getValue('size');
    if ($sizeIndex != NULL) {
      $currentItem->size = new \Drupal\food\Core\Order\OrderItemSize();
      $currentItem->size->id = $sizeIndex;
    }

    $currentItem->options = [];
    $allValues = $form_state->getValues();
    foreach ($allValues as $key => $submittedValue) {
      if (strpos($key, 'category') === 0) {
        $categoryIndex = intval(substr($key, strlen('category')));

        $categoryValues = [];
        if (is_array($submittedValue) == FALSE) {
          $categoryValues[] = $submittedValue;
        }
        else {
          foreach ($submittedValue as $key => $value) {
            if (!empty($value)) {
              $categoryValues[] = $key;
            }
          }
        }

        foreach ($categoryValues as $categoryValue) {
          if ($categoryValue == \Drupal\food\Core\Order\OrderItem::NOTHANKSVALUE) {
            continue;
          }

          $option = new \Drupal\food\Core\Order\OrderItemOption();
          $option->category_id = $categoryIndex;
          $option->id = $categoryValue;

          $currentItem->options[] = $option;
        }
      }
    }
    $currentItem->updateItemTotals();

    $currentItem->instructions = $form_state->getValue('special_instructions');

    $existingItem = NULL;
    foreach ($cart->order_details->items as $tempItem) {
      $propertiesToIgnore = [
        'Drupal\food\Core\Order\OrderItemOption::index',
        'Drupal\food\Core\Order\OrderItem::quantity',
        'Drupal\food\Core\Order\OrderItem::item_total_amount',
      ];
      if (PhpHelper::compareDeep($currentItem, $tempItem,
        ['propertiesToIgnore' => $propertiesToIgnore])) {
        $existingItem = $tempItem;
        break;
      }
    }

    if ($existingItem != NULL) {
      $existingItem->quantity += $currentItem->quantity;
      $existingItem->item_total_amount += $currentItem->item_total_amount;
    }
    else {
      $cart->order_details->items[] = $currentItem;
    }

    \Drupal\food\Core\CartController::updateCart($cart);

    //Re-calculate cart html.
    $user_cart_block_html = \Drupal\food\Form\Cart\CartController::getCurrentCartHtml(['render_mode' => \Drupal\food\Core\Cart\CartRenderMode::Editable]);

    $response = new AjaxResponse();

    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new HtmlCommand('.user-cart-container',
      $user_cart_block_html));
    $response->addCommand(new InvokeCommand('.block-user-cart-form-block .table-responsive',
      'scrollTop', [1000]));

    $form_state->setResponse($response);
  }

}
