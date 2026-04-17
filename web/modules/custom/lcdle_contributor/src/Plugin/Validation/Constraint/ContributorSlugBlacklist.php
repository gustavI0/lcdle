<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Plugin\Validation\Constraint;

use Drupal\Core\Validation\Attribute\Constraint;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Rejects reserved URL slugs that would clash with framework or system routes.
 */
#[Constraint(
  id: 'ContributorSlugBlacklist',
  label: new TranslatableMarkup('Contributor slug blacklist'),
  type: 'string',
)]
final class ContributorSlugBlacklist extends SymfonyConstraint {

  /**
   * Violation message when a reserved slug is used.
   *
   * @var string
   */
  public string $message = "Le slug « @slug » est réservé et ne peut pas être utilisé.";

  /**
   * List of reserved URL slugs that cannot be used as contributor identifiers.
   *
   * @var list<string>
   */
  public const RESERVED = [
    'admin',
    'user',
    'users',
    'node',
    'taxonomy',
    'api',
    'jsonapi',
    'theme',
    'chronique',
    'newsletter',
    'contribute',
    'about',
    'rss',
    'feed',
    'sitemap',
    'robots',
    'login',
    'search',
    'media',
    'files',
    'system',
    'contact',
  ];

}
