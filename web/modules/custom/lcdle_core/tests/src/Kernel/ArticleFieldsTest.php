<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_core
 */
final class ArticleFieldsTest extends KernelTestBase {

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
    'lcdle_core',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'lcdle_core']);
  }

  /**
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

  public static function provideExpectedFields(): array {
    return [
      'field_themes (multi)' => ['field_themes', 'entity_reference'],
      'field_chronique (single)' => ['field_chronique', 'entity_reference'],
      'field_cover_image (media)' => ['field_cover_image', 'entity_reference'],
      'field_excerpt (text)' => ['field_excerpt', 'string_long'],
    ];
  }

  public function testFieldThemesIsMultiValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_themes']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(-1, $cardinality, 'field_themes is unlimited cardinality.');
  }

  public function testFieldChroniqueIsSingleValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_chronique']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(1, $cardinality, 'field_chronique is single value.');
  }

}
