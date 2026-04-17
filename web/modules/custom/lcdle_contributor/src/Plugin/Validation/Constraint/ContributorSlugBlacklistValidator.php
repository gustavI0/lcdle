<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ContributorSlugBlacklist constraint.
 */
final class ContributorSlugBlacklistValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof ContributorSlugBlacklist) {
      return;
    }

    $slug = $this->extractSlug($value);
    if ($slug === NULL) {
      return;
    }

    if (in_array($slug, ContributorSlugBlacklist::RESERVED, TRUE)) {
      $this->context->addViolation($constraint->message, ['@slug' => $slug]);
    }
  }

  /**
   * Extracts the slug string from a field value or raw string.
   */
  private function extractSlug(mixed $value): ?string {
    if (is_string($value)) {
      return strtolower($value);
    }
    if (is_object($value) && method_exists($value, 'getValue')) {
      $items = $value->getValue();
      if (!empty($items[0]['value']) && is_string($items[0]['value'])) {
        return strtolower($items[0]['value']);
      }
    }
    return NULL;
  }

}
