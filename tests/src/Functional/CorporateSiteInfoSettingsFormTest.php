<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_corporate_site_info\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests the functionality of Corporate site information config form.
 */
class CorporateSiteInfoSettingsFormTest extends BrowserTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_corporate_site_info',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->setUpSparql();
    $this->container->get('config.factory')->getEditable('system.site')->set('page.front', '/user')->save();
  }

  /**
   * Test site owner and content owner fields on the site information form.
   */
  public function testConfigurationForm(): void {
    $this->drupalLogin($this->createUser([
      'access content',
      'administer site configuration',
      'view published skos concept entities',
      'access administration pages',
      'view the administration theme',
    ]));
    $this->drupalGet('/admin/config/system/site-information');
    $this->assertSession()->pageTextContains('Default content owner(s)');
    $this->assertSession()->fieldExists('Site owner');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Site owner and Content owners fields are required.
    $page->pressButton('Save configuration');
    $this->assertText('Site owner field is required.');
    $this->assertText('Content owner field is required.');
    $page->fillField('Site owner', 'Directorate-General for Agriculture and Rural Development');
    $page->fillField('content_owners[0][target]', 'Directorate-General for Agriculture and Rural Development');
    $page->pressButton('Save configuration');
    $this->assertNoText('Site owner field is required.');
    $this->assertNoText('Content owner field is required.');

    $add_more_button = $page->findButton('content_owners_add_more');

    $add_more_button->click();
    $page->fillField('content_owners[1][target]', 'Directorate-General for Budget');

    $page->pressButton('Save configuration');
    $page->fillField('content_owners[2][target]', 'Directorate-General for Climate Action');
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Site owner', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');

    $page->selectFieldOption('content_owners[2][_weight]', -2);
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Site owner', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');

    $page->fillField('Site owner', 'invalid skos term');
    $page->fillField('content_owners[1][target]', '');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('There are no entities matching "invalid skos term".');
    $page->fillField('Site owner', 'Directorate-General for Agriculture and Rural Development');

    $page->pressButton('Save configuration');
    $assert_session->fieldValueEquals('Site owner', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', '');
  }

}
