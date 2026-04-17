<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\pathauto\Entity\PathautoPattern;

/**
 * @group lcdle_contributor
 */
final class PathautoPatternUpdatedTest extends LcdleContributorKernelTestBase {

  protected function setUp(): void {
    parent::setUp();
    // hook_install() is not invoked by installConfig(). Call it explicitly so
    // the kernel test environment mirrors a real module installation where
    // the install hook rewrites the placeholder pathauto pattern.
    \Drupal::moduleHandler()->loadInclude('lcdle_contributor', 'install');
    lcdle_contributor_install();
  }

  public function testArticlePatternUsesAuthorSlug(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertNotNull($pattern);
    // Token key is the profile type id: contributor_profile.
    // The profile module registers [user:contributor_profile] via
    // hook_token_info_alter() using the profile type's id as the token key.
    $this->assertStringContainsString(
      '[node:author:',
      $pattern->getPattern(),
      'article pathauto pattern references the author.',
    );
    $this->assertStringContainsString(
      'slug',
      $pattern->getPattern(),
      'article pathauto pattern references the slug field.',
    );
  }

}
