<?php

declare(strict_types=1);

namespace Drupal\oe_corporate_site_info\Element;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\FormElement;

/**
 * Provides an multiple entity autocomplete form element.
 *
 * The autocomplete form element allows users to select one or multiple
 * entities, which can come from all or specific bundles of an entity type.
 * Some features and behavior of this form element inherited from
 * the Autocomplete widget.
 *
 * @see \Drupal\Core\Field\Plugin\Field\FieldWidget\EntityReferenceAutocompleteWidget
 *
 * Properties:
 * - #add_more_title: (optional) Title of the "add more" button.
 *
 * Usage example:
 * @code
 * $form['my_element'] = [
 *  '#type' => 'oe_corporate_site_info_entity_autocomplete_multiple',
 *  '#target_type' => 'node',
 *  '#default_value' => [
 *    $entity,
 *  ],
 *  '#selection_handler' => 'default',
 *  '#selection_settings' => [
 *    'target_bundles' => ['article', 'page'],
 *   ],
 * ];
 * @endcode
 *
 * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection
 * @see \Drupal\Core\Entity\Element\EntityAutocomplete
 *
 * @FormElement("oe_corporate_site_info_entity_autocomplete_multiple")
 */
class EntityAutocompleteMultiple extends FormElement {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $class = get_class($this);
    return [
      '#theme' => 'field_multiple_value_form',
      '#cardinality' => -1,
      '#cardinality_multiple' => TRUE,
      '#description' => NULL,
      '#add_more_title' => $this->t('Add another item'),
      '#element_validate' => [[$class, 'validateEntityAutocompleteItems']],
      '#process' => [
        [$class, 'processEntityAutocompleteMultiple'],
        [$class, 'processAjaxForm'],
      ],
    ];
  }

  /**
   * Apply multiple value features for the entity autocomplete form element.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The original complete form array.
   *
   * @return array
   *   The array of element.
   */
  public static function processEntityAutocompleteMultiple(array &$element, FormStateInterface $form_state, array &$complete_form): array {
    unset($element['#type']);
    $element['#tree'] = TRUE;
    $element['#field_name'] = $field_name = end($element['#array_parents']);
    $element['#default_value'] = $element['#default_value'] ?? [];
    $parents = $element['#parents'];

    $field_state = [];
    if (static::getWidgetState($parents, $field_name, $form_state) === NULL) {
      $max = count($element['#default_value']);
      $field_state['items_count'] = $max;
      static::setWidgetState($parents, $field_name, $form_state, $field_state);
    }
    else {
      $max = static::getWidgetState($parents, $field_name, $form_state)['items_count'];
    }

    for ($i = 0; $i <= $max; $i++) {
      $element[$i]['target'] = [
        '#type' => 'entity_autocomplete',
        '#target_type' => $element['#target_type'],
        '#selection_handler' => $element['#selection_handler'],
        '#selection_settings' => $element['#selection_settings'],
        '#validate_reference' => FALSE,
        '#maxlength' => $element['#maxlength'],
        '#default_value' => $element['#default_value'][$i] ?? NULL,
        '#size' => $element['#size'],
        '#placeholder' => $element['#placeholder'],
      ];
      $element[$i]['_weight'] = [
        '#type' => 'weight',
        '#title' => t('Weight for row @number', ['@number' => $i + 1]),
        '#title_display' => 'invisible',
        '#default_value' => $i,
        '#weight' => 100,
      ];
    }

    $id_prefix = implode('-', $parents);
    $wrapper_id = Html::getUniqueId($id_prefix . '-add-more-wrapper');
    $element['#prefix'] = '<div id="' . $wrapper_id . '">';
    $element['#suffix'] = '</div>';
    $element['add_more'] = [
      '#type' => 'submit',
      '#name' => strtr($id_prefix, '-', '_') . '_add_more',
      '#value' => $element['#add_more_title'],
      '#attributes' => ['class' => ['field-add-more-submit']],
      '#limit_validation_errors' => [$element['#array_parents']],
      '#submit' => [[get_called_class(), 'addMoreSubmit']],
      '#ajax' => [
        'callback' => [get_called_class(), 'addMoreAjax'],
        'wrapper' => $wrapper_id,
        'effect' => 'fade',
      ],
    ];
    // Remove leftover from root element array.
    unset($element['#maxlength']);
    return $element;
  }

  /**
   * Validate autocomplete field values.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $complete_form
   *   The original complete form array.
   */
  public static function validateEntityAutocompleteItems(array &$element, FormStateInterface $form_state, array &$complete_form) {
    if (empty($element['#required'])) {
      return;
    }

    $values = $form_state->getValue(implode('.', $element['#parents'])) ?? [];
    unset($values['add_more']);
    foreach ($values as $key => $value) {
      if (empty($value['target'])) {
        unset($values[$key]);
      }
    }
    if (empty($values)) {
      $form_state->setError($element, t('You have to select at least 1 content owner.'));
    }

  }

  /**
   * Handles the "Add another item" button AJAX request.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addMoreSubmit(array $form, FormStateInterface $form_state): void {
    $button = $form_state->getTriggeringElement();

    // Go one level up in the form, to the widgets container.
    $element = NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
    $field_name = $element['#field_name'];
    $parents = $element['#parents'];

    // Increment the items count.
    $field_state = static::getWidgetState($parents, $field_name, $form_state);
    $field_state['items_count']++;
    static::setWidgetState($parents, $field_name, $form_state, $field_state);

    $form_state->setRebuild();
  }

  /**
   * Ajax callback for the "Add another item" button.
   *
   * @param array $form
   *   The build form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function addMoreAjax(array $form, FormStateInterface $form_state) {
    $button = $form_state->getTriggeringElement();
    return NestedArray::getValue($form, array_slice($button['#array_parents'], 0, -1));
  }

  /**
   * Helper method for extracting form element state data.
   *
   * @param array $parents
   *   The parents array.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   *
   * @return array|null
   *   Actual form element state.
   */
  public static function getWidgetState(array $parents, string $field_name, FormStateInterface $form_state): ?array {
    return NestedArray::getValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name));
  }

  /**
   * Helper method for updating form element state data.
   *
   * @param array $parents
   *   The parents array.
   * @param string $field_name
   *   The field name.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   * @param array $field_state
   *   The field state.
   */
  public static function setWidgetState(array $parents, string $field_name, FormStateInterface $form_state, array $field_state): void {
    NestedArray::setValue($form_state->getStorage(), static::getWidgetStateParents($parents, $field_name), $field_state);
  }

  /**
   * Returns the location of processing information within $form_state.
   *
   * @param array $parents
   *   The array of #parents where the widget lives in the form.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The location of processing information within $form_state.
   */
  protected static function getWidgetStateParents(array $parents, string $field_name): array {
    // Field processing data is placed at
    // $form_state->get([
    // 'field_storage',
    // '#parents',
    // ...$parents...,
    // '#fields',
    // $field_name,
    // ]),
    // to avoid clashes between field names and $parents parts.
    return array_merge(['field_storage', '#parents'], $parents, [
      '#fields',
      $field_name,
    ]);
  }

}
