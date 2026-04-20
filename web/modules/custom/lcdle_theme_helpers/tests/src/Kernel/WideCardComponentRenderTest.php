<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the wide-card SDC and asserts image-less structure.
 *
 * @group lcdle_theme_helpers
 */
final class WideCardComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
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
   * Renders every required prop into the expected BEM element.
   */
  public function testRendersAllFields(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:wide-card',
      '#props' => [
        'title' => 'FIP la nuit — une radio pour ne pas dormir',
        'url' => '/gust/fip-la-nuit',
        'tag' => 'Électronique',
        'author_label' => 'Gus T.',
        'published_date' => '10 avr.',
        'reading_time' => '5 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('article.wide-card'));
    self::assertStringContainsString(
      'FIP la nuit',
      $crawler->filter('.wide-card__title')->text()
    );
    self::assertStringContainsString(
      'Électronique',
      $crawler->filter('.wide-card__tag')->text()
    );
    self::assertStringContainsString(
      'Gus T.',
      $crawler->filter('.wide-card__meta')->text()
    );
    self::assertStringContainsString(
      '5 min',
      $crawler->filter('.wide-card__meta')->text()
    );
  }

  /**
   * Wide-card never renders an image or cover element.
   */
  public function testNoCoverImageEverRendered(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:wide-card',
      '#props' => [
        'title' => 'T',
        'url' => '/t',
        'tag' => 'X',
        'author_label' => 'A',
        'published_date' => '1 janv.',
        'reading_time' => '1 min',
      ],
    ]);

    self::assertCount(0, $crawler->filter('img'));
    self::assertCount(0, $crawler->filter('.wide-card__cover'));
  }

}
