<?php

namespace Drupal\food\Form\Partner;

use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing;
use Drupal\Core\Url;

class MenuItemForm extends FormBase {
	
	public function getFormId() {
		return 'food_restaurant_menu_item_form';
	}

	public function buildForm(array $form, FormStateInterface $form_state) {
		$restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$menu_id = \Drupal::routeMatch()->getParameter('menu_id');
		$restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
		$restaurant_menu_section_id = \Drupal::routeMatch()->getParameter('restaurant_menu_section_id');
		
		$entity = $this->getEntity(FALSE);
		if($entity != NULL) {
			$entity = (object) $entity;
		}
		
        $form['restaurant_item_active'] = array(
            '#type' => 'checkbox',
            '#title' => $this->t('Activate/Deactivate Item'),
            '#attributes' => array(
                'class' => array(
                    'restaurant_item_active'
                )
            ),
            '#default_value' => $entity != NULL ? $entity->status : \Drupal\food\Core\EntityStatus::Enabled,
        );
        		
		$form['name'] = array(
			'#type' => 'textfield',
			'#title' => $this->t('Name'),
			'#required' => TRUE,
			'#default_value' => $entity != NULL ? $entity->name : '',
		);
		$form['description'] = array(
			'#type' => 'textarea',
			'#title' => $this->t('Description'),
			'#default_value' => $entity != NULL ? $entity->description : '',
		);
		$form['price'] = array(
			'#type' => 'number',
			'#title' => $this->t('Price'),
			'#step' => '.01',
			'#required' => TRUE,
			'#attributes' => array(
				'id' => 'item_price',
				'max' => '999999',
				'min' => '0'
			),
			'#default_value' => $entity != NULL ? $entity->price : '',
		);

		/*$dishes = \Drupal\food\Core\DishController::getAllDishes();
		if(count($dishes) > 0) {
			$dishOptions = ['' => $this->t('Select')];
			foreach($dishes as $dish) {
				$dishOptions[$dish->dish_id] = $dish->name;
			}
			$form['dish_id'] = array(
				'#type' => 'select',
				'#title' => $this->t('Select Dish'),
				'#options' => $dishOptions,
				'#default_value' => $entity != NULL ? $entity->dish_id : NULL,
			);
		}*/
		/*$form['tax_pct'] = array(
			'#type' => 'number',
			'#title' => $this->t('Tax'),
			'#step' => '.001',
			'#required' => TRUE,
			'#attributes' => array(
				'id' => 'item_tax',
				'max' => '99',
				'min' => '0'
			),
			'#default_value' => $entity != NULL ? $entity->tax_pct : '',
		);*/
        if ($entity != NULL && is_numeric($entity->image_fid)) {
            $picture = \Drupal\file\Entity\File::load($entity->image_fid);
			if($picture) {
				$form['current_picture'] = array(
					'#type' => 'html_tag',
					'#title' => t('Current Picture'),
					'#tag' => 'img',
					'#attributes' => array(
						'src' => $picture->url(),
						'style' => 'max-height: 150px;'
					),
				);
			}
        }
		$form['picture'] = array(
			'#type' => 'managed_file',
			'#title' => $this->t('Picture'),
			'#upload_validators' => array(
			   'file_validate_extensions' => array('png', 'jpg', 'jpeg', 'gif'),
			   'file_validate_size' => 4194304, //4 MB
			),
			'#theme' => 'image_widget',
			'#preview_image_style' => 'medium',
			'#upload_location' => $this->getUploadLocation(),
		);
		
		$form['item_sizes'] = array(
			'#type' => 'fieldset',
			'#title' => $this->t('Sizes')
		);
		$form['item_sizes']['size_add_button'] = array(
			'#type' => 'html_tag',
			'#tag' => 'div',
			'#value' => 'Add Size',
			'#attributes' => array(
				'class' => array('btn', 'btn-info'),
				'id' => 'size_add_button'
			)
		);
		$form['item_sizes']['size_table'] = array(
			'#type' => 'table',
			'#attributes' => array(
				'id' => 'size_table'
			),
			'#header' => array(
				$this->t('Name'),
				$this->t('Price'),
				$this->t('Default'),
				$this->t('Delete')
			)
		);
		
		$form['variations'] = array(
			'#type' => 'hidden',
			'#attributes' => array(
				'id' => 'variations'
			),
			'#default_value' => $entity != NULL ? json_encode($entity->variations) : ''
		);

		$sizes = $entity != NULL && $entity->variations != NULL ? $entity->variations->sizes : NULL;
		if ($sizes != NULL) {
			foreach($sizes as $sizeIndex => $size) {
				$form['item_sizes']['size_table'][$sizeIndex]['#attributes'] = array(
					'class' => array('size_row')
				);
				$form['item_sizes']['size_table'][$sizeIndex]['size_name'] = array(
					'#type' => 'textfield',
					'#attributes' => array(
						'class' => array('size_name')
					),
					'#default_value' => $size->name
				);
				$form['item_sizes']['size_table'][$sizeIndex]['size_price'] = array(
					'#type' => 'number',
					'#step' => '.01',
					'#attributes' => array(
						'class' => array('size_price'),
						'max' => '999999',
						'min' => '0'
					),
					'#default_value' => $size->price
				);
				$form['item_sizes']['size_table'][$sizeIndex]['size_default'] = array(
					'#type' => 'radio',
					'#attributes' => array(
						'class' => array('size_default', 'radio'),
						'checked' => $size->is_default ? 1 : NULL
					),
					'#title' => ''
				);
				$form['item_sizes']['size_table'][$sizeIndex]['size_remove'] = array(
					'#type' => 'html_tag',
					'#tag' => 'span',
					'#attributes' => array(
						'class' => array('glyphicon', 'glyphicon-trash', 'remove-size')
					)
				);
			}
		}

		$form['item_variations'] = array(
			'#type' => 'fieldset',
			'#title' => $this->t('Categories')
		);
		$form['item_variations']['variation_add_category_button'] = array(
			'#type' => 'html_tag',
			'#tag' => 'div',
			'#value' => 'Add Category',
			'#attributes' => array(
				'class' => array('btn', 'btn-info'),
				'id' => 'variation_add_category_button'
			)
		);
		$form['item_variations']['container_categories'] = array(
			'#type' => 'container',
			'#attributes' => array(
				'class' => array('container_categories')
			)
		);

		$categories = $entity != NULL && $entity->variations != NULL ? $entity->variations->categories : NULL;
		if ($categories != NULL) {
			$opCounter = 0;
			foreach($categories as $categoryIndex => $category) {
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex] = array(
					'#type' => 'table',
					'#attributes' => array(
						'class' => array('item_cat_table')
					),
				);
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_name'] = array(
					'#type' => 'textfield',
					'#attributes' => array(
						'class' => array('category_name'),
						'placeholder' => 'Category Name'
					),
					'#default_value' => $category->name
				);
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['display_type'] = array(
					'#type' => 'select',
					'#options' => [
						'checkbox' => $this->t('Checkbox (Choose several)'),
						'dropdown' => $this->t('Dropdown (Choose one)'),
						'radio' => $this->t('Radio (Choose one)'),
					],
					'#attributes' => array(
						'class' => array('display_type')
					),
					'#default_value' => array($category->display_type => $category->display_type),
				);
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_add_button'] = array(
					'#type' => 'html_tag',
					'#tag' => 'a',
					'#value' => $this->t('Add Option'),
					'#attributes' => array(
						'class' => array('category_option_add_button', 'btn', 'btn-info'),
						'href' => 'javascript:;'
					)
				);
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['required_checkbox'] = array(
					'#type' => 'checkbox',
					'#title' => 'Required',
					'#attributes' => array(
						'class' => array('category_required')
					),
					'#default_value' => $category->required,
				);
				$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['variation_remove_category_button'] = array(
					'#type' => 'html_tag',
					'#tag' => 'span',
					'#attributes' => array(
						'class' => array('glyphicon', 'glyphicon-trash', 'variation_remove_category_button')
					)
				);
				
				if ($category->options != NULL && count($category->options) > 0) {
					$opCounter++;
					foreach($category->options as $categoryOptionIndex => $categoryOption) {
						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['#attributes'] = array(
							'class' => array('category_option_row'),
						);
						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_name'] = array(
							'#type' => 'textfield',
							'#attributes' => array(
								'class' => array('category_option_name'),
								'placeholder' => $this->t('Item Name'),
							),
							'#default_value' => $categoryOption->name
						);
						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_price'] = array(
							'#type' => 'number',
							'#step' => '.01',
							'#attributes' => array(
								'class' => array('category_option_price'),
								'placeholder' => $this->t('Item Price'),
								'max' => '999999',
								'min' => '0'
							),
							'#default_value' => $categoryOption->price
						);
						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_is_price_pct'] = array(
							'#type' => 'checkbox',
							'#title' => $this->t('Price in %'),
							'#default_value' => $categoryOption->is_price_pct ? 1 : NULL,
							'#attributes' => array(
								'class' => array('category_option_is_price_pct'),
							),
						);
						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_default'] = array(
							'#type' => 'radio',
							'#title' => $this->t('Default'),
							'#attributes' => array(
								'class' => array('category_option_default'),
								'checked' => $categoryOption->is_default ? 1 : NULL,
							),
							'#wrapped_label' => TRUE,
						);

						$form['item_variations']['container_categories']['item_cat_table-' . $categoryIndex][$opCounter]['category_option_remove_button'] = array(
							'#type' => 'html_tag',
							'#tag' => 'span',
							'#attributes' => array(
								'class' => array('glyphicon', 'glyphicon-trash', 'category_option_remove_button'),
							),
						);
						
						$opCounter++;
					}
				}
				else {
					$opCounter++;
				}
			}
		}

		$form['actions']['#type'] = 'actions';
		$form['actions']['submit'] = array(
			'#type' => 'submit',
			'#value' => t('Submit'),
			'#button_type' => 'primary',
			'#attributes' => array(
				'id' => 'button_submit'
			)
		);
		
		$form['#attached']['library'][] = 'food/form.partner.menuitemform';
		return ($form);
	}

	public function validateForm(array &$form, FormStateInterface $form_state) {
	}

	public function submitForm(array & $form, FormStateInterface $form_state) {
		$restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$menu_id = \Drupal::routeMatch()->getParameter('menu_id');
		$restaurant_menu_id = \Drupal::routeMatch()->getParameter('restaurant_menu_id');
		$restaurant_menu_section_id = \Drupal::routeMatch()->getParameter('restaurant_menu_section_id');
		$restaurant_menu_item_id = \Drupal::routeMatch()->getParameter('restaurant_menu_item_id');
		
		$entity = $this->getEntity();

        $entity = array_merge($entity, array(
			'menu_id' => $menu_id,
			'restaurant_menu_id' => $restaurant_menu_id,
			'restaurant_menu_section_id' => $restaurant_menu_section_id,
			'name' => $form_state->getValue('name'),
			'description' => $form_state->getValue('description'),
			'price' => $form_state->getValue('price'),
			'tax_pct' => 0,
			'variations' => $form_state->getValue('variations'),
            'status' => $form_state->getValue('restaurant_item_active'),
		));

		if(isset($form['dish_id'])) {
			$entity['dish_id'] = empty($form_state->getValue('dish_id')) ? NULL : $form_state->getValue('dish_id');
		}
		
		$picture = $form_state->getValue('picture');
		if(!empty($picture)) {
			$file = \Drupal\file\Entity\File::load($picture[0]);
			$file->setPermanent();
			$file->save();

			$file_usage = \Drupal::service('file.usage');
			$file_usage->add($file, 'food', 'food_restaurant_menu_item', $file->id());
			
			$entity['image_fid'] = $file->id();
		}
		
		if(isset($entity['restaurant_menu_item_id'])) {
			$entity = \Drupal\food\Core\ControllerBase::prepareForUpdation('food_restaurant_menu_item', $entity);
			db_update('food_restaurant_menu_item')
				->fields($entity)
				->condition('restaurant_menu_item_id', $entity['restaurant_menu_item_id'])
				->execute();
				
			drupal_set_message(t('Item updated successfully...'));
		} else {
			db_insert('food_restaurant_menu_item')
				->fields($entity)
				->execute();
				
			drupal_set_message(t('Item added successfully.'));
		}

		\Drupal\food\Core\RestaurantController::updateRestaurant($restaurant_id);
		
		$url = Url::fromRoute('food.partner.restaurant.menu.section.item.list', ['restaurant_id' => $restaurant_id, 'menu_id' => $menu_id, 'restaurant_menu_id' => $restaurant_menu_id, 'restaurant_menu_section_id' => $restaurant_menu_section_id]);
		$form_state->setRedirectUrl($url);
	}

	private function getEntity($createDefault = TRUE) {
		$restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$restaurant_menu_item_id = \Drupal::routeMatch()->getParameter('restaurant_menu_item_id');
		
		//\Drupal\food\Core\UserController::validateCurrentUserOrAdmin($restaurant_id);
		
		$entity = NULL;
		if ($restaurant_menu_item_id != NULL) {
			$entity = (array) \Drupal\food\Core\MenuController::getRestaurantMenuItem($restaurant_menu_item_id);
		} else {
			if ($createDefault) {
				$restaurant = \Drupal\food\Core\RestaurantController::getRestaurantById($restaurant_id);
				$entity = array(
					'owner_user_id' => $restaurant->owner_user_id,
					'restaurant_id' => $restaurant_id,
				);
			}
		}

		return ($entity);
	}
	
	private function getUploadLocation () {
		$restaurant_id = \Drupal\food\Util::getRestaurantIdFromUrl();
		$dir = 'public://images/partner/menu/' . $restaurant_id;
		
		if(!file_prepare_directory($dir, FILE_MODIFY_PERMISSIONS)) {
			$service = \Drupal::service('file_system');
			$service->mkdir($dir, NULL, TRUE);
		}
		
		return ($dir);
	}
}
