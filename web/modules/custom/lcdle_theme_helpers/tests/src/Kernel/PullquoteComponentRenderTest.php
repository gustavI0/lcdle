<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\Core\Render\Markup;
use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the pullquote SDC and asserts structural invariants.
 *
 * @group lcdle_theme_helpers
 */
final class PullquoteComponentRenderTest extends ComponentKernelTestBase {

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
   * Renders quote content and the cite attribution.
   */
  public function testRendersQuoteAndCite(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:pullquote',
      '#slots' => [
        // Wrap in Markup::create so Drupal does not escape the HTML;
        // the scalar path would run #plain_text and render &lt;p&gt;.
        'quote' => [
          '#markup' => Markup::create('<p>Je travaille avec peu d\'éléments.</p>'),
        ],
        'cite' => '— Arvo Pärt, 1978',
      ],
    ]);

    self::assertCount(1, $crawler->filter('blockquote.pullquote'));
    self::assertStringContainsString("Je travaille avec peu d'éléments.", $crawler->filter('.pullquote__quote')->html());
    self::assertStringContainsString('Arvo Pärt', trim($crawler->filter('.pullquote__cite')->text()));
  }

  /**
   * The cite element is omitted when the cite slot is empty.
   */
  public function testRendersWithoutCite(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:pullquote',
      '#slots' => [
        'quote' => [
          '#markup' => Markup::create('<p>Anonymous quote.</p>'),
        ],
      ],
    ]);

    self::assertCount(1, $crawler->filter('.pullquote__quote'));
    self::assertCount(0, $crawler->filter('.pullquote__cite'));
  }

}
