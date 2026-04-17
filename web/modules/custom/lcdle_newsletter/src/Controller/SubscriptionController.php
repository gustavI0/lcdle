<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Handles newsletter subscription confirmation and unsubscribe actions.
 *
 * The confirm() and unsubscribe() methods are stubs returning placeholder
 * markup. Full token-validation logic is implemented in Task 4.
 */
class SubscriptionController extends ControllerBase {

  /**
   * Placeholder for the double opt-in confirmation endpoint.
   *
   * @param string $token
   *   The 64-character hex token extracted from the URL.
   *
   * @return array<string, string>
   *   A render array with placeholder markup.
   */
  public function confirm(string $token): array {
    return ['#markup' => 'Placeholder — implemented in Task 4.'];
  }

  /**
   * Placeholder for the one-click unsubscribe endpoint.
   *
   * @param string $token
   *   The 64-character hex token extracted from the URL.
   *
   * @return array<string, string>
   *   A render array with placeholder markup.
   */
  public function unsubscribe(string $token): array {
    return ['#markup' => 'Placeholder — implemented in Task 4.'];
  }

}
