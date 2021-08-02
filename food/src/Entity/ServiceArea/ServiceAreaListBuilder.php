<?php

namespace Drupal\food\Entity\ServiceArea;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for service_area entity.
 *
 * @ingroup food
 */
class ServiceAreaListBuilder extends EntityListBuilder {

  /**
   * The url generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container,
    EntityTypeInterface $entity_type) {
    return new static($entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('url_generator'));
  }

  /**
   * Constructs a new ServiceAreaListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\UrlGeneratorInterface $url_generator
   *   The url generator.
   */
  public function __construct(EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    UrlGeneratorInterface $url_generator) {
    parent::__construct($entity_type, $storage);
    $this->urlGenerator = $url_generator;
  }

  /**
   * {@inheritdoc}
   *
   * We override ::render() so that we can add our own content above the table.
   * parent::render() is where EntityListBuilder creates the table using our
   * buildHeader() and buildRow() implementations.
   */
  public function render() {
    $currentUser = \Drupal\user\Entity\User::load(\Drupal::currentUser()->id());
    $isAdministrator = $currentUser->hasRole(\Drupal\food\Core\RoleController::Administrator_Role_Name);
    if ($isAdministrator) {
      $build['admin_link'] = [
        '#markup' => $this->t('You can manage the fields on the <a href="@adminlink" class="restaurant-admin-button">Admin settings page</a>',
          [
            '@adminlink' => $this->urlGenerator->generateFromRoute('food.service_area_settings'),
          ]),
      ];
    }
    $build['add_button'] = array(
      'add_button' => array(
        '#type' => 'link',
        '#attributes' => array(
          'class' => array(
            'restaurant-admin-button',
          ),
        ),
        '#title' => 'Add Service Area',
        '#url' => Url::fromRoute('food.platform.service_area.add'),
      ),
    );
    $build['table'] = parent::render();
    return $build;
  }

  /**
   * {@inheritdoc}
   *
   * Building the header and content lines for the contact list.
   *
   * Calling the parent::buildHeader() adds a column for the possible actions
   * and inserts the 'edit' and 'delete' links as defined for the entity type.
   */
  public function buildHeader() {
    $header['service_area_id'] = $this->t('Id');
    $header['name'] = $this->t('Name');
    $header['country'] = $this->t('Country');
    $header['radius'] = $this->t('Radius');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\content_entity_example\Entity\Contact */
    $row['service_area_id'] = $entity->id();
    $row['name'] = $entity->link();
    $row['country'] = $entity->country->value;
    $row['radius'] = $entity->radius->value;

    return $row + parent::buildRow($entity);
  }

}
