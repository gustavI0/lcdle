<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Unit;

use Drupal\lcdle_theme_helpers\FontPreloadHelper;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the FontPreloadHelper service.
 *
 * Covers the "which weights get preloaded" policy: exactly the two
 * critical regular weights, pointing at self-hosted theme fonts, with
 * no external domain leaking in.
 *
 * @group lcdle_theme_helpers
 */
final class FontPreloadHelperTest extends TestCase {

  /**
   * The helper returns exactly the two critical regular weights.
   */
  public function testGetPreloadLinksReturnsTheCriticalWeights(): void {
    $helper = new FontPreloadHelper();
    $links = $helper->getPreloadLinks();

    // Exactly two preloads: Playfair Regular + Inter Regular.
    self::assertCount(2, $links);

    // Every link must have the expected rel + as + type + crossorigin.
    foreach ($links as $link) {
      self::assertSame('preload', $link['rel']);
      self::assertSame('font', $link['as']);
      self::assertSame('font/woff2', $link['type']);
      self::assertSame('anonymous', $link['crossorigin']);
      self::assertStringEndsWith('.woff2', $link['href']);
    }
  }

  /**
   * Preload hrefs point into the self-hosted theme fonts directory.
   */
  public function testPathsPointIntoTheThemeFontsDir(): void {
    $helper = new FontPreloadHelper();
    $links = $helper->getPreloadLinks();

    foreach ($links as $link) {
      self::assertStringContainsString(
        '/themes/custom/lcdle/fonts/',
        $link['href'],
        'Preload href must target the self-hosted theme fonts directory.'
      );
    }
  }

  /**
   * No external CDN or Google Fonts domain leaks into the preload list.
   */
  public function testNoExternalDomainIsReturned(): void {
    $helper = new FontPreloadHelper();
    foreach ($helper->getPreloadLinks() as $link) {
      self::assertStringNotContainsString('googleapis', $link['href']);
      self::assertStringNotContainsString('gstatic', $link['href']);
      self::assertStringNotContainsString('jsdelivr', $link['href']);
    }
  }

}
