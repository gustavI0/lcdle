<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\Core\Render\Markup;
use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the header SDC: type-set logo, nav slot, embedded dark-mode-toggle.
 *
 * @group lcdle_theme_helpers
 */
final class HeaderComponentRenderTest extends ComponentKernelTestBase {

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
   * Site name + tagline render from props.
   */
  public function testRendersSiteNameAndTagline(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => [
        'site_name' => "La Culture de l'Écran",
        'site_sub' => 'Musique · Cinéma · Art',
      ],
    ]);

    self::assertCount(1, $crawler->filter('header.header'));
    self::assertStringContainsString("La Culture de l'Écran", $crawler->filter('.header__site-name')->text());
    self::assertStringContainsString('Musique', $crawler->filter('.header__site-sub')->text());
  }

  /**
   * The dark-mode-toggle SDC is always embedded inside the header.
   */
  public function testEmbedsDarkModeToggle(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => ['site_name' => 'X', 'site_sub' => 'Y'],
    ]);

    self::assertCount(1, $crawler->filter('header.header button.dark-mode-toggle'));
  }

  /**
   * Markup passed into the nav slot renders inside the header nav.
   */
  public function testNavSlotRendered(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:header',
      '#props' => ['site_name' => 'X', 'site_sub' => 'Y'],
      '#slots' => [
        'nav' => [
          '#markup' => Markup::create('<a href="/articles">Articles</a><a href="/contribs">Contributeurs</a>'),
        ],
      ],
    ]);

    self::assertCount(2, $crawler->filter('.header__nav a'));
  }

}
