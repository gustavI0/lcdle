<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Unit;

use Drupal\lcdle_contributor\Plugin\Validation\Constraint\ContributorSlugBlacklist;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for slug format validation and blacklist consistency.
 *
 * @group lcdle_contributor
 */
final class SlugValidationUnitTest extends TestCase {

  private const ROUTE_REGEX = '/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/';

  /**
   * Tests the RESERVED blacklist constant is not empty.
   */
  public function testBlacklistIsNotEmpty(): void {
    $this->assertNotEmpty(
      ContributorSlugBlacklist::RESERVED,
      'RESERVED blacklist must not be empty.',
    );
  }

  /**
   * Tests that critical reserved slugs are present in the blacklist.
   *
   * @dataProvider provideCriticalReservedSlugs
   */
  public function testCriticalSlugsAreInBlacklist(string $slug): void {
    $this->assertContains(
      $slug,
      ContributorSlugBlacklist::RESERVED,
      "Critical slug '{$slug}' must be in RESERVED.",
    );
  }

  /**
   * Provides critical reserved slug values that must be in the blacklist.
   *
   * @return array<int, array{string}>
   *   List of critical reserved slugs.
   */
  public static function provideCriticalReservedSlugs(): array {
    return [
      ['admin'],
      ['user'],
      ['node'],
      ['api'],
      ['theme'],
      ['newsletter'],
      ['login'],
      ['feed'],
    ];
  }

  /**
   * Tests the route regex accepts properly formatted slugs.
   *
   * @dataProvider provideValidSlugs
   */
  public function testRouteRegexAcceptsValidSlugs(string $slug): void {
    $this->assertSame(
      1,
      preg_match(self::ROUTE_REGEX, $slug),
      "Valid slug '{$slug}' matches route regex.",
    );
  }

  /**
   * Provides valid slug strings.
   *
   * @return list<array{string}>
   *   List of valid slug arrays.
   */
  public static function provideValidSlugs(): array {
    return [
      ['alice'],
      ['bob-doe'],
      ['roland-derudet'],
      ['user42-a'],
      ['abc'],
    ];
  }

  /**
   * Tests the route regex rejects malformed slugs.
   *
   * @dataProvider provideInvalidSlugs
   */
  public function testRouteRegexRejectsInvalidSlugs(string $slug): void {
    $this->assertSame(
      0,
      preg_match(self::ROUTE_REGEX, $slug),
      "Invalid slug '{$slug}' must NOT match route regex.",
    );
  }

  /**
   * Provides invalid slug strings with descriptive keys.
   *
   * @return array<string, array{string}>
   *   List of invalid slugs keyed by description.
   */
  public static function provideInvalidSlugs(): array {
    return [
      'uppercase' => ['Alice'],
      'too short (1 char)' => ['a'],
      'too short (2 chars)' => ['ab'],
      'starts with hyphen' => ['-alice'],
      'ends with hyphen' => ['alice-'],
      'contains space' => ['alice doe'],
      'contains dot' => ['alice.doe'],
      'empty' => [''],
    ];
  }

  /**
   * Tests reserved slugs pass the regex but are blocked by the blacklist.
   */
  public function testRouteRegexMatchesBlacklistFormatButConstraintRejects(): void {
    // Every RESERVED slug that is >= 3 chars passes the route regex
    // (structurally valid). The blacklist enforcement is the constraint's
    // job, not the route regex.
    foreach (ContributorSlugBlacklist::RESERVED as $reserved) {
      // @phpstan-ignore greaterOrEqual.alwaysTrue
      if (strlen($reserved) >= 3) {
        $this->assertSame(
          1,
          preg_match(self::ROUTE_REGEX, $reserved),
          "Reserved slug '{$reserved}' is format-valid (blacklist constraint enforces rejection, not the route).",
        );
      }
    }
  }

}
