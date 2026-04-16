<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\PathProcessor;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Rewrites /{slug} → /contributor/{slug} inbound so Drupal's 2-segment
 * route matching works. Reverses on outbound so users see /{slug} in URLs.
 *
 * Drupal's RouteProvider::getCandidateOutlines() only generates the static
 * candidate for single-segment paths (hardcoded $masks = [1]), so a
 * wildcard route like /{contributor_slug} never matches. This processor
 * bridges the gap by adding the /contributor prefix internally.
 */
final class ContributorSlugPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  private const SLUG_PATTERN = '/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/';

  private const INTERNAL_PREFIX = '/contributor/';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public function processInbound($path, Request $request) {
    $parts = explode('/', ltrim($path, '/'));
    if (count($parts) !== 1) {
      return $path;
    }

    $slug = $parts[0];
    if (!preg_match(self::SLUG_PATTERN, $slug)) {
      return $path;
    }

    $ids = $this->entityTypeManager->getStorage('profile')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'contributor_profile')
      ->condition('field_slug', $slug)
      ->condition('status', 1)
      ->range(0, 1)
      ->execute();

    if (!empty($ids)) {
      return self::INTERNAL_PREFIX . $slug;
    }

    return $path;
  }

  public function processOutbound($path, &$options = [], ?Request $request = NULL, ?BubbleableMetadata $bubbleable_metadata = NULL) {
    if (str_starts_with($path, self::INTERNAL_PREFIX)) {
      $slug = substr($path, strlen(self::INTERNAL_PREFIX));
      if (preg_match(self::SLUG_PATTERN, $slug)) {
        return '/' . $slug;
      }
    }
    return $path;
  }

}
