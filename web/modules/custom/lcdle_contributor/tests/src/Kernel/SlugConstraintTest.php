<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * Tests slug field constraints on the contributor_profile profile type.
 *
 * @group lcdle_contributor
 */
final class SlugConstraintTest extends LcdleContributorKernelTestBase {

  /**
   * Tests the field_slug field exists and is required.
   */
  public function testFieldSlugExists(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('profile', 'contributor_profile');
    $this->assertArrayHasKey('field_slug', $fields);
    $this->assertTrue($fields['field_slug']->isRequired());
  }

  /**
   * Tests that two profiles cannot share the same slug.
   */
  public function testSlugMustBeUnique(): void {
    $user_a = User::create(['name' => 'alice', 'mail' => 'alice@example.test', 'status' => 1]);
    $user_a->save();
    $user_b = User::create(['name' => 'bob', 'mail' => 'bob@example.test', 'status' => 1]);
    $user_b->save();

    $profile_a = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_a->id(),
      'field_slug' => 'alice',
      'field_display_name' => 'Alice',
    ]);
    $violations_a = $profile_a->validate();
    $this->assertCount(0, $violations_a, 'First alice slug is valid.');
    $profile_a->save();

    $profile_b = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_b->id(),
      'field_slug' => 'alice',
      'field_display_name' => 'Bob',
    ]);
    $violations_b = $profile_b->validate();
    $this->assertGreaterThan(0, $violations_b->count(), 'Duplicate slug is rejected.');
  }

  /**
   * Tests that reserved slugs from the blacklist are rejected.
   *
   * @dataProvider provideBlacklistedSlugs
   */
  public function testSlugBlacklistRejectsReserved(string $slug): void {
    $user = User::create(['name' => 'eve_' . $slug, 'mail' => $slug . '@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => $slug,
      'field_display_name' => 'Eve ' . $slug,
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(
      0,
      $violations->count(),
      "Reserved slug '{$slug}' must be rejected.",
    );
  }

  /**
   * Provides reserved slug values that must be rejected.
   *
   * @return array<int, array{string}>
   *   List of reserved slug strings.
   */
  public static function provideBlacklistedSlugs(): array {
    return [
      ['admin'],
      ['user'],
      ['node'],
      ['api'],
      ['theme'],
      ['newsletter'],
      ['contribute'],
      ['feed'],
      ['rss'],
      ['login'],
    ];
  }

  /**
   * Tests that a slug shorter than the minimum length is rejected.
   */
  public function testShortSlugIsRejected(): void {
    $user = User::create(['name' => 'shortie', 'mail' => 'shortie@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => 'a',
      'field_display_name' => 'Shortie',
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Too-short slug rejected.');
  }

  /**
   * Tests that a slug with uppercase letters is rejected.
   */
  public function testUppercaseSlugIsRejected(): void {
    $user = User::create(['name' => 'caps', 'mail' => 'caps@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => 'Alice',
      'field_display_name' => 'Caps',
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Uppercase slug rejected by regex.');
  }

}
