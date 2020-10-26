<?php

declare(strict_types = 1);

namespace Drupal\oe_corporate_site_info;

use Drupal\Component\Utility\SortArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\system\Form\SiteInformationForm;

/**
 * Class replacement for the original SiteInformationForm.
 */
class CorporateSiteInformationForm extends SiteInformationForm {

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
      '#default_value' => \Drupal::entityTypeManager()->getStorage('skos_concept')->load($corporate_site_info->get('site_owner') ?? ''),
      '#size' => 60,
      '#placeholder' => '',
    ];

    $content_owners = $corporate_site_info->get('content_owners') ?? [];
    foreach ($content_owners as $key => $content_owner) {
      $skos_entity = \Drupal::entityTypeManager()->getStorage('skos_concept')->load($content_owner ?? '');
      $content_owners[$key] = $skos_entity ?? [];
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
      '#default_value' => $content_owners ?? NULL,
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
    $corporate_site_info = $this->configFactory()->getEditable('oe_corporate_site_info.settings');
    $corporate_site_info->set('site_owner', $form_state->getValue('site_owner'));
    $content_owners = $form_state->getValue('content_owners', []);
    // Massage values before saving inside config.
    unset($content_owners['add_more']);
    // Reorder content owners by weight.
    usort($content_owners, function ($a, $b) {
      return SortArray::sortByKeyInt($a, $b, '_weight');
    });

    foreach ($content_owners as $key => $content_owner) {
      // Remove empty content owner references.
      if (empty($content_owner['target'])) {
        unset($content_owners[$key]);
        continue;
      }
      else {
        $content_owners[$key] = $content_owner['target'];
      }
    }

    $corporate_site_info->set('content_owners', array_values($content_owners));
    $corporate_site_info->save();
    parent::submitForm($form, $form_state);
  }

}
