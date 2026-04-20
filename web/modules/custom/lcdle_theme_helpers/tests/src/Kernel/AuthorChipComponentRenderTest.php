<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_theme_helpers\Kernel;

use Drupal\KernelTests\Components\ComponentKernelTestBase;

/**
 * Renders the author-chip SDC and asserts structural invariants.
 *
 * @group lcdle_theme_helpers
 */
final class AuthorChipComponentRenderTest extends ComponentKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'serialization',
    'image',
    'responsive_image',
    'breakpoint',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $themes = ['lcdle'];

  /**
   * The chip renders its name and URL.
   */
  public function testRendersNameAndUrl(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Léa Martineau',
        'url' => '/leamartineau',
        'avatar_initials' => 'LM',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.author-chip'));
    self::assertSame('Léa Martineau', trim($crawler->filter('.author-chip__name')->text()));
    self::assertSame('/leamartineau', $crawler->filter('a.author-chip')->attr('href'));
  }

  /**
   * Avatar initials render when avatar_src is absent.
   */
  public function testAvatarFallbackRendersInitials(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Hugo B.',
        'url' => '/hugob',
        'avatar_initials' => 'HB',
      ],
    ]);

    self::assertSame('HB', trim($crawler->filter('.author-chip__avatar')->text()));
  }

  /**
   * The size prop applies the matching BEM modifier class.
   */
  public function testSizeModifierApplied(): void {
    $crawler = $this->renderComponentRenderArray([
      '#type' => 'component',
      '#component' => 'lcdle:author-chip',
      '#props' => [
        'name' => 'Test',
        'url' => '/test',
        'avatar_initials' => 'T',
        'size' => 'lg',
      ],
    ]);

    self::assertCount(1, $crawler->filter('.author-chip--lg'));
  }

}
