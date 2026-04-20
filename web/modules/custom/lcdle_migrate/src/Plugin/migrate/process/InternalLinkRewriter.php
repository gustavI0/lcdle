<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Rewrites absolute internal WP URLs in article bodies to relative paths.
 *
 * Converts URLs of the form https://laculturedelecran.com/some-path/ to the
 * relative form /some-path/ so that links and image src attributes continue to
 * work after the DNS switchover to the new Drupal site. The resulting relative
 * paths are resolved by Drupal's redirect module after migration.
 *
 * Both https:// and http:// variants are handled. External links to other
 * domains are left untouched. The replacement is a simple string operation
 * (no DOM parsing) which is appropriate for this content volume (YAGNI).
 *
 * Usage in a migration YAML:
 * @code
 * process:
 *   body/value:
 *     plugin: internal_link_rewriter
 *     source: post_content
 *     domain: laculturedelecran.com
 * @endcode
 *
 * @see \Drupal\migrate\ProcessPluginBase
 */
#[MigrateProcess(
  id: 'internal_link_rewriter',
)]
class InternalLinkRewriter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   *
   * Passes the source value through rewriteLinks() and returns the result.
   *
   * @param mixed $value
   *   The source value (expected to be a string containing HTML body content).
   * @param \Drupal\migrate\MigrateExecutableInterface $migrate_executable
   *   The migration executable.
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param string $destination_property
   *   The destination property name.
   *
   * @return string
   *   The text with internal absolute URLs replaced by relative paths.
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    if (!is_string($value)) {
      return (string) $value;
    }
    $domain = $this->configuration['domain'] ?? 'laculturedelecran.com';
    return static::rewriteLinks($value, $domain);
  }

  /**
   * Rewrites all absolute internal URLs for the given domain to relative paths.
   *
   * This method is intentionally public and static so that it can be called
   * in unit tests without a Drupal bootstrap. The replacement uses a regex
   * that matches both http:// and https:// variants and strips only the scheme
   * and domain, leaving the path (and any query string or fragment) intact.
   *
   * Any URL not starting with the internal domain is left unchanged, which
   * ensures external links are never modified.
   *
   * @param string $text
   *   The raw HTML string to process.
   * @param string $domain
   *   The WordPress domain to rewrite, e.g. 'laculturedelecran.com'.
   *
   * @return string
   *   The text with all absolute internal URLs converted to relative paths.
   */
  public static function rewriteLinks(string $text, string $domain): string {
    $escaped_domain = preg_quote($domain, '/');
    return preg_replace('/https?:\/\/' . $escaped_domain . '/', '', $text);
  }

}
