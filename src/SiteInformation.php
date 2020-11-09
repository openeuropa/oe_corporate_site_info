<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_skos\Entity\Concept;

/**
 * Get current corporate site information.
 */
class SiteInformation implements SiteInformationInterface {

  /**
   * Site information configuration name.
   */
  const CONFIG_NAME = 'oe_corporate_site_info.settings';

  /**
   * SKOS concept entity storage.
   *
   * @var \Drupal\Core\Entity\ContentEntityStorageInterface
   */
  protected $entityStorage;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * SiteInformation constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Configuration factory service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, ConfigFactoryInterface $configFactory) {
    $this->entityStorage = $entityTypeManager->getStorage('skos_concept');
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSiteOwner(): bool {
    return (bool) $this->configFactory->get(self::CONFIG_NAME)->get('site_owner');
  }

  /**
   * {@inheritdoc}
   */
  public function getSiteOwner(): Concept {
    $id = $this->configFactory->get(self::CONFIG_NAME)->get('site_owner');
    return $this->entityStorage->load($id);
  }

}
