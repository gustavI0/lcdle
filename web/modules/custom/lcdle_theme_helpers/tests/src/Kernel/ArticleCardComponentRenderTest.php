<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the article-card SDC with all required props.
 *
 * @group lcdle_theme_helpers
 */
final class ArticleCardComponentRenderTest extends ComponentKernelTestBase {

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
   * All required props render in the expected BEM slots.
   */
  public function testRendersAllFields(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:article-card',
      '#props' => [
        'title' => 'Wim Wenders à Tokyo',
        'url' => '/hugob/wim-wenders-tokyo',
        'tag' => 'Cinéma',
        'author_label' => 'Hugo B.',
        'reading_time' => '8 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('article.article-card'));
    self::assertStringContainsString('Wim Wenders à Tokyo', $crawler->filter('.article-card__title')->text());
    self::assertStringContainsString('Cinéma', $crawler->filter('.article-card__tag')->text());
    self::assertStringContainsString('Hugo B.', $crawler->filter('.article-card__meta')->text());
    self::assertSame('/hugob/wim-wenders-tokyo', $crawler->filter('.article-card__title a')->attr('href'));
  }

  /**
   * When no cover slot is passed, a 4:3 placeholder surface renders.
   */
  public function testCoverPlaceholderWhenNoImage(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:article-card',
      '#props' => [
        'title' => 'Test',
        'url' => '/test',
        'tag' => 'Test',
        'author_label' => 'Anon',
        'reading_time' => '1 min',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.article-card__cover--placeholder'));
  }

}
