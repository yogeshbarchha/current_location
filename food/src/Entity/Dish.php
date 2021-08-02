<?php

namespace Drupal\food\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\food\DishInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the Dish entity.
 *
 * @ingroup food
 *
 * @ContentEntityType(
 *   id = "food_dish",
 *   label = @Translation("Dish"),
 *   handlers = {
 *     "view_builder" = "Drupal\food\Entity\Dish\DishViewBuilder",
 *     "list_builder" = "Drupal\food\Entity\Dish\DishListBuilder",
 *     "form" = {
 *       "add" = "Drupal\food\Form\Platform\DishForm",
 *       "edit" = "Drupal\food\Form\Platform\DishForm",
 *       "delete" = "Drupal\food\Form\Platform\DishDeleteForm"
 *     },
 *     "access" = "Drupal\food\Entity\Dish\DishAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "food_dish",
 *   admin_permission = "administer content types",
 *   entity_keys = {
 *     "id" = "dish_id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "canonical" = "/dish/{food_dish}",
 *     "edit-form" = "/platform/dish/edit/{food_dish}",
 *     "delete-form" = "/platform/dish/delete/{food_dish}",
 *     "collection" = "/platform/dish/list"
 *   },
 *   field_ui_base_route = "food.dish_settings",
 * )
 *
 */
class Dish extends ContentEntityBase implements DishInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getChangedTime() {
    return $this->get('changed')->value;
  }

  /**
   * {@inheritdoc}
   *
   * Define the field properties here.
   *
   * Field name, type and size determine the table structure.
   *
   * In addition, we can define how the field and its content can be manipulated
   * in the GUI. The behaviour of the widgets used can be determined here.
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    // Standard field, used as unique if primary index.
    $fields['dish_id'] = BaseFieldDefinition::create('integer')
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created');

    $fields['changed'] = BaseFieldDefinition::create('changed');

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setRequired(TRUE)
      ->setDefaultValue(1);

    $fields['name'] = BaseFieldDefinition::create('string')
	  ->setSetting('max_length', 500)
	  ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['image_fid'] = BaseFieldDefinition::create('integer')
      ->setRequired(FALSE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
	  ->setSetting('case_sensitive', FALSE);

    $fields['url_slug'] = BaseFieldDefinition::create('string')
	  ->setSetting('max_length', 500)
	  ->setSetting('text_processing', 0);

    return $fields;
  }

}
