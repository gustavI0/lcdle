<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes\Command;
use Drush\Commands\DrushCommands;

/**
 * Drush audit commands for the LCDLE WordPress → Drupal migration.
 *
 * Provides two commands:
 *  - lcdle:audit-wp   (law): Reports counts and structure from the WP source DB.
 *  - lcdle:audit-drupal (lad): Reports counts from the Drupal destination.
 *
 * Run both before and after migration to verify completeness.
 */
final class MigrateAuditCommands extends DrushCommands {

  /**
   * WordPress table prefix.
   */
  private const WP_PREFIX = '6Q3sMyXEg_';

  /**
   * Constructs a MigrateAuditCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Audits the WordPress source database and reports content counts.
   *
   * Queries the WP MySQL database (registered as the 'wp' connection) and
   * reports: user count, published post/page/attachment counts, root categories
   * (themes), child categories (chroniques), tags, and a shortcode inventory
   * across all published post bodies.
   *
   * @command lcdle:audit-wp
   * @aliases law
   * @usage drush lcdle:audit-wp
   *   Audit the WordPress source database.
   */
  #[Command(name: 'lcdle:audit-wp', aliases: ['law'])]
  public function auditWp(): void {
    $db = Database::getConnection('default', 'wp');
    $p = self::WP_PREFIX;

    $this->io()->title('WordPress Source Database Audit');

    // --- Users ---
    $this->io()->section('Users');
    $userCount = (int) $db->query("SELECT COUNT(*) FROM {$p}users")->fetchField();
    $this->io()->writeln("Total users: {$userCount}");

    // --- Posts ---
    $this->io()->section('Content');
    $posts = (int) $db->query(
      "SELECT COUNT(*) FROM {$p}posts WHERE post_status = 'publish' AND post_type = 'post'"
    )->fetchField();
    $pages = (int) $db->query(
      "SELECT COUNT(*) FROM {$p}posts WHERE post_status = 'publish' AND post_type = 'page'"
    )->fetchField();
    $attachments = (int) $db->query(
      "SELECT COUNT(*) FROM {$p}posts WHERE post_type = 'attachment'"
    )->fetchField();
    $this->io()->writeln("Published posts:       {$posts}");
    $this->io()->writeln("Published pages:       {$pages}");
    $this->io()->writeln("Attachments:           {$attachments}");

    // --- Root categories (→ themes) ---
    $this->io()->section('Root Categories (→ Themes)');
    $roots = $db->query(
      "SELECT t.name, t.slug, tt.count
       FROM {$p}terms t
       JOIN {$p}term_taxonomy tt ON t.term_id = tt.term_id
       WHERE tt.taxonomy = 'category' AND tt.parent = 0
       ORDER BY t.name"
    )->fetchAll();
    foreach ($roots as $row) {
      $this->io()->writeln(sprintf('  [%s] %s (%d posts)', $row->slug, $row->name, $row->count));
    }
    $this->io()->writeln(sprintf('Total root categories: %d', count($roots)));

    // --- Child categories (→ chroniques) ---
    $this->io()->section('Child Categories (→ Chroniques)');
    $children = $db->query(
      "SELECT t.name, t.slug, tt.count, tp.name AS parent_name
       FROM {$p}terms t
       JOIN {$p}term_taxonomy tt ON t.term_id = tt.term_id
       JOIN {$p}term_taxonomy ttp ON tt.parent = ttp.term_id
       JOIN {$p}terms tp ON ttp.term_id = tp.term_id
       WHERE tt.taxonomy = 'category' AND tt.parent != 0
       ORDER BY tp.name, t.name"
    )->fetchAll();
    $currentParent = NULL;
    foreach ($children as $row) {
      if ($row->parent_name !== $currentParent) {
        $this->io()->writeln("  {$row->parent_name}:");
        $currentParent = $row->parent_name;
      }
      $this->io()->writeln(sprintf('    [%s] %s (%d posts)', $row->slug, $row->name, $row->count));
    }
    $this->io()->writeln(sprintf('Total child categories: %d', count($children)));

    // --- Tags ---
    $this->io()->section('Tags');
    $tagCount = (int) $db->query(
      "SELECT COUNT(*) FROM {$p}term_taxonomy WHERE taxonomy = 'post_tag'"
    )->fetchField();
    $this->io()->writeln("Total tags: {$tagCount}");

    // --- Shortcode inventory ---
    $this->io()->section('Shortcode Inventory');
    $bodies = $db->query(
      "SELECT post_content FROM {$p}posts WHERE post_status = 'publish' AND post_type = 'post'"
    )->fetchCol();

    $shortcodes = [];
    foreach ($bodies as $body) {
      if (preg_match_all('/\[([a-z0-9_-]+)[\s\]]/', (string) $body, $matches)) {
        foreach ($matches[1] as $name) {
          $shortcodes[$name] = ($shortcodes[$name] ?? 0) + 1;
        }
      }
    }
    if (empty($shortcodes)) {
      $this->io()->writeln('No shortcodes found.');
    }
    else {
      arsort($shortcodes);
      foreach ($shortcodes as $name => $count) {
        $this->io()->writeln(sprintf('  %-40s %d', $name, $count));
      }
      $this->io()->writeln(sprintf('Distinct shortcodes: %d', count($shortcodes)));
    }

    $this->io()->success('WordPress audit complete.');
  }

  /**
   * Audits the Drupal destination and reports migrated content counts.
   *
   * Queries Drupal entities to count users, contributor profiles, articles,
   * media items, taxonomy terms (themes, chroniques, tags_legacy), and
   * redirects. Run this after migration to compare against the WP baseline.
   *
   * @command lcdle:audit-drupal
   * @aliases lad
   * @usage drush lcdle:audit-drupal
   *   Audit the Drupal destination after migration.
   */
  #[Command(name: 'lcdle:audit-drupal', aliases: ['lad'])]
  public function auditDrupal(): void {
    $this->io()->title('Drupal Destination Audit');

    // --- Users ---
    $this->io()->section('Users');
    $userCount = (int) $this->entityTypeManager
      ->getStorage('user')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->io()->writeln("Total users: {$userCount}");

    // --- Contributor profiles ---
    $this->io()->section('Profiles');
    $profileCount = (int) $this->entityTypeManager
      ->getStorage('profile')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'contributor_profile')
      ->count()
      ->execute();
    $this->io()->writeln("Contributor profiles: {$profileCount}");

    // --- Articles ---
    $this->io()->section('Content');
    $articleCount = (int) $this->entityTypeManager
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'article')
      ->count()
      ->execute();
    $this->io()->writeln("Articles: {$articleCount}");

    // --- Media ---
    $this->io()->section('Media');
    $mediaCount = (int) $this->entityTypeManager
      ->getStorage('media')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->count()
      ->execute();
    $this->io()->writeln("Media (image): {$mediaCount}");

    // --- Taxonomy terms ---
    $this->io()->section('Taxonomy');
    foreach (['themes' => 'Themes', 'chroniques' => 'Chroniques', 'tags_legacy' => 'Tags legacy'] as $vid => $label) {
      $termCount = (int) $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', $vid)
        ->count()
        ->execute();
      $this->io()->writeln("{$label}: {$termCount}");
    }

    // --- Redirects ---
    $this->io()->section('Redirects');
    $redirectCount = (int) $this->entityTypeManager
      ->getStorage('redirect')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $this->io()->writeln("Redirects: {$redirectCount}");

    $this->io()->success('Drupal audit complete.');
  }

}
