<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\AliasManagerInterface as CoreAliasManagerInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\system\Form\SiteInformationForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class replacement for the original SiteInformationForm.
 */
class CorporateSiteInformationForm extends SiteInformationForm {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a CorporateSiteInformationForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   The path alias manager.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   The request context.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, $alias_manager, PathValidatorInterface $path_validator, RequestContext $request_context, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory, $alias_manager, $path_validator, $request_context);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path_alias.manager'),
      $container->get('path.validator'),
      $container->get('router.request_context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $corporate_site_info = $this->config('oe_corporate_site_info.settings');

    $form['oe_site_information'] = [
      '#type' => 'details',
      '#title' => $this->t('Corporate site information'),
      '#open' => TRUE,
    ];

    $site_owner = $corporate_site_info->get('site_owner') ?? NULL;
    $site_owner_entity = NULL;
    if ($site_owner) {
      $site_owner_entity = $this->entityTypeManager->getStorage('skos_concept')->load($site_owner);
    }

    $form['oe_site_information']['site_owner'] = [
      '#title' => $this->t('Site owner'),
      '#type' => 'entity_autocomplete',
      '#target_type' => 'skos_concept',
      '#selection_handler' => 'default:skos_concept',
      '#selection_settings' => [
        'concept_schemes' => [
          'http://publications.europa.eu/resource/authority/corporate-body',
        ],
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
      ],
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => $site_owner_entity,
      '#required' => TRUE,
      '#size' => 60,
      '#placeholder' => '',
    ];

    $content_owner_ids = $corporate_site_info->get('content_owners') ?? [];
    $content_owners_entities = [];
    foreach ($content_owner_ids as $key => $content_owner_id) {
      $skos_entity = $this->entityTypeManager->getStorage('skos_concept')->load($content_owner_id);
      if ($skos_entity instanceof EntityInterface) {
        $content_owners_entities[] = $skos_entity;
      }
    }

    $form['oe_site_information']['content_owners'] = [
      '#title' => $this->t('Default content owner(s)'),
      '#type' => 'oe_corporate_site_info_entity_autocomplete_multiple',
      '#required' => TRUE,
      '#target_type' => 'skos_concept',
      '#selection_handler' => 'default:skos_concept',
      '#selection_settings' => [
        'concept_schemes' => [
          'http://publications.europa.eu/resource/authority/corporate-body',
        ],
        'concept_subset' => 'oe_corporate_site_info_corporate_bodies_department_executive_agencies',
        'match_operator' => 'CONTAINS',
        'match_limit' => 10,
      ],
      '#validate_reference' => FALSE,
      '#maxlength' => 1024,
      '#default_value' => $content_owners_entities,
      '#size' => 60,
      '#placeholder' => '',
      '#description' => $this->t('This is not the writer of the content, but the subject matter expert responsible for keeping this content up to date. <br>When this field is populated, it will provide the default Content owner for all new content on this website. It can be overwritten for every new item.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $corporate_site_info = $this->configFactory()->getEditable('oe_corporate_site_info.settings');
    $corporate_site_info->set('site_owner', $form_state->getValue('site_owner'));
    $content_owners = $form_state->getValue('content_owners', []);
    // Massage values before saving inside config.
    unset($content_owners['add_more']);
    // Reorder content owners by weight.
    usort($content_owners, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, '_weight');
    });
    $content_owner_ids = [];
    foreach ($content_owners as $key => $content_owner) {
      // Remove empty content owner references.
      if (!empty($content_owner['target'])) {
        $content_owner_ids[] = $content_owner['target'];
      }
    }

    $corporate_site_info->set('content_owners', $content_owner_ids);
    $corporate_site_info->save();
  }

}
