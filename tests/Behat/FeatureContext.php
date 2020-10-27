<?php

declare(strict_types = 1);

namespace Drupal\Tests\oe_corporate_site_info\Behat;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\Tests\oe_paragraphs\Traits\FieldsTrait;
use Drupal\Tests\oe_paragraphs\Traits\UtilityTrait;
use PHPUnit\Framework\Assert;

/**
 * Provides extra steps definitions specific to component.
 */
class FeatureContext extends RawDrupalContext {

  use FieldsTrait;
  use UtilityTrait;

  /**
   * Assert value of the specific field by the occurrence.
   *
   * @param string $position
   *   The ordinal position of the field amongst the ones with same label.
   * @param string $field
   *   The field label.
   * @param string $value
   *   The field value.
   *
   * @Then the :position :field (field )value is :field_value
   */
  public function assertNthFieldValue(string $position, string $field, string $value) {
    $field = $this->unescapeStepArgument($field);
    $value = $this->unescapeStepArgument($value);
    $position = $this->convertOrdinalToNumber($position) - 1;

    Assert::assertEquals($value, $this->getNthField($field, $position)->getValue());
  }

}
