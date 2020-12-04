<?php

/**
 * @file
 * OpenEuropa Corporate Site Information post updates.
 */

declare(strict_types = 1);

/**
 * Install Multi-value form element contrib module.
 */
function oe_corporate_site_info_post_update_update_dependencies(): void {
  \Drupal::service('module_installer')->install(['multivalue_form_element']);
}
