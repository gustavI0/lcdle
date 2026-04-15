<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * @group lcdle_contributor
 */
final class SlugConstraintTest extends LcdleContributorKernelTestBase {

  public function testFieldSlugExists(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('profile', 'contributor_profile');
    $this->assertArrayHasKey('field_slug', $fields);
    $this->assertTrue($fields['field_slug']->isRequired());
  }

  public function testSlugMustBeUnique(): void {
    $user_a = User::create(['name' => 'alice', 'mail' => 'alice@example.test', 'status' => 1]);
    $user_a->save();
    $user_b = User::create(['name' => 'bob', 'mail' => 'bob@example.test', 'status' => 1]);
    $user_b->save();

    $profile_a = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_a->id(),
      'field_slug' => 'alice',
    ]);
    $violations_a = $profile_a->validate();
    $this->assertCount(0, $violations_a, 'First alice slug is valid.');
    $profile_a->save();

    $profile_b = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_b->id(),
      'field_slug' => 'alice',
    ]);
    $violations_b = $profile_b->validate();
    $this->assertGreaterThan(0, $violations_b->count(), 'Duplicate slug is rejected.');
  }

  /**
   * @dataProvider provideBlacklistedSlugs
   */
  public function testSlugBlacklistRejectsReserved(string $slug): void {
    $user = User::create(['name' => 'eve_' . $slug, 'mail' => $slug . '@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => $slug,
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(
      0,
      $violations->count(),
      "Reserved slug '{$slug}' must be rejected.",
    );
  }

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

  public function testShortSlugIsRejected(): void {
    $user = User::create(['name' => 'shortie', 'mail' => 'shortie@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => 'a',
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Too-short slug rejected.');
  }

  public function testUppercaseSlugIsRejected(): void {
    $user = User::create(['name' => 'caps', 'mail' => 'caps@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => 'Alice',
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Uppercase slug rejected by regex.');
  }

}
