<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders dark-mode-toggle and asserts accessible button + icon markup.
 *
 * @group lcdle_theme_helpers
 */
final class DarkModeToggleComponentRenderTest extends ComponentKernelTestBase {

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
   * The toggle is a real <button> with an aria-label and aria-pressed.
   */
  public function testRendersAccessibleButton(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:dark-mode-toggle',
    ]);

    $button = $crawler->filter('button.dark-mode-toggle');
    self::assertCount(1, $button);
    self::assertNotEmpty($button->attr('aria-label'));
    self::assertSame('false', $button->attr('aria-pressed'));
    self::assertSame('button', $button->attr('type'));
  }

  /**
   * Both sun and moon icons ship in the DOM; CSS swaps them by mode.
   */
  public function testContainsBothSvgIcons(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:dark-mode-toggle',
    ]);

    self::assertCount(2, $crawler->filter('button.dark-mode-toggle svg'));
  }

}
