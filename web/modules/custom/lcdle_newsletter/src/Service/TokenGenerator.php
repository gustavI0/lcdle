<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Service;

/**
 * Generates cryptographically secure, URL-safe tokens for newsletter opt-in.
 */
final class TokenGenerator {

  /**
   * Generates a 64-character hex token (32 bytes of entropy).
   */
  public function generate(): string {
    return bin2hex(random_bytes(32));
  }

}
