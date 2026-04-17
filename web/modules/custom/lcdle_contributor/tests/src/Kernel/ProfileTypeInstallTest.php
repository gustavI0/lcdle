<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\ProfileType;

/**
 * Tests the contributor_profile profile type is installed correctly.
 *
 * @group lcdle_contributor
 */
final class ProfileTypeInstallTest extends LcdleContributorKernelTestBase {

  /**
   * Tests the contributor_profile type exists and has the correct label.
   */
  public function testContributorProfileTypeExists(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertNotNull($type, 'Profile type contributor_profile exists.');
    $this->assertSame('Profil contributeur', $type->label());
  }

  /**
   * Tests the contributor_profile type does not allow multiple profiles.
   */
  public function testContributorProfileTypeIsMultiple(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertFalse(
      $type->allowsMultiple(),
      'contributor_profile allows only one profile per user.',
    );
  }

  /**
   * Tests the contributor_profile type is bound to editorial roles.
   */
  public function testContributorProfileTypeIsRoleBound(): void {
    $type = ProfileType::load('contributor_profile');
    $roles = $type->getRoles();
    sort($roles);
    $this->assertSame(
      ['contributor_new', 'contributor_trusted', 'editor'],
      $roles,
      'contributor_profile is bound to the three editorial roles.',
    );
  }

  /**
   * Tests the contributor_profile has the expected field with the correct type.
   *
   * @dataProvider provideExpectedFields
   */
  public function testContributorProfileHasField(string $fieldName, string $expectedType, int $expectedCardinality): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('profile', 'contributor_profile');
    $this->assertArrayHasKey($fieldName, $fields);
    $this->assertSame($expectedType, $fields[$fieldName]->getType());
    $this->assertSame(
      $expectedCardinality,
      $fields[$fieldName]->getFieldStorageDefinition()->getCardinality(),
    );
  }

  /**
   * Provides expected field names, types, and cardinalities.
   *
   * @return array<string, array{string, string, int}>
   *   Keyed by field name with field type and cardinality.
   */
  public static function provideExpectedFields(): array {
    return [
      'field_display_name' => ['field_display_name', 'string', 1],
      'field_bio' => ['field_bio', 'text_long', 1],
      'field_avatar' => ['field_avatar', 'image', 1],
      'field_banner' => ['field_banner', 'image', 1],
      'field_social_links' => ['field_social_links', 'link', -1],
      'field_accent_color' => ['field_accent_color', 'string', 1],
    ];
  }

}
