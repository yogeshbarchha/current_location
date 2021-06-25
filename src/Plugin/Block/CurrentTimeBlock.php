<?php

namespace Drupal\current_location\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\current_location\Services\CurrentTime;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;

/**
 * Provides a 'Current Time' block.
 *
 * @Block(
 *   id = "current_time_block",
 *   admin_label = @Translation("Current Time")
 * )
 */
class CurrentTimeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentTime;

  /**
   * The kill switch.
   *
   * @var \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  protected $killSwitch;

  /**
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\Core\Session\AccountInterface $account
   * @param \Drupal\current_location\Services\CurrentTime $current_time
   * @param \Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, AccountInterface $account, CurrentTime $current_time, KillSwitch $killSwitch) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->configFactory = $config_factory;
    $this->account = $account;
    $this->currentTime = $current_time;
    $this->killSwitch = $killSwitch;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('current_location.current_time'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['label_display' => FALSE];
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    // Do NOT cache a page with this block on it.
    // \Drupal::service('page_cache_kill_switch')->trigger();
    // Mark this page as being uncacheable.
    $this->killSwitch->trigger();

    $time = $this->currentTime->getCurrentTime();

    $renderable['current_time'] = [
      '#theme' => 'current_time_template',
      '#time' => $time,
      '#cache' => ['max-age' => 0, 'contexts' => [], 'tags' => []],
    ];

    return $renderable;
  }

  /**
   * //  * {@inheritdoc}
   * //  .*/
  // Public function getCacheContexts() {
  //   return Cache::mergeContexts(parent::getCacheContexts(), ['current_time']);
  // }.
}
