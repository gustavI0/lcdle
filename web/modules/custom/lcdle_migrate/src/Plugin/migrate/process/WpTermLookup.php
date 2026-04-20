<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Converts a comma-separated list of WP term IDs to Drupal entity references.
 *
 * Accepts a comma-separated string of source term IDs, looks each one up
 * via the specified migration, and returns an array of target_id arrays
 * suitable for a multi-value entity reference field.
 *
 * Usage:
 * @code
 * field_themes:
 *   plugin: wp_term_lookup
 *   source: category_ids
 *   migration: wp_terms_themes
 * @endcode
 */
#[MigrateProcess(
  id: 'wp_term_lookup',
)]
class WpTermLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migrate lookup service.
   */
  protected MigrateLookupInterface $migrateLookup;

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrateLookupInterface $migrate_lookup,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migrateLookup = $migrate_lookup;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('migrate.lookup'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): array {
    if (empty($value)) {
      return [];
    }

    $migration = $this->configuration['migration'] ?? NULL;
    if (!$migration) {
      return [];
    }

    $ids = array_filter(array_map('trim', explode(',', (string) $value)));
    $result = [];

    foreach ($ids as $source_id) {
      $destination_ids = $this->migrateLookup->lookup([$migration], [(int) $source_id]);
      if (!empty($destination_ids)) {
        $target_id = reset($destination_ids[0]);
        if ($target_id) {
          $result[] = ['target_id' => $target_id];
        }
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   *
   * This plugin returns multiple values (an array of reference arrays).
   */
  public function multiple(): bool {
    return TRUE;
  }

}
