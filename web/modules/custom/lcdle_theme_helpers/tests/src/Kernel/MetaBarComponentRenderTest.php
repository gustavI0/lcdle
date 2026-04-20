<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\Core\Render\Markup;
use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the meta-bar SDC and asserts structural invariants.
 *
 * @group lcdle_theme_helpers
 */
final class MetaBarComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   *
   * The lcdle theme's config/install ships image-style + responsive-image
   * configs; those modules must be present for the theme to install.
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'image',
    'breakpoint',
    'responsive_image',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  /**
   * Renders markup passed into the items slot.
   */
  public function testRendersItemsSlot(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:meta-bar',
      '#slots' => [
        // Wrap in Markup::create so Drupal does not escape the HTML;
        // the scalar path would run #plain_text and render &lt;span&gt;.
        'items' => [
          '#markup' => Markup::create('<span>12 avril 2025</span><span>8 min</span>'),
        ],
      ],
    ]);

    self::assertCount(1, $crawler->filter('.meta-bar'));
    self::assertCount(2, $crawler->filter('.meta-bar > span'));
    self::assertStringContainsString('12 avril', $crawler->filter('.meta-bar')->text());
  }

}
