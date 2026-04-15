<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * @group lcdle_core
 */
final class VocabulariesInstallTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'text',
    'field',
    'file',
    'image',
    'media',
    'taxonomy',
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

  /**
   * @dataProvider provideVocabularyIds
   */
  public function testVocabularyExists(string $vid): void {
    $vocabulary = Vocabulary::load($vid);
    $this->assertNotNull($vocabulary, "Vocabulary {$vid} is installed.");
  }

  public static function provideVocabularyIds(): array {
    return [
      'themes' => ['themes'],
      'chroniques' => ['chroniques'],
      'tags_legacy' => ['tags_legacy'],
    ];
  }

}
