<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Functional;

use Drupal\Tests\BrowserTestBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Verifies that the lcdle theme loads its foundation CSS on anonymous pages.
 *
 * @group lcdle_theme_helpers
 */
final class TokensLoadedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'lcdle';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['image', 'breakpoint', 'responsive_image', 'lcdle_theme_helpers'];

  /**
   * {@inheritdoc}
   *
   * The lcdle theme ships config that depends on image + responsive_image.
   * BrowserTestBase installs the default theme BEFORE modules from
   * ::$modules, so we install those module dependencies first to satisfy
   * the theme's config/install/ validation.
   */
  protected function installDefaultThemeFromClassProperty(ContainerInterface $container): void {
    $container->get('module_installer')->install([
      'image',
      'breakpoint',
      'responsive_image',
    ]);
    // Rebuild the container because module_installer swaps it out.
    $this->container = $container = \Drupal::getContainer();
    parent::installDefaultThemeFromClassProperty($container);
  }

  public function testTokensCssIsLinkedOnTheFrontPage(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->statusCodeEquals(200);

    $html = $this->getSession()->getPage()->getHtml();

    // The five foundation CSS files must all be linked.
    self::assertStringContainsString(
      '/themes/custom/lcdle/css/layers.css',
      $html,
      'layers.css must be linked in the rendered HTML.'
    );
    self::assertStringContainsString(
      '/themes/custom/lcdle/css/base/tokens.css',
      $html,
      'tokens.css (light mode) must be linked.'
    );
    self::assertStringContainsString(
      '/themes/custom/lcdle/css/base/tokens-dark.css',
      $html,
      'tokens-dark.css must be linked so dark mode works.'
    );
    self::assertStringContainsString(
      '/themes/custom/lcdle/css/base/fonts.css',
      $html,
      'fonts.css must be linked so @font-face declarations apply.'
    );
    self::assertStringContainsString(
      '/themes/custom/lcdle/css/base/base.css',
      $html,
      'base.css must be linked so element defaults apply.'
    );
  }

  public function testPreloadLinksPresentForCriticalFonts(): void {
    $this->drupalGet('<front>');
    $html = $this->getSession()->getPage()->getHtml();

    self::assertMatchesRegularExpression(
      '#<link[^>]+rel="preload"[^>]+href="[^"]*PlayfairDisplay-Regular\.woff2"#',
      $html,
      'Playfair Display Regular must be preloaded.'
    );
    self::assertMatchesRegularExpression(
      '#<link[^>]+rel="preload"[^>]+href="[^"]*Inter-Regular\.woff2"#',
      $html,
      'Inter Regular must be preloaded.'
    );
  }

  public function testNoExternalFontHostIsReferenced(): void {
    $this->drupalGet('<front>');
    $html = $this->getSession()->getPage()->getHtml();

    self::assertStringNotContainsString('fonts.googleapis.com', $html);
    self::assertStringNotContainsString('fonts.gstatic.com', $html);
  }

}
