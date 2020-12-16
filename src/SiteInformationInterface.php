<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info;

use Drupal\rdf_skos\Entity\ConceptInterface;

/**
 * Interface for corporate site information service.
 */
interface SiteInformationInterface {

  /**
   * Check whether site owner is set for the site.
   *
   * @return bool
   *   TRUE if set, FALSE if it is not.
   */
  public function hasSiteOwner(): bool;

  /**
   * Get current site owner SKOS concept entity.
   *
   * @return \Drupal\rdf_skos\Entity\Concept
   *   The current site owner SKOS concept entity.
   */
  public function getSiteOwner(): ConceptInterface;

  /**
   * Check whether content owners are set for the site.
   *
   * @return bool
   *   TRUE if set, FALSE if no content owners are set.
   */
  public function hasContentOwners(): bool;

  /**
   * Get current content owner SKOS concept entities.
   *
   * @return \Drupal\rdf_skos\Entity\Concept[]
   *   The current site owner SKOS concept entities.
   */
  public function getContentOwners(): array;

}
