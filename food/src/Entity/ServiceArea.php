<?php

namespace Drupal\food\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\food\ServiceAreaInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Entity\EntityChangedTrait;

/**
 * Defines the ServiceArea entity.
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
 *  - base_table: Define the name of the table used to store the data. Make sure
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
 *    entityListController. They will show up as action buttons in an additional
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
 *   id = "food_service_area",
 *   label = @Translation("Service Area"),
 *   handlers = {
 *     "view_builder" = "Drupal\food\Entity\ServiceArea\ServiceAreaViewBuilder",
 *     "list_builder" = "Drupal\food\Entity\ServiceArea\ServiceAreaListBuilder",
 *     "form" = {
 *       "add" = "Drupal\food\Form\Platform\ServiceAreaForm",
 *       "edit" = "Drupal\food\Form\Platform\ServiceAreaForm",
 *       "delete" = "Drupal\food\Form\Platform\ServiceAreaDeleteForm"
 *     },
 *     "access" = "Drupal\food\Entity\ServiceArea\ServiceAreaAccessControlHandler",
 *   },
 *   list_cache_contexts = { "user" },
 *   base_table = "food_service_area",
 *   admin_permission = "administer content types",
 *   entity_keys = {
 *     "id" = "service_area_id",
 *     "label" = "name"
 *   },
 *   links = {
 *     "canonical" = "/service_area/{food_service_area}",
 *     "edit-form" = "/platform/service_area/edit/{food_service_area}",
 *     "delete-form" = "/platform/service_area/delete/{food_service_area}",
 *     "collection" = "/platform/service_area/list"
 *   },
 *   field_ui_base_route = "food.service_area_settings",
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
 * The ServiceArea class defines methods and fields for the ServiceArea entity.
 *
 * Being derived from the ContentEntityBase class, we can override the methods
 * we want. In our case we want to provide access to the standard fields about
 * creation and changed time stamps.
 *
 * Our interface (see ServiceAreaInterface) also exposes the EntityOwnerInterface.
 * This allows us to provide methods for setting and providing ownership
 * information.
 *
 * The most important part is the definitions of the field properties for this
 * entity type. These are of the same type as fields added through the GUI, but
 * they can by changed in code. In the definition we can define if the user with
 * the rights privileges can influence the presentation (view, edit) of each
 * field.
 *
 * The class also uses the EntityChangedTrait trait which allows it to record
 * timestamps of save operations.
 */
class ServiceArea extends ContentEntityBase implements ServiceAreaInterface {

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
    $fields['service_area_id'] = BaseFieldDefinition::create('integer')
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

    $fields['address'] = BaseFieldDefinition::create('string')
	  ->setSetting('max_length', 500)
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

    $fields['radius'] = BaseFieldDefinition::create('decimal')
	  ->setSetting('precision', 15)
	  ->setSetting('scale', 7)
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
