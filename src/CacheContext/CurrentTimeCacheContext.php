<?php

namespace Drupal\current_location\CacheContext;

use Drupal\Core\Cache\Context\CacheContextInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 *
 */
class CurrentTimeCacheContext implements CacheContextInterface {
  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $user_current;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(AccountProxy $user_current, ConfigFactoryInterface $config_factory) {
    $this->user_current = $user_current;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Current Time cache context');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    $time = time();
    return $time;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata() {
    return new CacheableMetadata();
  }

}
