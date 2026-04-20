<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\Core\Render\Markup;
use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the footer SDC: site name + links slot.
 *
 * @group lcdle_theme_helpers
 */
final class FooterComponentRenderTest extends ComponentKernelTestBase {

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
   * Site name prop and links slot both render into their BEM slots.
   */
  public function testRendersSiteNameAndLinksSlot(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:footer',
      '#props' => ['site_name' => "La Culture de l'Écran"],
      '#slots' => [
        'links' => [
          '#markup' => Markup::create('<a href="/a-propos">À propos</a><a href="/rss">RSS</a>'),
        ],
      ],
    ]);

    self::assertCount(1, $crawler->filter('footer.footer'));
    self::assertStringContainsString("La Culture de l'Écran", $crawler->filter('.footer__site-name')->text());
    self::assertCount(2, $crawler->filter('.footer__links a'));
  }

}
