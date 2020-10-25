<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\oe_corporate_site_info\CorporateSiteInformationForm;
use Symfony\Component\Routing\RouteCollection;

/**
 * OpenEuropa Corporate Site Information route subscriber.
 */
class SiteInfoRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $collection->get('system.site_information_settings')
      ->setDefault('_form', CorporateSiteInformationForm::class);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

}
