<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts WordPress shortcodes found in post_content to HTML or removes them.
 *
 * Handles the 8 shortcode families discovered during the WP content audit
 * (Task 3). Unknown shortcodes are left intact so no content is silently lost.
 *
 * Conversion strategies:
 *   - [caption]  → <figure><img><figcaption>text</figcaption></figure>
 *   - [embed]    → unwrapped URL (strips wrapper tags)
 *   - [gallery]  → <div class="wp-gallery"><!-- gallery ids=… --></div>
 *   - [youtube]  → unwrapped URL (both inline and block forms)
 *   - [dropcap]  → <span class="dropcap">X</span>
 *   - [soundcloud] → unwrapped URL (both inline and block forms)
 *   - [8tracks]  → HTML comment (service defunct)
 *   - [contact-form-7] → HTML comment
 *   - unknown    → left intact
 *
 * Usage in a migration YAML:
 * @code
 * process:
 *   body/value:
 *     plugin: shortcode_converter
 *     source: post_content
 * @endcode
 *
 * @see \Drupal\migrate\ProcessPluginBase
 */
#[MigrateProcess(
  id: 'shortcode_converter',
)]
class ShortcodeConverter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Passes the source value through convertShortcodes() and returns the result.
   *
   * @param mixed $value
   *   The source value (expected to be a string containing WP post_content).
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration executable.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param string $destination_property
   *   The destination property name.
   *
   * @return string
   *   The converted text.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    if (!is_string($value)) {
      return (string) $value;
    }
    return static::convertShortcodes($value);
  }

  /**
   * Converts all known WordPress shortcodes in the given text.
   *
   * This method is intentionally public and static so that it can be called
   * in unit tests without a Drupal bootstrap. Each shortcode family is handled
   * by its own preg_replace or preg_replace_callback invocation, in order of
   * specificity (most specific first to avoid accidental partial matches).
   *
   * @param string $text
   *   The raw WordPress post_content string.
   *
   * @return string
   *   The text with all known shortcodes converted and unknown ones preserved.
   */
  public static function convertShortcodes(string $text): string {
    $text = static::convertCaption($text);
    $text = static::convertEmbed($text);
    $text = static::convertGallery($text);
    $text = static::convertYoutube($text);
    $text = static::convertDropcap($text);
    $text = static::convertSoundcloud($text);
    $text = static::convert8tracks($text);
    $text = static::convertContactForm($text);
    return $text;
  }

  /**
   * Converts [caption ...] ... [/caption] to <figure> markup.
   *
   * The shortcode wraps an <img> tag followed by optional caption text.
   * The img tag and any trailing text are preserved; the shortcode wrapper is
   * replaced by <figure>…<figcaption>text</figcaption></figure>.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with caption shortcodes converted.
   */
  protected static function convertCaption(string $text): string {
    return preg_replace_callback(
      '/\[caption[^\]]*\](.*?)\[\/caption\]/s',
      static function (array $matches): string {
        $inner = $matches[1];
        // Separate the <img> tag from the caption text that follows it.
        if (preg_match('/^(\s*<img[^>]*>)(.*)/s', $inner, $parts)) {
          $img = trim($parts[1]);
          $caption_text = trim($parts[2]);
          if ($caption_text !== '') {
            return '<figure>' . $img . '<figcaption>' . $caption_text . '</figcaption></figure>';
          }
          return '<figure>' . $img . '</figure>';
        }
        // Fallback: no img found, wrap content as-is.
        return '<figure>' . trim($inner) . '</figure>';
      },
      $text
    );
  }

  /**
   * Converts [embed]URL[/embed] by stripping the wrapper and keeping the URL.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with embed shortcodes stripped.
   */
  protected static function convertEmbed(string $text): string {
    return preg_replace('/\[embed\](.*?)\[\/embed\]/s', '$1', $text);
  }

  /**
   * Converts [gallery ids="1,2,3"] to a placeholder div.
   *
   * The full gallery rendering is deferred to Phase 2 theming. A structured
   * HTML comment inside the div preserves the original IDs for later use.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with gallery shortcodes replaced by placeholder divs.
   */
  protected static function convertGallery(string $text): string {
    return preg_replace_callback(
      '/\[gallery([^\]]*)\]/i',
      static function (array $matches): string {
        $attrs = $matches[1];
        // Extract ids attribute value if present.
        $ids = '';
        if (preg_match('/ids=["\']([^"\']+)["\']/', $attrs, $id_match)) {
          $ids = $id_match[1];
        }
        $comment = $ids !== '' ? '<!-- gallery ids=' . $ids . ' -->' : '<!-- gallery -->';
        return '<div class="wp-gallery">' . $comment . '</div>';
      },
      $text
    );
  }

  /**
   * Converts [youtube] shortcodes by stripping the wrapper and keeping the URL.
   *
   * Handles two forms:
   *   - Block: [youtube]URL[/youtube]
   *   - Inline: [youtube URL]
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with youtube shortcodes stripped.
   */
  protected static function convertYoutube(string $text): string {
    // Block form: [youtube]URL[/youtube]
    $text = preg_replace('/\[youtube\](.*?)\[\/youtube\]/s', '$1', $text);
    // Inline form: [youtube URL]
    $text = preg_replace('/\[youtube\s+(https?[^\]]+)\]/i', '$1', $text);
    return $text;
  }

  /**
   * Converts [dropcap]X[/dropcap] to <span class="dropcap">X</span>.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with dropcap shortcodes converted.
   */
  protected static function convertDropcap(string $text): string {
    return preg_replace('/\[dropcap\](.*?)\[\/dropcap\]/s', '<span class="dropcap">$1</span>', $text);
  }

  /**
   * Converts [soundcloud] shortcodes by stripping the wrapper and keeping URL.
   *
   * Handles two forms:
   *   - Block: [soundcloud]URL[/soundcloud]
   *   - Inline: [soundcloud URL]
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with soundcloud shortcodes stripped.
   */
  protected static function convertSoundcloud(string $text): string {
    // Block form: [soundcloud]URL[/soundcloud]
    $text = preg_replace('/\[soundcloud\](.*?)\[\/soundcloud\]/s', '$1', $text);
    // Inline form: [soundcloud URL]
    $text = preg_replace('/\[soundcloud\s+(https?[^\]]+)\]/i', '$1', $text);
    return $text;
  }

  /**
   * Replaces [8tracks ...] shortcodes with an HTML removal comment.
   *
   * 8tracks shut down in 2019. All embeds are replaced with a comment that
   * signals the removed content without breaking surrounding markup.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with 8tracks shortcodes removed.
   */
  protected static function convert8tracks(string $text): string {
    return preg_replace('/\[8tracks[^\]]*\]/i', '<!-- 8tracks embed removed (service defunct) -->', $text);
  }

  /**
   * Replaces [contact-form-7 ...] shortcodes with an HTML removal comment.
   *
   * Contact Form 7 forms are not migrated; a comment is left as a marker.
   *
   * @param string $text
   *   Input text.
   *
   * @return string
   *   Text with contact-form-7 shortcodes removed.
   */
  protected static function convertContactForm(string $text): string {
    return preg_replace('/\[contact-form-7[^\]]*\]/i', '<!-- contact-form removed -->', $text);
  }

}
