<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_corporate_site_info\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Drupal\DrupalExtension\Context\ConfigContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\rdf_skos\Entity\Concept;

/**
 * Set and assert corporate site information.
 */
class CorporateSiteInformationContext extends RawDrupalContext {

  /**
   * Configuration context from Drupal Behat Extension.
   *
   * @var \Drupal\DrupalExtension\Context\ConfigContext
   */
  protected $configContext;

  /**
   * Gather external contexts.
   *
   * @param \Behat\Behat\Hook\Scope\BeforeScenarioScope $scope
   *   The scenario scope.
   *
   * @BeforeScenario
   */
  public function gatherContexts(BeforeScenarioScope $scope) {
    $environment = $scope->getEnvironment();
    $this->configContext = $environment->getContext(ConfigContext::class);
  }

  /**
   * Set site owner configuration.
   *
   * @param string $label
   *   Site owner SKOS concept label.
   *
   * @Given I set the site owner to :label
   */
  public function setSiteOwner(string $label): void {
    $entity = $this->loadEntityByLabel($label);
    $this->configContext->setConfig('oe_corporate_site_info.settings', 'site_owner', $entity->id());
  }

  /**
   * Assert site owner.
   *
   * @param string $label
   *   Site owner SKOS concept label.
   *
   * @Then the site owner should be set to :arg1
   */
  public function assertSiteOwner(string $label): void {
    /** @var \Drupal\oe_corporate_site_info\SiteInformationInterface $site_information */
    $site_information = \Drupal::service('oe_corporate_site_info.site_information');
    if (!$site_information->hasSiteOwner()) {
      throw new \InvalidArgumentException("No site owner has been set yet.");
    }

    $expected = $this->loadEntityByLabel($label);
    $actual = $site_information->getSiteOwner();
    if ($expected->id() !== $actual->id()) {
      throw new \Exception("The site owner is set to '{$actual->id()}', while is should be set to '{$expected->id()}'.");
    }
  }

  /**
   * Load a SKOS concept entity given its label, if any.
   *
   * @param string $label
   *   Entity label.
   *
   * @return \Drupal\rdf_skos\Entity\Concept
   *   SKOS entity object.
   */
  protected function loadEntityByLabel(string $label): Concept {
    $entities = \Drupal::entityTypeManager()->getStorage('skos_concept')->loadByProperties([
      'pref_label' => $label,
    ]);

    // Fail if no entity is found.
    if (empty($entities)) {
      throw new \InvalidArgumentException("No SKOS concept found with label '{$label}'.");
    }

    // Fail if more than one entity is found.
    if (count($entities) > 1) {
      throw new \InvalidArgumentException("More than one SKOS concept found with label '{$label}'.");
    }

    return reset($entities);
  }

}
