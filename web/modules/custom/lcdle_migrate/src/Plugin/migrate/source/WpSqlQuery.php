<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\migrate\source\SqlBase;

/**
 * Raw SQL query source plugin for WordPress migrations.
 *
 * Executes an arbitrary SQL query against the configured database key.
 *
 * Available configuration keys:
 * - query: The raw SQL query string to execute.
 * - key: (optional) Database connection key. Defaults to 'migrate'.
 * - ids: Associative array mapping ID column names to their type definitions.
 *
 * Example:
 * @code
 * source:
 *   plugin: wp_sql_query
 *   key: wp
 *   query: |
 *     SELECT ID, user_login FROM wp_users WHERE ID > 0
 *   ids:
 *     ID:
 *       type: integer
 * @endcode
 */
#[MigrateSource('wp_sql_query')]
class WpSqlQuery extends SqlBase {

  /**
   * {@inheritdoc}
   *
   * Not used: the parent's query() returns a SelectInterface, but this plugin
   * bypasses it entirely via initializeIterator() which executes the raw SQL
   * directly. Returning NULL is safe because neither count() nor
   * initializeIterator() call parent::query().
   */
  public function query() {
    // @phpstan-ignore-next-line return.type
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, string>
   */
  public function fields(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, array<string, string>>
   */
  public function getIds(): array {
    return $this->configuration['ids'];
  }

  /**
   * {@inheritdoc}
   *
   * Override to use a simple query rather than the SqlBase SelectInterface
   * approach, since we have a raw SQL string.
   */
  protected function initializeIterator() {
    $sql = trim($this->configuration['query']);
    $result = $this->getDatabase()->query($sql);
    $rows = [];
    foreach ($result as $row) {
      $rows[] = (array) $row;
    }
    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE): int {
    $sql = trim($this->configuration['query']);
    // Wrap the query to count rows.
    $count_sql = 'SELECT COUNT(*) FROM (' . $sql . ') AS subquery';
    return (int) $this->getDatabase()->query($count_sql)->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function __toString(): string {
    return trim($this->configuration['query'] ?? '');
  }

}
