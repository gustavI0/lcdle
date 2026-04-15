<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the custom fields on the article content type.
 *
 * @group lcdle_core
 */
final class ArticleFieldsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'text',
    'field',
    'taxonomy',
    'file',
    'image',
    'media',
    'views',
    'media_library',
    'workflows',
    'content_moderation',
    'path',
    'path_alias',
    'token',
    'pathauto',
    'lcdle_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'lcdle_core']);
  }

  /**
   * Tests the article content type has expected fields with correct types.
   *
   * @dataProvider provideExpectedFields
   */
  public function testArticleHasField(string $fieldName, string $expectedType): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $this->assertArrayHasKey($fieldName, $fields, "article has field {$fieldName}.");
    $this->assertSame(
      $expectedType,
      $fields[$fieldName]->getType(),
      "article.{$fieldName} is type {$expectedType}.",
    );
  }

  /**
   * Provides expected field names and types for the article content type.
   *
   * @return array<string, array{string, string}>
   */
  public static function provideExpectedFields(): array {
    return [
      'field_themes (multi)' => ['field_themes', 'entity_reference'],
      'field_chronique (single)' => ['field_chronique', 'entity_reference'],
      'field_cover_image (media)' => ['field_cover_image', 'entity_reference'],
      'field_excerpt (text)' => ['field_excerpt', 'string_long'],
    ];
  }

  /**
   * Tests field_themes is multi-value (unlimited cardinality).
   */
  public function testFieldThemesIsMultiValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_themes']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(-1, $cardinality, 'field_themes is unlimited cardinality.');
  }

  /**
   * Tests field_chronique is single-value (cardinality 1).
   */
  public function testFieldChroniqueIsSingleValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_chronique']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(1, $cardinality, 'field_chronique is single value.');
  }

  /**
   * Tests field_themes is required on the article form.
   */
  public function testFieldThemesIsRequired(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $this->assertTrue($fields['field_themes']->isRequired(), 'field_themes is required (an article must be tagged).');
  }

}
