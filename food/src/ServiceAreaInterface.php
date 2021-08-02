<?php

namespace Drupal\food;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a ServiceAreaInterface entity.
 *
 * We have this interface so we can join the other interfaces it extends.
 *
 * @ingroup food
 */
interface ServiceAreaInterface extends ContentEntityInterface, EntityChangedInterface {

}
