<?php

declare(strict_types=1);

namespace Drupal\Tests\oe_corporate_site_info\Behat;

use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\TableNode;
use Drupal\DrupalExtension\Context\ConfigContext;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\rdf_skos\Entity\Concept;
use Drupal\rdf_skos\Entity\ConceptInterface;

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
   * @param string $labels
   *   Site owner SKOS concept labels which can be delimited by comma.
   *
   * @Given I set the site owner to :labels
   */
  public function setSiteOwner(string $labels): void {
    $labels = explode(', ', $labels);
    $entity_ids = [];
    foreach ($labels as $label) {
      $entity = $this->loadSkosConceptByLabel($label);
      if (!$entity instanceof ConceptInterface) {
        throw new \InvalidArgumentException("The SKOS concept with label '{$label}' is not a valid Concept entity.");
      }
      $entity_ids[] = $entity->id();
    }
    $this->setConfigValues('oe_corporate_site_info.settings', 'site_owners', $entity_ids);
  }

  /**
   * Assert site owner.
   *
   * @param string $label
   *   Site owner SKOS concept label.
   *
   * @Then the site owner should be set to :label
   */
  public function assertSiteOwner(string $label): void {
    /** @var \Drupal\oe_corporate_site_info\SiteInformationInterface $site_information */
    $site_information = \Drupal::service('oe_corporate_site_info.site_information');
    if (!$site_information->hasSiteOwners()) {
      throw new \InvalidArgumentException("No site owner has been set yet.");
    }

    $expected = $this->loadSkosConceptByLabel($label);
    $actual_site_owners = $site_information->getSiteOwners();
    foreach ($actual_site_owners as $actual_site_owner) {
      if ($expected->id() === $actual_site_owner->id()) {
        return;
      }
    }

    throw new \Exception("The site owner should be set to '{$expected->id()}'.");
  }

  /**
   * Set site default content owner configuration.
   *
   * @param string $label
   *   Content owner SKOS concept label.
   *
   * @Given I set the site default content owner to :label
   */
  public function setSiteDefaultContentOwner(string $label): void {
    $entity = $this->loadSkosConceptByLabel($label);
    $this->setConfigValues('oe_corporate_site_info.settings', 'content_owners', [$entity->id()]);
  }

  /**
   * Assert the site default content owner.
   *
   * @param string $label
   *   Content owner SKOS concept label.
   *
   * @Then the site default content owner should be set to :label
   */
  public function assertSiteDefaultContentOwner(string $label): void {
    /** @var \Drupal\oe_corporate_site_info\SiteInformationInterface $site_information */
    $site_information = \Drupal::service('oe_corporate_site_info.site_information');
    if (!$site_information->hasDefaultContentOwners()) {
      throw new \InvalidArgumentException("No content owners have been set yet.");
    }

    $expected = $this->loadSkosConceptByLabel($label);
    $owners = $site_information->getDefaultContentOwners();
    foreach ($owners as $owner) {
      if ($expected->id() === $owner->id()) {
        return;
      }
    }

    throw new \Exception("No '{$expected->id()}' content owner found.");
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
  protected function loadSkosConceptByLabel(string $label): Concept {
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

  /**
   * Prepare data and set config value through the 'setComplexConfig'  method.
   *
   * @param string $name
   *   Configuration name.
   * @param string $config_key
   *   Configuration key.
   * @param array $values
   *   Values which should be passed to the config.
   */
  protected function setConfigValues(string $name, string $config_key, array $values): void {
    $table_array = [
      [
        'key',
        'value',
      ],
    ];
    foreach ($values as $key => $value) {
      $table_array[] = [
        $key,
        $value,
      ];
    }
    $table_node = new TableNode($table_array);
    $this->configContext->setComplexConfig($name, $config_key, $table_node);
  }

}
