<?php

namespace Drupal\workbench_annotation\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Dynamic Entity Reference valid reference constraint.
 *
 * Verifies that no annotation block the current transition.
 *
 * @Constraint(
 *   id = "ModerationAnnotation",
 *   label = @Translation("Valid workbench annotations", context = "Validation")
 * )
 */
class ModerationAnnotation extends Constraint {

  public $message = 'You cannot @transition until you resolve all important annotations';

}
