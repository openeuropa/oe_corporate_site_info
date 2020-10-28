<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_corporate_site_info\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests the JavaScript functionality of Corporate site information config form.
 */
class CorporateSiteInfoSettingsFormTest extends WebDriverTestBase {

  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'seven';

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

    $page->fillField('Site owner', 'ACP–EU Joint Assembly');
    $page->fillField('content_owners[0][target]', 'Directorate-General for Agriculture and Rural Development');

    $add_more_button = $page->findButton('content_owners_add_more');

    $add_more_button->click();
    $assert_session->waitForField('content_owners[1][target]');
    $page->fillField('content_owners[1][target]', 'Directorate-General for Budget');

    $page->pressButton('Save configuration');
    $assert_session->waitForField('content_owners[2][target]');
    $page->fillField('content_owners[2][target]', 'Directorate-General for Climate Action');
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Site owner', 'ACP–EU Joint Assembly (http://publications.europa.eu/resource/authority/corporate-body/ACP-EU_JA)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');

    $page->pressButton('Show row weights');
    $page->selectFieldOption('content_owners[2][_weight]', -2);
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Site owner', 'ACP–EU Joint Assembly (http://publications.europa.eu/resource/authority/corporate-body/ACP-EU_JA)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');

    $page->fillField('Site owner', 'invalid skos term');
    $page->fillField('content_owners[1][target]', '');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('There are no entities matching "invalid skos term".');
    $page->fillField('Site owner', 'ACP–EU Joint Assembly');

    $page->pressButton('Save configuration');
    $assert_session->fieldValueEquals('Site owner', 'ACP–EU Joint Assembly (http://publications.europa.eu/resource/authority/corporate-body/ACP-EU_JA)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', '');

    // Content owners field is required.
    $page->fillField('content_owners[0][target]', '');
    $page->fillField('content_owners[1][target]', '');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('You have to select at least 1 content owner.');
  }

}
