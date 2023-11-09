<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info\EventSubscriber;

use Drupal\config_translation\Event\ConfigMapperPopulateEvent;
use Drupal\config_translation\Event\ConfigTranslationEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Adds configuration names to configuration mapper on POPULATE_MAPPER event.
 */
class ConfigNamesMapperSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    if (class_exists('Drupal\config_translation\Event\ConfigTranslationEvents')) {
      return [
        ConfigTranslationEvents::POPULATE_MAPPER => ['addConfigNames'],
      ];
    }
    return [];
  }

  /**
   * Reacts to the populating of a configuration mapper.
   *
   * We include the oe_corporate_site_info settings config into the
   * system.site config translation.
   *
   * @param \Drupal\config_translation\Event\ConfigMapperPopulateEvent $event
   *   The configuration mapper event.
   */
  public function addConfigNames(ConfigMapperPopulateEvent $event) {
    $mapper = $event->getMapper();
    if ($mapper->getBaseRouteName() === 'system.site_information_settings') {
      $mapper->addConfigName('oe_corporate_site_info.settings');
    }
  }

}
