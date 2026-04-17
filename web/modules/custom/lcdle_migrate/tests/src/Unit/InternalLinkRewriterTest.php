<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_migrate\Unit;

use Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the InternalLinkRewriter migrate process plugin.
 *
 * Tests call InternalLinkRewriter::rewriteLinks() directly so no Drupal
 * bootstrap is required. Each test method covers one rewriting scenario.
 *
 * @group lcdle_migrate
 * @coversDefaultClass \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter
 */
class InternalLinkRewriterTest extends TestCase {

  /**
   * Tests that absolute https:// links are converted to relative paths.
   *
   * @covers ::rewriteLinks
   */
  public function testAbsoluteToRelative(): void {
    $input = '<a href="https://laculturedelecran.com/mon-article/">Mon article</a>';
    $output = InternalLinkRewriter::rewriteLinks($input, 'laculturedelecran.com');

    $this->assertStringContainsString('href="/mon-article/"', $output);
    $this->assertStringNotContainsString('https://laculturedelecran.com', $output);
  }

  /**
   * Tests that http:// variant is also converted to a relative path.
   *
   * @covers ::rewriteLinks
   */
  public function testHttpAlsoConverted(): void {
    $input = '<a href="http://laculturedelecran.com/mon-article/">Mon article</a>';
    $output = InternalLinkRewriter::rewriteLinks($input, 'laculturedelecran.com');

    $this->assertStringContainsString('href="/mon-article/"', $output);
    $this->assertStringNotContainsString('http://laculturedelecran.com', $output);
  }

  /**
   * Tests that external links to other domains are left untouched.
   *
   * @covers ::rewriteLinks
   */
  public function testExternalLinksUntouched(): void {
    $input = '<a href="https://example.com/page/">Externe</a>';
    $output = InternalLinkRewriter::rewriteLinks($input, 'laculturedelecran.com');

    $this->assertSame($input, $output);
  }

  /**
   * Tests that image src attributes pointing to the WP domain are rewritten.
   *
   * @covers ::rewriteLinks
   */
  public function testImageSrcRewritten(): void {
    $input = '<img src="https://laculturedelecran.com/wp-content/uploads/2023/photo.jpg" alt="Photo">';
    $output = InternalLinkRewriter::rewriteLinks($input, 'laculturedelecran.com');

    $this->assertStringContainsString('src="/wp-content/uploads/2023/photo.jpg"', $output);
    $this->assertStringNotContainsString('https://laculturedelecran.com', $output);
  }

  /**
   * Tests that bare-text URLs with the internal domain are also rewritten.
   *
   * The simple regex replacement is by design: even bare-text URLs become
   * relative paths, which is acceptable for the migration use case.
   *
   * @covers ::rewriteLinks
   */
  public function testPlainTextUrlRewritten(): void {
    $input = 'Before https://laculturedelecran.com/page After';
    $output = InternalLinkRewriter::rewriteLinks($input, 'laculturedelecran.com');

    $this->assertSame('Before /page After', $output);
  }

}
