<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pathauto\Entity\PathautoPattern;

/**
 * Tests the Pathauto pattern shipped by lcdle_core.
 *
 * @group lcdle_core
 */
final class PathautoPatternTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system', 'user', 'node', 'text', 'field', 'taxonomy', 'file', 'image', 'media', 'media_library', 'workflows', 'content_moderation', 'views', 'path', 'path_alias', 'token', 'pathauto', 'lcdle_core',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'lcdle_core']);
  }

  /**
   * Tests that the article pathauto pattern is installed.
   */
  public function testArticlePatternExists(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertNotNull($pattern, 'pathauto pattern article exists.');
  }

  /**
   * Tests the pattern targets the node:article bundle.
   */
  public function testArticlePatternTargetsNodeArticle(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertSame('canonical_entities:node', $pattern->getType());
    // Iterate conditions to find the entity_bundle:node condition by plugin ID.
    $found = NULL;
    foreach ($pattern->getSelectionConditions() as $condition) {
      if ($condition->getPluginId() === 'entity_bundle:node') {
        $found = $condition;
        break;
      }
    }
    $this->assertNotNull($found, 'Selection condition entity_bundle:node found.');
    $configuration = $found->getConfiguration();
    $this->assertContains('article', array_keys($configuration['bundles']));
  }

}
