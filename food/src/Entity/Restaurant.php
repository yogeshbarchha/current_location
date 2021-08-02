<?php

namespace Drupal\food\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\food\RestaurantInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Restaurant entity.
 *
 * @ingroup food
 *
 * This is the main definition of the entity type. From it, an entityType is
 * derived. The most important properties in this example are listed below.
 *
 * id: The unique identifier of this entityType. It follows the pattern
 * 'moduleName_xyz' to avoid naming conflicts.
 *
 * label: Human readable name of the entity type.
 *
 * handlers: Handler classes are used for different tasks. You can use
 * standard handlers provided by D8 or build your own, most probably derived
 * from the standard class. In detail:
 *
 * - view_builder: we use the standard controller to view an instance. It is
 *   called when a route lists an '_entity_view' default for the entityType
 *   (see routing.yml for details. The view can be manipulated by using the
 *   standard drupal tools in the settings.
 *
 * - list_builder: We derive our own list builder class from the
 *   entityListBuilder to control the presentation.
 *   If there is a view available for this entity from the views module, it
 *   overrides the list builder. @todo: any view? naming convention?
 *
 * - form: We derive our own forms to add functionality like additional fields,
 *   redirects etc. These forms are called when the routing list an
 *   '_entity_form' default for the entityType. Depending on the suffix
 *   (.add/.edit/.delete) in the route, the correct form is called.
 *
 * - access: Our own accessController where we determine access rights based on
 *   permissions.
 *
 * More properties:
 *
 *  - base_table: Define the name of the table used to store the data. Make
 *   sure
 *    it is unique. The schema is automatically determined from the
 *    BaseFieldDefinitions below. The table is automatically created during
 *    installation.
 *
 *  - fieldable: Can additional fields be added to the entity via the GUI?
 *    Analog to content types.
 *
 *  - entity_keys: How to access the fields. Analog to 'nid' or 'uid'.
 *
 *  - links: Provide links to do standard tasks. The 'edit-form' and
 *    'delete-form' links are added to the list built by the
 *    entityListController. They will show up as action buttons in an
 *   additional
 *    column.
 *
 * There are many more properties to be used in an entity type definition. For
 * a complete overview, please refer to the '\Drupal\Core\Entity\EntityType'
 * class definition.
 *
 * The following construct is the actual definition of the entity type which
 * is read and cached. Don't forget to clear cache after changes.
 *
 * @ContentEntityType(
 *   id = "food_restaurant",
 *   label = @Translation("Restaurant"),
 *   handlers = {
 *     "view_builder" = "Drupal\food\Entity\Restaurant\RestaurantViewBuilder",
 *     "list_builder" = "Drupal\food\Entity\Restaurant\RestaurantListBuilder",
 *     "form" = {
 *       "add" = "Drupal\food\Form\Partner\RestaurantForm",
 *       "edit" = "Drupal\food\Form\Partner\RestaurantForm"
 *     },
 *     "access" =
 *   "Drupal\food\Entity\Restaurant\RestaurantAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "food_restaurant",
 *   admin_permission = "administer content types",
 *   entity_keys = {
 *     "id" = "restaurant_id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "canonical" = "/restaurant/{food_restaurant}/menu",
 *     "edit-form" = "/partner/restaurant/edit/{food_restaurant}",
 *     "collection" = "/partner/restaurant/list"
 *   },
 *   field_ui_base_route = "food.restaurant_settings",
 * )
 *
 * The 'links' above are defined by their path. For core to find the
 * corresponding route, the route name must follow the correct pattern:
 *
 * entity.<entity-name>.<link-name> (replace dashes with underscores)
 * Example: 'entity.content_entity_example_contact.canonical'
 *
 * See routing file above for the corresponding implementation
 *
 * The Restaurant class defines methods and fields for the contact entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see RestaurantInterface) also exposes the
 *   EntityOwnerInterface. This allows us to provide methods for setting and
 *   providing ownership information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user
 *   with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 *
 * The class also uses the EntityChangedTrait trait which allows it to record
 * timestamps of save operations.
 */
class Restaurant extends ContentEntityBase implements RestaurantInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   *
   * When a new entity instance is added, set the user_id entity reference to
   * the current user as the creator of the instance.
   */
  public static function preCreate(EntityStorageInterface $storage_controller,
    array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'owner_user_id' => \Drupal::currentUser()->id(),
    ];
  }

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
   */
  public function getOwner() {
    return $this->get('owner_user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('owner_user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('owner_user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('owner_user_id', $account->id());
    return $this;
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
    $fields['restaurant_id'] = BaseFieldDefinition::create('integer')
      ->setReadOnly(TRUE);

    $fields['owner_user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback('Drupal\food\Entity\Restaurant::getCurrentUserId');

    $fields['created_time'] = BaseFieldDefinition::create('integer')
      ->setSetting('size', 'big')
      ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created');

    $fields['changed'] = BaseFieldDefinition::create('changed');

    $fields['status'] = BaseFieldDefinition::create('integer')
      ->setRequired(TRUE)
      ->setDefaultValue(1);

    $fields['image_fid'] = BaseFieldDefinition::create('integer')
      ->setRequired(FALSE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['phone_number'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 50)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['fax_number'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 50)
      ->setSetting('text_processing', 0);

    $fields['email'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 100)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['address_line1'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['address_line2'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0);


    $fields['city'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['postal_code'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 25)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['country'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0)
      ->setRequired(TRUE);

    $fields['latitude'] = BaseFieldDefinition::create('decimal')
      ->setSetting('precision', 15)
      ->setSetting('scale', 8)
      ->setRequired(TRUE);

    $fields['longitude'] = BaseFieldDefinition::create('decimal')
      ->setSetting('precision', 15)
      ->setSetting('scale', 8)
      ->setRequired(TRUE);

    $fields['about_summary'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['about_detailed'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['tag_line'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0);

    $fields['speciality'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 255)
      ->setSetting('text_processing', 0);

    $fields['order_contact_details'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['order_types'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE)
      ->setRequired(TRUE);

    $fields['order_constraints'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['timings'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['delivery_area_type'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['delivery_radius'] = BaseFieldDefinition::create('decimal')
      ->setSetting('precision', 15)
      ->setSetting('scale', 7)
      ->setRequired(TRUE);

    $fields['delivery_polygon'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['user_payment_settings'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['settlement_payment_settings'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['platform_settings'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['deals'] = BaseFieldDefinition::create('string_long')
      ->setSetting('case_sensitive', FALSE);

    $fields['tax_pct'] = BaseFieldDefinition::create('decimal')
      ->setSetting('precision', 10)
      ->setSetting('scale', 3)
      ->setRequired(TRUE)
      ->setDefaultValue(0);

    $fields['url_slug'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0);

    $fields['payment_accept_mode'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0);

    $fields['featured_restaurant'] = BaseFieldDefinition::create('string')
      ->setSetting('max_length', 500)
      ->setSetting('text_processing', 0);
    return $fields;
  }

  /**
   * Default value callback for 'owner_user_id' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

}
