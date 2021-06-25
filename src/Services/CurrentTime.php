<?php

namespace Drupal\current_location\Services;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
* @file providing the service that display current time.
*
*/

/**
 * Class CurrentTime.
 */
class CurrentTime {

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

   /**
   * A date time instance.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Constructs \Drupal\current_location\Services.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $config_factory
   *   A config factory.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   A date time instance.
   */
  public function __construct(DateFormatterInterface $date_formatter, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    $this->dateFormatter = $date_formatter;
    $this->configFactory = $config_factory;
    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('config.factory'),
      $container->get('datetime.time'),
    );
  }

  /**
   *
   */
  public function getCurrentTime() {
    // Do something here to get any data.
    $config = $this->configFactory->getEditable('current_location.settings');
    $timezone = !empty($config->get('timezone')) ? $config->get('timezone') : drupal_get_user_timezone();
    $country = !empty($config->get('country')) ? $config->get('country') : '';
    $city = !empty($config->get('city')) ? $config->get('city') : '';
    $time = $this->dateFormatter->format($this->time->getCurrentTime(), 'custom', 'jS M Y \- H:i:s a', $timezone);
    return $country .' '. $time .' '. $config->get('timezone');
  }

}
