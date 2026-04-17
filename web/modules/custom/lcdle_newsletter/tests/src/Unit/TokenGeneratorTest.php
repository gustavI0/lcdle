<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Unit;

use Drupal\lcdle_newsletter\Service\TokenGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the token generator service.
 *
 * @group lcdle_newsletter
 */
final class TokenGeneratorTest extends TestCase {

  /**
   * Tests generated token has correct length.
   */
  public function testTokenLength(): void {
    $generator = new TokenGenerator();
    $token = $generator->generate();
    $this->assertSame(64, strlen($token));
  }

  /**
   * Tests token is URL-safe hex.
   */
  public function testTokenIsHexOnly(): void {
    $generator = new TokenGenerator();
    $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $generator->generate());
  }

  /**
   * Tests two tokens differ.
   */
  public function testTokensAreUnique(): void {
    $generator = new TokenGenerator();
    $this->assertNotSame($generator->generate(), $generator->generate());
  }

}
