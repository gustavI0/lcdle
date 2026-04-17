<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\ParamConverter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Resolves a {contributor_slug} route parameter to a loaded ProfileContributor.
 */
final class ContributorSlugConverter implements ParamConverterInterface {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   *
   * @param mixed $value
   *   The raw value from the URL.
   * @param mixed $definition
   *   The parameter definition from the route.
   * @param string $name
   *   The name of the parameter.
   * @param array<string, mixed> $defaults
   *   The route defaults.
   */
  public function convert($value, $definition, $name, array $defaults): mixed {
    if (!is_string($value) || $value === '') {
      return NULL;
    }

    $storage = $this->entityTypeManager->getStorage('profile');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'contributor_profile')
      ->condition('field_slug', $value)
      ->condition('status', 1)
      ->range(0, 1)
      ->execute();

    if (empty($ids)) {
      return NULL;
    }

    return $storage->load(reset($ids));
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route): bool {
    return ($definition['type'] ?? NULL) === 'lcdle_contributor_slug';
  }

}
