<?php

declare(strict_types=1);

namespace Drupal\lcdle_theme_helpers;

/**
 * Returns the <link rel="preload"> descriptors for the critical fonts.
 *
 * Two weights are preloaded: Playfair Display Regular (400) used for
 * headings + site name, and Inter Regular (400) used for body text.
 * Other weights (italic, medium, black italic, light) load lazily via
 * their @font-face declarations when a glyph actually needs them.
 *
 * Kept as a stateless service so it can be instantiated directly in
 * unit tests without DI container bootstrapping.
 */
final class FontPreloadHelper {

  private const THEME_FONTS_BASE = '/themes/custom/lcdle/fonts';

  private const CRITICAL_WEIGHTS = [
    'PlayfairDisplay-Regular.woff2',
    'Inter-Regular.woff2',
  ];

  /**
   * Returns the preload link descriptors for the critical fonts.
   *
   * @return list<array{rel: string, as: string, type: string, crossorigin: string, href: string}>
   *   One entry per critical font weight, ready to be appended to
   *   #attached[html_head_link].
   */
  public function getPreloadLinks(): array {
    $links = [];
    foreach (self::CRITICAL_WEIGHTS as $filename) {
      $links[] = [
        'rel' => 'preload',
        'as' => 'font',
        'type' => 'font/woff2',
        'crossorigin' => 'anonymous',
        'href' => self::THEME_FONTS_BASE . '/' . $filename,
      ];
    }
    return $links;
  }

}
