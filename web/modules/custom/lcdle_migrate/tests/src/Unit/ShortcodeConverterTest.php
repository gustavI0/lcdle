<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_migrate\Unit;

use Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the ShortcodeConverter migrate process plugin.
 *
 * Tests call ShortcodeConverter::convertShortcodes() directly so no Drupal
 * bootstrap is required. Each test method covers one shortcode family.
 *
 * @group lcdle_migrate
 * @coversDefaultClass \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter
 */
class ShortcodeConverterTest extends TestCase {

  /**
   * Tests that [caption] shortcodes are converted to <figure> elements.
   *
   * @covers ::convertShortcodes
   */
  public function testCaptionToFigure(): void {
    $input = '[caption id="attachment_42" align="aligncenter" width="600"]<img src="photo.jpg" alt="Photo"> Légende de la photo[/caption]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertStringContainsString('<figure>', $output);
    $this->assertStringContainsString('<img src="photo.jpg" alt="Photo">', $output);
    $this->assertStringContainsString('<figcaption>Légende de la photo</figcaption>', $output);
    $this->assertStringContainsString('</figure>', $output);
    $this->assertStringNotContainsString('[caption', $output);
    $this->assertStringNotContainsString('[/caption]', $output);
  }

  /**
   * Tests that [embed] shortcodes are stripped, leaving only the URL.
   *
   * @covers ::convertShortcodes
   */
  public function testEmbedStripped(): void {
    $input = '[embed]https://www.youtube.com/watch?v=abc123[/embed]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('https://www.youtube.com/watch?v=abc123', $output);
  }

  /**
   * Tests that [gallery] shortcodes become a placeholder div with a comment.
   *
   * @covers ::convertShortcodes
   */
  public function testGalleryPlaceholder(): void {
    $input = '[gallery ids="1,2,3"]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertStringContainsString('<div class="wp-gallery">', $output);
    $this->assertStringContainsString('<!-- gallery ids=1,2,3 -->', $output);
    $this->assertStringContainsString('</div>', $output);
    $this->assertStringNotContainsString('[gallery', $output);
  }

  /**
   * Tests that [youtube] shortcodes are stripped, leaving only the URL.
   *
   * @covers ::convertShortcodes
   */
  public function testYoutubeStripped(): void {
    $input = '[youtube]https://www.youtube.com/watch?v=xyz789[/youtube]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('https://www.youtube.com/watch?v=xyz789', $output);
  }

  /**
   * Tests that [youtube URL] inline form is also stripped.
   *
   * @covers ::convertShortcodes
   */
  public function testYoutubeInlineStripped(): void {
    $input = '[youtube https://www.youtube.com/watch?v=xyz789]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('https://www.youtube.com/watch?v=xyz789', $output);
  }

  /**
   * Tests that [dropcap] shortcodes become a <span class="dropcap"> element.
   *
   * @covers ::convertShortcodes
   */
  public function testDropcapConverted(): void {
    $input = '[dropcap]L[/dropcap]e texte commence ici.';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertStringContainsString('<span class="dropcap">L</span>', $output);
    $this->assertStringContainsString('e texte commence ici.', $output);
    $this->assertStringNotContainsString('[dropcap]', $output);
    $this->assertStringNotContainsString('[/dropcap]', $output);
  }

  /**
   * Tests that [soundcloud] shortcodes are stripped, leaving only the URL.
   *
   * @covers ::convertShortcodes
   */
  public function testSoundcloudStripped(): void {
    $input = '[soundcloud]https://soundcloud.com/artist/track[/soundcloud]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('https://soundcloud.com/artist/track', $output);
  }

  /**
   * Tests that [soundcloud URL] inline form is also stripped.
   *
   * @covers ::convertShortcodes
   */
  public function testSoundcloudInlineStripped(): void {
    $input = '[soundcloud https://soundcloud.com/artist/track]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('https://soundcloud.com/artist/track', $output);
  }

  /**
   * Tests that [8tracks] shortcodes are replaced by a removal comment.
   *
   * @covers ::convertShortcodes
   */
  public function test8tracksRemoved(): void {
    $input = '[8tracks width="300" height="250" id="12345" playlist="abc"]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('<!-- 8tracks embed removed (service defunct) -->', $output);
  }

  /**
   * Tests that [contact-form-7] shortcodes are replaced by a removal comment.
   *
   * @covers ::convertShortcodes
   */
  public function testContactFormRemoved(): void {
    $input = '[contact-form-7 id="123" title="Contact"]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame('<!-- contact-form removed -->', $output);
  }

  /**
   * Tests that unknown shortcodes are left intact without modification.
   *
   * @covers ::convertShortcodes
   */
  public function testUnknownShortcodeLeftIntact(): void {
    $input = '[unknown foo="bar"]some content[/unknown]';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame($input, $output);
  }

  /**
   * Tests that plain text with no shortcodes passes through unchanged.
   *
   * @covers ::convertShortcodes
   */
  public function testPlainTextUnchanged(): void {
    $input = 'Ceci est un texte simple sans shortcodes. Il contient des <strong>balises HTML</strong> normales.';
    $output = ShortcodeConverter::convertShortcodes($input);

    $this->assertSame($input, $output);
  }

}
