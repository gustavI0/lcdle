<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * @group lcdle_core
 */
final class ArticleContentTypeTest extends KernelTestBase {

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

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'lcdle_core']);
  }

  public function testArticleNodeTypeExists(): void {
    $type = NodeType::load('article');
    $this->assertNotNull($type, 'Content type article exists.');
    $this->assertSame('Article', $type->label());
  }

  public function testArticleHasBodyField(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $this->assertArrayHasKey('body', $fields, 'article has body field.');
  }

}
