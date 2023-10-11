<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_corporate_site_info\Functional;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;

/**
 * Tests the functionality of Corporate site information config form.
 */
class CorporateSiteInfoSettingsFormTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'oe_corporate_site_info',
    'link',
    'config_translation',
    'language',
    'block',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->setUpSparql();
    $this->container->get('config.factory')->getEditable('system.site')->set('page.front', '/user')->save();

    // Enable bulgarian language.
    ConfigurableLanguage::createFromLangcode('bg')->save();
    $this->drupalPlaceBlock('local_tasks_block');
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
      'translate configuration',
    ]));
    $this->drupalGet('/admin/config/system/site-information');
    $this->assertSession()->fieldExists('Accessibility statement');
    $this->assertSession()->pageTextContains('Default content owner(s)');
    $this->assertSession()->fieldExists('Site owner');

    $page = $this->getSession()->getPage();
    $assert_session = $this->assertSession();

    // Assert fields are required.
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextContains('Accessibility statement field is required.');
    $this->assertSession()->pageTextContains('Site owner field is required.');
    $this->assertSession()->pageTextContains('Content owner field is required.');
    $page->fillField('Accessibility statement', '<front>');
    $page->fillField('Site owner', 'Directorate-General for Agriculture and Rural Development');
    $page->fillField('content_owners[0][target]', 'Audit Board of the European Communities');
    $page->pressButton('Save configuration');
    $this->assertSession()->pageTextNotContains('Accessibility statement field is required.');
    $this->assertSession()->pageTextNotContains('Site owner field is required.');
    $this->assertSession()->pageTextNotContains('Content owner field is required.');

    $add_more_button = $page->findButton('content_owners_add_more');

    $add_more_button->click();
    $page->fillField('content_owners[1][target]', 'Directorate-General for Budget');

    $page->pressButton('Save configuration');
    $page->fillField('content_owners[2][target]', 'Directorate-General for Climate Action');
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Accessibility statement', '<front>');
    $assert_session->fieldValueEquals('Site owner', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Audit Board of the European Communities (http://publications.europa.eu/resource/authority/corporate-body/ABEC)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');

    $page->fillField('Accessibility statement', 'https://example.com');
    $page->selectFieldOption('content_owners[2][_weight]', -2);
    $page->pressButton('Save configuration');

    $assert_session->fieldValueEquals('Accessibility statement', 'https://example.com');
    $assert_session->fieldValueEquals('Site owner', 'Directorate-General for Agriculture and Rural Development (http://publications.europa.eu/resource/authority/corporate-body/AGRI)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Audit Board of the European Communities (http://publications.europa.eu/resource/authority/corporate-body/ABEC)');
    $assert_session->fieldValueEquals('content_owners[2][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');

    // Assert the accessibility link can be translated.
    $page->clickLink('Translate system information');
    $page->clickLink('Add');
    $assert_session->pageTextContains('Accessibility statement');
    $assert_session->pageTextNotContains('Site owner');
    $assert_session->pageTextNotContains('Default content owner(s)');
    $assert_session->fieldValueEquals('translation[config_names][oe_corporate_site_info.settings][accessibility]', 'https://example.com');
    $page->fillField('translation[config_names][oe_corporate_site_info.settings][accessibility]', 'https://example.com/bg');
    $page->pressButton('Save translation');
    $assert_session->pageTextContains('Successfully saved Bulgarian translation.');

    $page->clickLink('Settings');
    $page->fillField('Accessibility statement', 'Non-existing node');
    $page->fillField('Site owner', 'invalid skos term');
    $page->fillField('content_owners[1][target]', '');
    $page->pressButton('Save configuration');
    $assert_session->pageTextContainsOnce('Manually entered paths should start with one of the following characters: / ? #');
    $assert_session->pageTextContainsOnce('There are no skos concept entities matching "invalid skos term".');
    $page->fillField('Accessibility statement', 'https://example.com');
    $page->fillField('Site owner', 'European Patent Office');

    $page->pressButton('Save configuration');
    $assert_session->fieldValueEquals('Accessibility statement', 'https://example.com');
    $assert_session->fieldValueEquals('Site owner', 'European Patent Office (http://publications.europa.eu/resource/authority/corporate-body/EPOFF)');
    $assert_session->fieldValueEquals('content_owners[0][target]', 'Directorate-General for Climate Action (http://publications.europa.eu/resource/authority/corporate-body/CLIMA)');
    $assert_session->fieldValueEquals('content_owners[1][target]', 'Directorate-General for Budget (http://publications.europa.eu/resource/authority/corporate-body/BUDG)');
    $assert_session->fieldValueEquals('content_owners[2][target]', '');
  }

}
