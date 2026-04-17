---
id: PLAN-1
type: implementation-plan
status: draft
last-updated: 2026-04-17
spec: SPEC-001
phase: "1 — WordPress Migration"
---

# Phase 1 — WordPress Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate all content from the WordPress site `laculturedelecran.com` (SQL dump + wp-content files) into the Drupal 11 content model built in Phase 0 — users, profiles, taxonomy terms, media, articles, and redirects — with automated verification that every old URL has a 301 redirect and every piece of content landed correctly.

**Architecture:** Custom module `lcdle_migrate` containing Drupal Migrate API configuration (YAML) and custom process plugins. A second MySQL database in DDEV hosts the WordPress dump as a read-only source. Migrations run via `drush migrate:import` in dependency order (users → terms → media → articles → redirects). Two custom process plugins handle shortcode-to-HTML conversion and internal link rewriting in article bodies. A drush command provides pre/post-migration audit reports.

**Tech Stack:** Drupal 11 Migrate API (core), `migrate_plus`, `migrate_tools`, `migrate_file` (if available, otherwise custom file copy), custom process plugins, DDEV second database (MySQL 8), drush.

---

## Scope

This plan produces a **staging-only migration** — all content migrated, verified, but NOT yet live. DNS switchover happens in Phase 2 (theming + launch).

Out of scope: theming, newsletter sending, IndieWeb/Fediverse, DNS, SSL, prod deployment.

---

## Source Data Profile

| Asset | Count | Location |
|---|---|---|
| WP SQL dump (MariaDB 11.8) | 64 MB, 143k lines | `backup-wp/lcdle_db_2026-04-11_02h15.sql` |
| WP files (uploads) | 20,194 files, 2 GB | `backup-wp/wordpress-files/wp-content/uploads/` |
| Table prefix | `6Q3sMyXEg_` | All WP tables |
| Published posts | ~1,100 | `wp_posts WHERE post_status='publish' AND post_type='post'` |
| Users (authors) | 19 | `wp_users` |
| Categories (themes + chroniques) | To audit (Task 3) | `wp_terms + wp_term_taxonomy WHERE taxonomy='category'` |
| Tags | To audit (Task 3) | `wp_terms + wp_term_taxonomy WHERE taxonomy='post_tag'` |
| Shortcodes: `[embed]` | ~2,004 | In post_content |
| Shortcodes: `[caption]` | ~311 | In post_content |
| Shortcodes: `[gallery]` | ~132 | In post_content |
| Shortcodes: `[contact-form-7]` | 6 | In post_content |

---

## File Structure

### Created

```
web/modules/custom/lcdle_migrate/
├── lcdle_migrate.info.yml
├── lcdle_migrate.services.yml
├── src/
│   ├── Plugin/
│   │   └── migrate/
│   │       └── process/
│   │           ├── ShortcodeConverter.php
│   │           └── InternalLinkRewriter.php
│   └── Commands/
│       └── MigrateAuditCommands.php
├── config/
│   └── install/
│       └── migrate_plus.migration_group.lcdle_wp.yml
├── migrations/
│   ├── wp_users.yml
│   ├── wp_contributor_profiles.yml
│   ├── wp_terms_themes.yml
│   ├── wp_terms_chroniques.yml
│   ├── wp_terms_tags_legacy.yml
│   ├── wp_media_files.yml
│   ├── wp_media_entities.yml
│   ├── wp_posts.yml
│   └── wp_redirects.yml
└── tests/
    └── src/
        └── Unit/
            ├── ShortcodeConverterTest.php
            └── InternalLinkRewriterTest.php

.ddev/
└── docker-compose.wp-db.yaml    # second MySQL service for WP data
```

### Modified

- `composer.json` — require `drupal/migrate_plus`, `drupal/migrate_tools`
- `.ddev/config.yaml` — no changes (second DB is a docker-compose override)
- `config/sync/` — re-exported after migration module install

---

## Conventions

- **Migration IDs** prefixed `wp_` (e.g., `wp_users`, `wp_posts`).
- **Migration group** `lcdle_wp` — groups all migrations for `drush migrate:import --group=lcdle_wp`.
- **Source DB key** `wp` — registered in `settings.ddev.php` or `settings.local.php` pointing to the MySQL container.
- **WP table prefix** `6Q3sMyXEg_` — hardcoded in migration source queries (it's specific to this dump).
- **TDD for process plugins** — unit tests with known input/output (no Drupal bootstrap needed).
- **Migration testing** — drush-level commands, NOT PHPUnit kernel tests (migrations need the live WP source DB which isn't available in test isolation).
- **Code English, UI French.** One commit per task.

---

## Task 1 — DDEV second database (MySQL for WP) + import dump

**Files:**
- Create: `.ddev/docker-compose.wp-db.yaml`

- [ ] **Step 1: Create the docker-compose override for a MySQL service**

`.ddev/docker-compose.wp-db.yaml`:

```yaml
services:
  wp-db:
    container_name: ddev-${DDEV_SITENAME}-wp-db
    image: mysql:8.0
    restart: "no"
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: wordpress
      MYSQL_USER: wp
      MYSQL_PASSWORD: wp
    ports:
      - "3307:3306"
    volumes:
      - wp-db-data:/var/lib/mysql
    labels:
      com.ddev.site-name: ${DDEV_SITENAME}
      com.ddev.approot: $DDEV_APPROOT

volumes:
  wp-db-data:
```

- [ ] **Step 2: Restart DDEV to pick up the new service**

Run: `ddev restart`
Expected: output includes `wp-db` container starting.

- [ ] **Step 3: Import the WP SQL dump**

Run:
```bash
ddev exec -s wp-db mysql -u wp -pwp wordpress < backup-wp/lcdle_db_2026-04-11_02h15.sql
```

If the import fails due to charset issues, try:
```bash
ddev exec -s wp-db mysql -u wp -pwp --default-character-set=utf8mb4 wordpress < backup-wp/lcdle_db_2026-04-11_02h15.sql
```

Expected: import completes without errors (warnings about deprecations are OK).

- [ ] **Step 4: Verify the import**

Run:
```bash
ddev exec -s wp-db mysql -u wp -pwp -e "SELECT COUNT(*) AS post_count FROM wordpress.6Q3sMyXEg_posts WHERE post_status='publish' AND post_type='post';"
```

Expected: prints a count matching ~1,100 published posts.

Run:
```bash
ddev exec -s wp-db mysql -u wp -pwp -e "SELECT COUNT(*) FROM wordpress.6Q3sMyXEg_users;"
```

Expected: 19 users.

- [ ] **Step 5: Configure Drupal to know about the WP database**

Add the WP database connection to Drupal's settings. Create or append to `web/sites/default/settings.local.php`:

```php
<?php

/**
 * @file
 * Local settings overrides for DDEV environment.
 */

// WordPress source database for migration.
$databases['wp']['default'] = [
  'database' => 'wordpress',
  'username' => 'wp',
  'password' => 'wp',
  'host' => 'wp-db',
  'port' => '3306',
  'driver' => 'mysql',
  'prefix' => '',
];
```

Then enable the settings.local.php include in `web/sites/default/settings.php`. Find the commented-out block near the end:

```php
# if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
#   include $app_root . '/' . $site_path . '/settings.local.php';
# }
```

Uncomment it to:

```php
if (file_exists($app_root . '/' . $site_path . '/settings.local.php')) {
  include $app_root . '/' . $site_path . '/settings.local.php';
}
```

Verify the connection:

```bash
ddev drush ev "print_r(array_keys(\Drupal\Core\Database\Database::getAllConnectionInfo()));"
```

Expected: `Array ( [0] => default [1] => wp )`.

- [ ] **Step 6: Commit**

```bash
git add .ddev/docker-compose.wp-db.yaml web/sites/default/settings.php
git -c commit.gpgsign=false commit -m "Add DDEV MySQL service for WordPress source database

A second database container (MySQL 8) hosts the WP SQL dump as a
read-only source for the Migrate API. The connection is registered as
'wp' in settings.local.php. settings.php now includes settings.local.php
when present (was commented out by default).

The SQL dump itself is NOT committed — it lives in backup-wp/ which is
gitignored (ADR-003)."
```

Note: `web/sites/default/settings.local.php` is gitignored (by design — it contains local overrides). Only `settings.php` changes are committed.

---

## Task 2 — Migrate modules + lcdle_migrate module skeleton

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create: `web/modules/custom/lcdle_migrate/lcdle_migrate.info.yml`
- Create: `web/modules/custom/lcdle_migrate/config/install/migrate_plus.migration_group.lcdle_wp.yml`

- [ ] **Step 1: Require migrate contrib modules**

Run:
```bash
ddev composer require drupal/migrate_plus:^6 drupal/migrate_tools:^6 --no-interaction -W
```

Expected: packages installed.

- [ ] **Step 2: Create module info**

`lcdle_migrate.info.yml`:

```yaml
name: 'LCDLE Migrate'
type: module
description: 'WordPress to Drupal migration for La Culture de l''Écran.'
package: 'LCDLE'
core_version_requirement: ^11
dependencies:
  - drupal:migrate
  - migrate_plus:migrate_plus
  - migrate_tools:migrate_tools
  - lcdle_core:lcdle_core
  - lcdle_contributor:lcdle_contributor
```

- [ ] **Step 3: Create migration group config**

`config/install/migrate_plus.migration_group.lcdle_wp.yml`:

```yaml
langcode: fr
status: true
dependencies:
  enforced:
    module:
      - lcdle_migrate
id: lcdle_wp
label: 'WordPress → Drupal (laculturedelecran.com)'
description: 'Migrates all content from the WordPress SQL dump into the Drupal content model.'
source_type: 'WordPress MySQL dump'
module: lcdle_migrate
shared_configuration:
  source:
    key: wp
    database:
      target: default
      key: wp
```

- [ ] **Step 4: Enable the module**

Run: `ddev drush pm:install lcdle_migrate -y`
Expected: success (installs migrate, migrate_plus, migrate_tools transitively).

Run: `ddev drush migrate:status --group=lcdle_wp`
Expected: empty list (no migrations defined yet) or "No migrations found."

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add lcdle_migrate module with migrate_plus + migrate_tools

Scaffolds the migration module with the lcdle_wp migration group.
Shared group config registers 'wp' as the source database key so all
child migrations inherit the WP MySQL connection."
```

---

## Task 3 — WP content audit drush command

**Files:**
- Create: `web/modules/custom/lcdle_migrate/src/Commands/MigrateAuditCommands.php`
- Create: `web/modules/custom/lcdle_migrate/lcdle_migrate.services.yml`

- [ ] **Step 1: Create the drush command**

`src/Commands/MigrateAuditCommands.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Commands;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drush\Attributes\Command;
use Drush\Commands\DrushCommands;

/**
 * Drush commands for auditing WordPress content before/after migration.
 */
final class MigrateAuditCommands extends DrushCommands {

  private const WP_PREFIX = '6Q3sMyXEg_';

  /**
   * Audits the WordPress source database and reports content counts.
   */
  #[Command(name: 'lcdle:audit-wp', aliases: ['law'])]
  public function auditWp(): void {
    $wp = Database::getConnection('default', 'wp');

    $this->io()->title('WordPress Source Audit');

    $users = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "users}")->fetchField();
    $this->io()->writeln("Users: {$users}");

    $posts = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "posts} WHERE post_status = 'publish' AND post_type = 'post'")->fetchField();
    $this->io()->writeln("Published posts: {$posts}");

    $pages = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "posts} WHERE post_status = 'publish' AND post_type = 'page'")->fetchField();
    $this->io()->writeln("Published pages: {$pages}");

    $attachments = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "posts} WHERE post_type = 'attachment'")->fetchField();
    $this->io()->writeln("Attachments (media): {$attachments}");

    $categories = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "term_taxonomy} WHERE taxonomy = 'category'")->fetchField();
    $this->io()->writeln("Categories: {$categories}");

    $tags = (int) $wp->query("SELECT COUNT(*) FROM {" . self::WP_PREFIX . "term_taxonomy} WHERE taxonomy = 'post_tag'")->fetchField();
    $this->io()->writeln("Tags: {$tags}");

    // Root categories (no parent).
    $root_cats = $wp->query("
      SELECT t.name, t.slug, tt.count
      FROM {" . self::WP_PREFIX . "terms} t
      JOIN {" . self::WP_PREFIX . "term_taxonomy} tt ON t.term_id = tt.term_id
      WHERE tt.taxonomy = 'category' AND tt.parent = 0
      ORDER BY t.name
    ")->fetchAll();

    $this->io()->section('Root categories (→ themes)');
    foreach ($root_cats as $cat) {
      $this->io()->writeln("  {$cat->name} ({$cat->slug}) — {$cat->count} posts");
    }

    // Child categories (has parent).
    $child_cats = $wp->query("
      SELECT t.name, t.slug, tt.count, p.name AS parent_name
      FROM {" . self::WP_PREFIX . "terms} t
      JOIN {" . self::WP_PREFIX . "term_taxonomy} tt ON t.term_id = tt.term_id
      JOIN {" . self::WP_PREFIX . "terms} p ON tt.parent = p.term_id
      WHERE tt.taxonomy = 'category' AND tt.parent != 0
      ORDER BY p.name, t.name
    ")->fetchAll();

    $this->io()->section('Child categories (→ chroniques)');
    foreach ($child_cats as $cat) {
      $this->io()->writeln("  {$cat->parent_name} > {$cat->name} ({$cat->slug}) — {$cat->count} posts");
    }

    // Shortcode inventory.
    $bodies = $wp->query("
      SELECT post_content FROM {" . self::WP_PREFIX . "posts}
      WHERE post_status = 'publish' AND post_type = 'post'
    ")->fetchCol();

    $shortcodes = [];
    foreach ($bodies as $body) {
      if (preg_match_all('/\[([a-z0-9_-]+)[\s\]]/i', $body, $matches)) {
        foreach ($matches[1] as $sc) {
          $shortcodes[$sc] = ($shortcodes[$sc] ?? 0) + 1;
        }
      }
    }
    arsort($shortcodes);

    $this->io()->section('Shortcodes found in published posts');
    foreach ($shortcodes as $name => $count) {
      $this->io()->writeln("  [{$name}] — {$count} occurrences");
    }

    $this->io()->success("Audit complete.");
  }

  /**
   * Audits the Drupal side after migration, compares counts.
   */
  #[Command(name: 'lcdle:audit-drupal', aliases: ['lad'])]
  public function auditDrupal(): void {
    $this->io()->title('Drupal Post-Migration Audit');

    $users = (int) \Drupal::entityTypeManager()->getStorage('user')
      ->getQuery()->accessCheck(FALSE)->count()->execute();
    $this->io()->writeln("Users: {$users}");

    $profiles = (int) \Drupal::entityTypeManager()->getStorage('profile')
      ->getQuery()->accessCheck(FALSE)
      ->condition('type', 'contributor_profile')
      ->count()->execute();
    $this->io()->writeln("Contributor profiles: {$profiles}");

    $articles = (int) \Drupal::entityTypeManager()->getStorage('node')
      ->getQuery()->accessCheck(FALSE)
      ->condition('type', 'article')
      ->count()->execute();
    $this->io()->writeln("Articles: {$articles}");

    $media = (int) \Drupal::entityTypeManager()->getStorage('media')
      ->getQuery()->accessCheck(FALSE)
      ->condition('bundle', 'image')
      ->count()->execute();
    $this->io()->writeln("Media (image): {$media}");

    $themes = (int) \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->getQuery()->accessCheck(FALSE)
      ->condition('vid', 'themes')
      ->count()->execute();
    $this->io()->writeln("Themes terms: {$themes}");

    $chroniques = (int) \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->getQuery()->accessCheck(FALSE)
      ->condition('vid', 'chroniques')
      ->count()->execute();
    $this->io()->writeln("Chroniques terms: {$chroniques}");

    $tags = (int) \Drupal::entityTypeManager()->getStorage('taxonomy_term')
      ->getQuery()->accessCheck(FALSE)
      ->condition('vid', 'tags_legacy')
      ->count()->execute();
    $this->io()->writeln("Tags legacy terms: {$tags}");

    $redirects = (int) \Drupal::entityTypeManager()->getStorage('redirect')
      ->getQuery()->accessCheck(FALSE)
      ->count()->execute();
    $this->io()->writeln("Redirects: {$redirects}");

    $this->io()->success("Audit complete.");
  }

}
```

`lcdle_migrate.services.yml`:

```yaml
services:
  lcdle_migrate.commands:
    class: Drupal\lcdle_migrate\Commands\MigrateAuditCommands
    tags:
      - { name: drush.command }
```

- [ ] **Step 2: Run the WP audit**

Run: `ddev drush cr && ddev drush lcdle:audit-wp`
Expected: prints content counts + category tree + shortcode inventory. Save this output — it's the baseline for post-migration verification.

- [ ] **Step 3: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add drush audit commands for pre/post migration verification

lcdle:audit-wp queries the WordPress source DB and reports: user count,
post/page/attachment counts, categories (root vs child), tags, and a
shortcode inventory across all published post bodies.

lcdle:audit-drupal does the same on the Drupal side after migration so
the two can be compared for completeness."
```

---

## Task 4 — Process plugin: ShortcodeConverter + unit tests

**Files:**
- Create: `web/modules/custom/lcdle_migrate/src/Plugin/migrate/process/ShortcodeConverter.php`
- Create: `web/modules/custom/lcdle_migrate/tests/src/Unit/ShortcodeConverterTest.php`

This plugin converts known WP shortcodes in post_content to standard HTML. Strategy per shortcode:

| Shortcode | Conversion |
|---|---|
| `[caption id="..." ...]<img ...>[/caption]` | `<figure><img ...><figcaption>caption text</figcaption></figure>` |
| `[gallery ids="1,2,3"]` | `<div class="wp-gallery"><!-- gallery ids=1,2,3 --></div>` (placeholder — real rendering deferred to theming Phase 2) |
| `[embed]URL[/embed]` | `URL` (strip the shortcode wrapper — CKEditor/Media embed will handle oEmbed rendering) |
| `[contact-form-7 ...]` | `<!-- contact-form removed -->` (6 occurrences, handled manually post-migration) |
| Unknown shortcodes | Left as-is with a log warning |

- [ ] **Step 1: Write unit tests**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_migrate\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for ShortcodeConverter logic.
 *
 * Tests the static conversion methods directly without Drupal bootstrap.
 *
 * @group lcdle_migrate
 */
final class ShortcodeConverterTest extends TestCase {

  /**
   * Tests [caption] conversion to <figure>.
   */
  public function testCaptionToFigure(): void {
    $input = '[caption id="attachment_123" align="alignnone" width="640"]<img src="http://example.com/image.jpg" alt="test" /> My caption text[/caption]';
    $expected = '<figure><img src="http://example.com/image.jpg" alt="test" /><figcaption>My caption text</figcaption></figure>';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertSame($expected, trim($result));
  }

  /**
   * Tests [embed] is stripped to just the URL.
   */
  public function testEmbedStripped(): void {
    $input = 'Before [embed]https://www.youtube.com/watch?v=123[/embed] After';
    $expected = 'Before https://www.youtube.com/watch?v=123 After';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertSame($expected, $result);
  }

  /**
   * Tests [gallery] becomes a placeholder div.
   */
  public function testGalleryPlaceholder(): void {
    $input = 'Text [gallery ids="1,2,3" columns="3"] more text';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertStringContainsString('wp-gallery', $result);
    $this->assertStringContainsString('ids=1,2,3', $result);
    $this->assertStringNotContainsString('[gallery', $result);
  }

  /**
   * Tests [contact-form-7] is replaced with a comment.
   */
  public function testContactFormRemoved(): void {
    $input = 'Text [contact-form-7 id="42" title="Contact"] more';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertStringContainsString('contact-form removed', $result);
    $this->assertStringNotContainsString('[contact-form-7', $result);
  }

  /**
   * Tests unknown shortcodes are left intact.
   */
  public function testUnknownShortcodeLeftIntact(): void {
    $input = 'Text [unknown_shortcode attr="val"]content[/unknown_shortcode] more';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertStringContainsString('[unknown_shortcode', $result);
  }

  /**
   * Tests text without shortcodes passes through unchanged.
   */
  public function testPlainTextUnchanged(): void {
    $input = '<p>Just regular HTML content.</p>';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\ShortcodeConverter::convertShortcodes($input);
    $this->assertSame($input, $result);
  }

}
```

- [ ] **Step 2: Run — expect FAIL (class not found)**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_migrate/tests/src/Unit/ShortcodeConverterTest.php"`

- [ ] **Step 3: Create the process plugin**

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Converts WordPress shortcodes in post_content to standard HTML.
 *
 * Usage in migration YAML:
 * @code
 * process:
 *   body/value:
 *     plugin: shortcode_converter
 *     source: post_content
 * @endcode
 */
#[MigrateProcess('shortcode_converter')]
final class ShortcodeConverter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    if (!is_string($value) || $value === '') {
      return '';
    }
    return self::convertShortcodes($value);
  }

  /**
   * Converts known shortcodes to HTML.
   *
   * Public static so it can be unit-tested without Drupal bootstrap.
   */
  public static function convertShortcodes(string $text): string {
    // [caption ...]<img ...>Caption text[/caption] → <figure>.
    $text = preg_replace_callback(
      '/\[caption[^\]]*\](.*?)\[\/caption\]/s',
      static function (array $matches): string {
        $inner = trim($matches[1]);
        if (preg_match('/(<img[^>]+>)\s*(.*)/s', $inner, $parts)) {
          $img = trim($parts[1]);
          $caption = trim($parts[2]);
          return '<figure>' . $img . '<figcaption>' . $caption . '</figcaption></figure>';
        }
        return $inner;
      },
      $text
    );

    // [embed]URL[/embed] → URL.
    $text = preg_replace('/\[embed\](.*?)\[\/embed\]/s', '$1', $text);

    // [gallery ids="1,2,3" ...] → placeholder div.
    $text = preg_replace_callback(
      '/\[gallery([^\]]*)\]/',
      static function (array $matches): string {
        $attrs = trim($matches[1]);
        $ids = '';
        if (preg_match('/ids="([^"]+)"/', $attrs, $id_match)) {
          $ids = $id_match[1];
        }
        return '<div class="wp-gallery"><!-- gallery ids=' . $ids . ' --></div>';
      },
      $text
    );

    // [contact-form-7 ...] → HTML comment.
    $text = preg_replace('/\[contact-form-7[^\]]*\]/', '<!-- contact-form removed -->', $text);
    $text = preg_replace('/\[contact-form[^\]]*\]/', '<!-- contact-form removed -->', $text);

    return $text;
  }

}
```

- [ ] **Step 4: Run unit tests — expect PASS**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_migrate/tests/src/Unit/ShortcodeConverterTest.php"`
Expected: 6/6 PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add ShortcodeConverter process plugin for WP migration

Converts [caption] to <figure>, strips [embed] wrappers, replaces
[gallery] with a placeholder div (rendering deferred to Phase 2
theming), and removes [contact-form-7] references. Unknown shortcodes
are left intact. The conversion logic is static + unit-testable without
Drupal bootstrap."
```

---

## Task 5 — Process plugin: InternalLinkRewriter + unit tests

**Files:**
- Create: `web/modules/custom/lcdle_migrate/src/Plugin/migrate/process/InternalLinkRewriter.php`
- Create: `web/modules/custom/lcdle_migrate/tests/src/Unit/InternalLinkRewriterTest.php`

This plugin rewrites internal links in post bodies from old WP URLs to new Drupal paths.

Pattern: `https://laculturedelecran.com/some-slug/` → `/some-slug/` (relative path, since the domain stays the same after DNS switchover). The relative path will resolve via Drupal's redirect module (Task 10) to the new canonical URL.

- [ ] **Step 1: Write unit tests**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_migrate\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for InternalLinkRewriter logic.
 *
 * @group lcdle_migrate
 */
final class InternalLinkRewriterTest extends TestCase {

  private const WP_DOMAIN = 'laculturedelecran.com';

  /**
   * Tests absolute internal links are converted to relative.
   */
  public function testAbsoluteToRelative(): void {
    $input = '<a href="https://laculturedelecran.com/mon-article/">Link</a>';
    $expected = '<a href="/mon-article/">Link</a>';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter::rewriteLinks($input, self::WP_DOMAIN);
    $this->assertSame($expected, $result);
  }

  /**
   * Tests http:// variant is also handled.
   */
  public function testHttpAlsoConverted(): void {
    $input = '<a href="http://laculturedelecran.com/page/">Link</a>';
    $expected = '<a href="/page/">Link</a>';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter::rewriteLinks($input, self::WP_DOMAIN);
    $this->assertSame($expected, $result);
  }

  /**
   * Tests external links are left untouched.
   */
  public function testExternalLinksUntouched(): void {
    $input = '<a href="https://example.com/page/">External</a>';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter::rewriteLinks($input, self::WP_DOMAIN);
    $this->assertSame($input, $result);
  }

  /**
   * Tests src attributes in images are also rewritten.
   */
  public function testImageSrcRewritten(): void {
    $input = '<img src="https://laculturedelecran.com/wp-content/uploads/2023/photo.jpg" />';
    $expected = '<img src="/wp-content/uploads/2023/photo.jpg" />';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter::rewriteLinks($input, self::WP_DOMAIN);
    $this->assertSame($expected, $result);
  }

  /**
   * Tests text without links passes through.
   */
  public function testPlainTextUnchanged(): void {
    $input = 'No links here, just text about laculturedelecran.com.';

    $result = \Drupal\lcdle_migrate\Plugin\migrate\process\InternalLinkRewriter::rewriteLinks($input, self::WP_DOMAIN);
    $this->assertSame($input, $result);
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

- [ ] **Step 3: Create the plugin**

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_migrate\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Rewrites internal WordPress URLs in body text to relative Drupal paths.
 *
 * Usage in migration YAML:
 * @code
 * process:
 *   body/value:
 *     - plugin: shortcode_converter
 *       source: post_content
 *     - plugin: internal_link_rewriter
 * @endcode
 */
#[MigrateProcess('internal_link_rewriter')]
final class InternalLinkRewriter extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property): string {
    if (!is_string($value) || $value === '') {
      return '';
    }
    $domain = $this->configuration['domain'] ?? 'laculturedelecran.com';
    return self::rewriteLinks($value, $domain);
  }

  /**
   * Rewrites absolute internal URLs to relative paths.
   *
   * Public static for unit testing without Drupal bootstrap.
   */
  public static function rewriteLinks(string $text, string $domain): string {
    $escaped = preg_quote($domain, '/');
    return preg_replace(
      '/https?:\/\/' . $escaped . '/',
      '',
      $text
    );
  }

}
```

- [ ] **Step 4: Run — expect PASS**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_migrate/tests/src/Unit/InternalLinkRewriterTest.php"`
Expected: 5/5 PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add InternalLinkRewriter process plugin for WP migration

Converts absolute internal WP URLs (https://laculturedelecran.com/...)
to relative paths (/...) so they work after DNS switchover. External
links are left untouched. Handles both href and src attributes. The
resulting relative paths will be caught by the redirect module (Task 10)
and forwarded to the new canonical Drupal URLs."
```

---

## Task 6 — Migration: WP users → Drupal users + contributor profiles

**Files:**
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_users.yml`
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_contributor_profiles.yml`

- [ ] **Step 1: Create the users migration**

`migrations/wp_users.yml`:

```yaml
id: wp_users
label: 'WP Users → Drupal Users'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      u.ID,
      u.user_login,
      u.user_email,
      u.user_nicename,
      u.display_name,
      u.user_registered
    FROM 6Q3sMyXEg_users u
    WHERE u.ID > 0
  ids:
    ID:
      type: integer
process:
  name: user_login
  mail: user_email
  init: user_email
  status:
    plugin: default_value
    default_value: 1
  created:
    plugin: format_date
    source: user_registered
    from_format: 'Y-m-d H:i:s'
    to_format: 'U'
  roles:
    plugin: default_value
    default_value:
      - contributor_trusted
destination:
  plugin: 'entity:user'
migration_dependencies: {}
```

Note: The `source_sql` plugin comes from `migrate_plus`. The query uses the raw table prefix. All WP users get role `contributor_trusted` (per brainstorming decision).

- [ ] **Step 2: Create the contributor profiles migration**

`migrations/wp_contributor_profiles.yml`:

```yaml
id: wp_contributor_profiles
label: 'WP Users → Contributor Profiles'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      u.ID,
      u.user_nicename,
      u.display_name,
      (SELECT meta_value FROM 6Q3sMyXEg_usermeta WHERE user_id = u.ID AND meta_key = 'description' LIMIT 1) AS bio
    FROM 6Q3sMyXEg_users u
    WHERE u.ID > 0
  ids:
    ID:
      type: integer
process:
  uid:
    plugin: migration_lookup
    migration: wp_users
    source: ID
  type:
    plugin: default_value
    default_value: contributor_profile
  field_slug: user_nicename
  field_display_name: display_name
  field_bio/value: bio
  field_bio/format:
    plugin: default_value
    default_value: plain_text
  status:
    plugin: default_value
    default_value: 1
destination:
  plugin: 'entity:profile'
migration_dependencies:
  required:
    - wp_users
```

- [ ] **Step 3: Run the users migration**

Run:
```bash
ddev drush cr
ddev drush migrate:import wp_users --feedback=10
```

Expected: 19 users imported.

Run: `ddev drush migrate:import wp_contributor_profiles --feedback=10`
Expected: 19 profiles imported.

- [ ] **Step 4: Verify**

Run: `ddev drush lcdle:audit-drupal | head -5`
Expected: Users ~20 (19 WP + 1 admin), Contributor profiles 19.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add migration configs for WP users + contributor profiles

19 WP authors are migrated as contributor_trusted users. A second
migration creates ContributorProfile entities linked by uid, with
slug from user_nicename, display_name, and bio from usermeta."
```

---

## Task 7 — Migration: WP terms → themes + chroniques + tags_legacy

**Files:**
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_terms_themes.yml`
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_terms_chroniques.yml`
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_terms_tags_legacy.yml`

- [ ] **Step 1: Create themes migration (root categories)**

`migrations/wp_terms_themes.yml`:

```yaml
id: wp_terms_themes
label: 'WP Root Categories → Themes'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      t.term_id,
      t.name,
      t.slug
    FROM 6Q3sMyXEg_terms t
    JOIN 6Q3sMyXEg_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'category' AND tt.parent = 0
      AND t.slug != 'uncategorized'
  ids:
    term_id:
      type: integer
process:
  vid:
    plugin: default_value
    default_value: themes
  name: name
destination:
  plugin: 'entity:taxonomy_term'
migration_dependencies: {}
```

- [ ] **Step 2: Create chroniques migration (child categories)**

`migrations/wp_terms_chroniques.yml`:

```yaml
id: wp_terms_chroniques
label: 'WP Child Categories → Chroniques'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      t.term_id,
      t.name,
      t.slug,
      tt.parent AS parent_term_id
    FROM 6Q3sMyXEg_terms t
    JOIN 6Q3sMyXEg_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'category' AND tt.parent != 0
  ids:
    term_id:
      type: integer
process:
  vid:
    plugin: default_value
    default_value: chroniques
  name: name
destination:
  plugin: 'entity:taxonomy_term'
migration_dependencies:
  required:
    - wp_terms_themes
```

- [ ] **Step 3: Create tags migration**

`migrations/wp_terms_tags_legacy.yml`:

```yaml
id: wp_terms_tags_legacy
label: 'WP Tags → Tags Legacy'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      t.term_id,
      t.name,
      t.slug
    FROM 6Q3sMyXEg_terms t
    JOIN 6Q3sMyXEg_term_taxonomy tt ON t.term_id = tt.term_id
    WHERE tt.taxonomy = 'post_tag'
  ids:
    term_id:
      type: integer
process:
  vid:
    plugin: default_value
    default_value: tags_legacy
  name: name
destination:
  plugin: 'entity:taxonomy_term'
migration_dependencies: {}
```

- [ ] **Step 4: Run all three**

```bash
ddev drush migrate:import wp_terms_themes --feedback=10
ddev drush migrate:import wp_terms_chroniques --feedback=10
ddev drush migrate:import wp_terms_tags_legacy --feedback=10
```

- [ ] **Step 5: Verify**

Run: `ddev drush lcdle:audit-drupal | grep -E "(Themes|Chroniques|Tags)"`
Expected: counts matching WP audit output from Task 3.

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add migration configs for WP categories and tags

Root WP categories (no parent) → themes vocabulary. Child WP categories
(has parent) → chroniques vocabulary. WP tags → tags_legacy vocabulary
(quarantine for manual triage). 'Uncategorized' excluded from themes."
```

---

## Task 8 — Migration: WP attachments → Media entities

**Files:**
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_media_files.yml`
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_media_entities.yml`

- [ ] **Step 1: Copy WP uploads to Drupal's public files**

Before migration, rsync the uploads into Drupal's files directory so Drupal can reference them:

```bash
ddev exec "mkdir -p /var/www/html/web/sites/default/files/migrated"
rsync -av backup-wp/wordpress-files/wp-content/uploads/ web/sites/default/files/migrated/
```

Expected: uploads synced. This is a one-time operation.

- [ ] **Step 2: Create the media files migration (file entities)**

`migrations/wp_media_files.yml`:

```yaml
id: wp_media_files
label: 'WP Attachments → Drupal File Entities'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      p.ID,
      p.post_title,
      p.post_mime_type,
      (SELECT meta_value FROM 6Q3sMyXEg_postmeta WHERE post_id = p.ID AND meta_key = '_wp_attached_file' LIMIT 1) AS file_path
    FROM 6Q3sMyXEg_posts p
    WHERE p.post_type = 'attachment'
      AND p.post_mime_type LIKE 'image/%'
  ids:
    ID:
      type: integer
process:
  filename: post_title
  uri:
    plugin: concat
    source:
      - constants/file_base
      - file_path
    delimiter: ''
  filemime: post_mime_type
  status:
    plugin: default_value
    default_value: 1
source_constants:
  file_base: 'public://migrated/'
destination:
  plugin: 'entity:file'
migration_dependencies: {}
```

- [ ] **Step 3: Create the media entities migration**

`migrations/wp_media_entities.yml`:

```yaml
id: wp_media_entities
label: 'WP Attachments → Drupal Media (image)'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      p.ID,
      p.post_title,
      (SELECT meta_value FROM 6Q3sMyXEg_postmeta WHERE post_id = p.ID AND meta_key = '_wp_attachment_image_alt' LIMIT 1) AS alt_text
    FROM 6Q3sMyXEg_posts p
    WHERE p.post_type = 'attachment'
      AND p.post_mime_type LIKE 'image/%'
  ids:
    ID:
      type: integer
process:
  bundle:
    plugin: default_value
    default_value: image
  name: post_title
  field_media_image/target_id:
    plugin: migration_lookup
    migration: wp_media_files
    source: ID
  field_media_image/alt:
    plugin: null_coalesce
    source:
      - alt_text
      - post_title
  status:
    plugin: default_value
    default_value: 1
destination:
  plugin: 'entity:media'
migration_dependencies:
  required:
    - wp_media_files
```

- [ ] **Step 4: Run the media migrations**

```bash
ddev drush migrate:import wp_media_files --feedback=50
ddev drush migrate:import wp_media_entities --feedback=50
```

Expected: image files + media entities created. The count should match WP attachment count for image MIME types.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add migration configs for WP media (images only)

Two-phase migration: wp_media_files creates Drupal file entities
pointing at the rsynced wp-content/uploads files, then wp_media_entities
creates Media entities (bundle=image) referencing those files with
alt text from WP postmeta. Non-image attachments are excluded — they
can be added later if needed."
```

---

## Task 9 — Migration: WP posts → Articles

**Files:**
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_posts.yml`

This is the main migration. It depends on all previous migrations.

- [ ] **Step 1: Create the articles migration**

`migrations/wp_posts.yml`:

```yaml
id: wp_posts
label: 'WP Posts → Drupal Articles'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      p.ID,
      p.post_title,
      p.post_content,
      p.post_excerpt,
      p.post_name,
      p.post_author,
      p.post_date,
      p.post_modified,
      p.post_status,
      (SELECT meta_value FROM 6Q3sMyXEg_postmeta
       WHERE post_id = p.ID AND meta_key = '_thumbnail_id' LIMIT 1) AS featured_image_id,
      (SELECT GROUP_CONCAT(tt.term_id)
       FROM 6Q3sMyXEg_term_relationships tr
       JOIN 6Q3sMyXEg_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
       WHERE tr.object_id = p.ID AND tt.taxonomy = 'category' AND tt.parent = 0
      ) AS category_ids,
      (SELECT GROUP_CONCAT(tt.term_id)
       FROM 6Q3sMyXEg_term_relationships tr
       JOIN 6Q3sMyXEg_term_taxonomy tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
       WHERE tr.object_id = p.ID AND tt.taxonomy = 'category' AND tt.parent != 0
      ) AS chronique_ids
    FROM 6Q3sMyXEg_posts p
    WHERE p.post_status = 'publish' AND p.post_type = 'post'
  ids:
    ID:
      type: integer
process:
  type:
    plugin: default_value
    default_value: article
  title: post_title
  body/value:
    - plugin: shortcode_converter
      source: post_content
    - plugin: internal_link_rewriter
  body/format:
    plugin: default_value
    default_value: plain_text
  field_excerpt: post_excerpt
  uid:
    plugin: migration_lookup
    migration: wp_users
    source: post_author
  field_cover_image:
    plugin: migration_lookup
    migration: wp_media_entities
    source: featured_image_id
    no_stub: true
  field_themes:
    plugin: explode
    source: category_ids
    delimiter: ','
  'field_themes/*/target_id':
    plugin: migration_lookup
    migration: wp_terms_themes
  field_chronique:
    plugin: migration_lookup
    migration: wp_terms_chroniques
    source: chronique_ids
    no_stub: true
  created:
    plugin: format_date
    source: post_date
    from_format: 'Y-m-d H:i:s'
    to_format: 'U'
  changed:
    plugin: format_date
    source: post_modified
    from_format: 'Y-m-d H:i:s'
    to_format: 'U'
  moderation_state:
    plugin: default_value
    default_value: published
destination:
  plugin: 'entity:node'
migration_dependencies:
  required:
    - wp_users
    - wp_terms_themes
    - wp_terms_chroniques
    - wp_media_entities
```

Note: the `field_themes` mapping using `explode` + sub-process `migration_lookup` may need adjustment depending on how `migrate_plus` handles multi-value entity reference mapping. The exact process pipeline for multi-value term references might require using the `sub_process` plugin. If the simple approach fails, replace with:

```yaml
  field_themes:
    - plugin: explode
      source: category_ids
      delimiter: ','
    - plugin: sub_process
      process:
        target_id:
          plugin: migration_lookup
          migration: wp_terms_themes
          source: '0'
```

Similarly, `field_chronique` with a single `chronique_ids` value that might be NULL — use `skip_on_empty` or `null_coalesce`:

```yaml
  field_chronique:
    - plugin: skip_on_empty
      source: chronique_ids
      method: process
    - plugin: explode
      delimiter: ','
    - plugin: extract
      index: [0]
    - plugin: migration_lookup
      migration: wp_terms_chroniques
      no_stub: true
```

- [ ] **Step 2: Run the articles migration**

```bash
ddev drush migrate:import wp_posts --feedback=50
```

Expected: ~1,100 articles imported.

- [ ] **Step 3: Verify**

Run: `ddev drush lcdle:audit-drupal | head -5`
Expected: Articles count matches WP published posts count.

Spot-check a few articles:
```bash
ddev drush ev "\$n = \Drupal\node\Entity\Node::load(1); if (\$n) { print \$n->label() . PHP_EOL . \$n->toUrl()->toString() . PHP_EOL; }"
```

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add migration config for WP posts → Drupal articles

The main migration: maps post_content through shortcode_converter +
internal_link_rewriter process plugins, looks up author via wp_users,
cover image via wp_media_entities, themes via wp_terms_themes, chronique
via wp_terms_chroniques. All published posts arrive as moderation_state
'published'. Body format set to plain_text (upgraded to full_html in
Phase 2 theming)."
```

---

## Task 10 — Redirects 301

**Files:**
- Create: `web/modules/custom/lcdle_migrate/migrations/wp_redirects.yml`

This migration creates redirect entities for every published WP post, mapping old WP URL paths to new Drupal node paths.

- [ ] **Step 1: Create redirects migration**

`migrations/wp_redirects.yml`:

```yaml
id: wp_redirects
label: 'WP Post URLs → Drupal Redirects (301)'
migration_group: lcdle_wp
source:
  plugin: source_sql
  key: wp
  query: |
    SELECT
      p.ID,
      CONCAT('/', p.post_name, '/') AS old_path
    FROM 6Q3sMyXEg_posts p
    WHERE p.post_status = 'publish' AND p.post_type = 'post'
  ids:
    ID:
      type: integer
process:
  redirect_source/path:
    plugin: substr
    source: old_path
    start: 1
  redirect_redirect/uri:
    plugin: migration_lookup
    migration: wp_posts
    source: ID
  status_code:
    plugin: default_value
    default_value: 301
  language:
    plugin: default_value
    default_value: fr
destination:
  plugin: 'entity:redirect'
migration_dependencies:
  required:
    - wp_posts
```

Note: the `redirect_redirect/uri` from `migration_lookup` will return a node ID. We need to format it as `internal:/node/{nid}`. This may require a custom process:

```yaml
  redirect_redirect/uri:
    - plugin: migration_lookup
      migration: wp_posts
      source: ID
    - plugin: concat
      delimiter: ''
      source:
        - constants/node_prefix
        - '@_self'
source_constants:
  node_prefix: 'internal:/node/'
```

The exact process pipeline depends on how `migrate_plus` handles redirect URI format. The implementer should test and adjust.

Additionally, category redirects (e.g., `/musique/` → `/theme/musique`) should be added. These are a small number and can be created manually or via a simple drush script post-migration rather than a full migration config.

- [ ] **Step 2: Run**

```bash
ddev drush migrate:import wp_redirects --feedback=50
```

- [ ] **Step 3: Verify**

```bash
ddev drush ev "\$count = \Drupal::entityTypeManager()->getStorage('redirect')->getQuery()->accessCheck(FALSE)->count()->execute(); print 'Redirects: ' . \$count . PHP_EOL;"
```

Expected: count ≈ number of published posts.

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Add migration config for WP post URL redirects (301)

Creates a redirect entity for each published WP post, mapping the old
WordPress slug-based URL to the new Drupal node path. These redirects
are the SEO-critical bridge during and after the DNS switchover — every
indexed WP URL must resolve to a 301 pointing at the new canonical URL."
```

---

## Task 11 — Full migration dry-run + automated verification

- [ ] **Step 1: Rollback all migrations**

```bash
ddev drush migrate:rollback --group=lcdle_wp --feedback=50
```

- [ ] **Step 2: Run all migrations in dependency order**

```bash
ddev drush migrate:import --group=lcdle_wp --feedback=50
```

Expected: all migrations complete without errors.

- [ ] **Step 3: Run post-migration audit**

```bash
ddev drush lcdle:audit-wp
ddev drush lcdle:audit-drupal
```

Compare the two outputs side by side. Expected correspondences:
- WP users = Drupal contributor profiles
- WP published posts ≈ Drupal articles
- WP root categories ≈ Drupal themes terms
- WP child categories ≈ Drupal chroniques terms
- WP tags ≈ Drupal tags_legacy terms
- Redirect count ≈ published post count

- [ ] **Step 4: Spot-check 20 articles**

Pick 20 articles (first 10 + last 10 by ID) and verify:
- Title present
- Body non-empty and shortcodes converted
- Author linked to correct user
- At least one theme term assigned
- Cover image present (if WP had a featured image)
- Pathauto alias matches `/{author-slug}/{article-slug}` pattern

```bash
ddev drush ev "
\$nids = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
  ->accessCheck(FALSE)->condition('type','article')->sort('nid','ASC')->range(0,10)->execute();
foreach (\$nids as \$nid) {
  \$n = \Drupal\node\Entity\Node::load(\$nid);
  print \$nid . ' | ' . \$n->label() . ' | ' . \$n->toUrl()->toString() . PHP_EOL;
}
"
```

- [ ] **Step 5: Commit the final state of the migration module**

```bash
git add web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Complete migration dry-run verification

Full rollback + re-import of all WP migrations confirmed. Audit reports
match between WP source and Drupal destination. 20-article spot check
validates title, body, author, terms, cover image, and URL alias."
```

---

## Task 12 — Config export + tag + push

- [ ] **Step 1: Run quality checks**

```bash
ddev exec "vendor/bin/phpcs --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse --memory-limit=1G"
ddev exec "vendor/bin/phpunit --testsuite=unit"
```

Fix any issues. Kernel + functional tests for other modules should still pass (but won't test migration — that's drush-level).

- [ ] **Step 2: Export config**

```bash
ddev drush config:export -y
```

- [ ] **Step 3: Commit**

```bash
git add config/sync/ web/modules/custom/lcdle_migrate/
git -c commit.gpgsign=false commit -m "Export active config after Phase 1 migration module install

config/sync/ now includes lcdle_migrate, migrate_plus, migrate_tools
in core.extension. The migration configs in the module's migrations/
directory are discovery-based (not in config/sync) so they can be
iterated on without config import/export."
```

- [ ] **Step 4: Tag**

```bash
git tag -a phase-1-complete -m "Phase 1 WordPress migration complete

lcdle_migrate ships: 9 migration configs (users, profiles, themes,
chroniques, tags_legacy, media files, media entities, posts, redirects),
2 custom process plugins (ShortcodeConverter, InternalLinkRewriter)
with unit tests, and 2 drush audit commands (lcdle:audit-wp,
lcdle:audit-drupal).

~1100 articles, 19 contributors, media, taxonomy terms, and 301
redirects migrated and verified. Ready for Phase 2 (theming + launch)."
```

- [ ] **Step 5: Push**

```bash
git push origin main
git push origin phase-1-complete
```

---

## Self-Review

**Spec coverage:**

| Spec reference | Task |
|---|---|
| §6.2 Source = SQL dump + wp-content | 1 |
| §6.3 Mapping users | 6 |
| §6.3 Mapping posts | 9 |
| §6.3 Mapping categories → themes | 7 |
| §6.3 Mapping sub-categories → chroniques | 7 |
| §6.3 Mapping tags → tags_legacy | 7 |
| §6.3 Mapping media | 8 |
| §6.3 Comments not migrated | N/A (not included — correct) |
| §6.4 Internal link rewriting | 5 |
| §6.5 Redirects 301 | 10 |
| §6.6 Audit + verification | 3, 11 |
| Shortcode conversion | 4 |

**Out of scope (correctly deferred):**
- Category-based URL redirects (`/musique/` → `/theme/musique`) — manual post-migration
- Author page redirects (`/author/slug/` → `/slug`) — manual post-migration
- `body/format` upgrade to `full_html` — Phase 2 theming
- Comments JSON export — separate one-off script, not part of migration module

**Placeholder scan:** No TBD/TODO. Migration YAML process pipelines have alternative implementations noted inline for multi-value handling — the implementer chooses based on what works.

**Risks:**
- Migration YAML process pipelines for multi-value entity references (field_themes, field_chronique) may need adjustment — the plan provides two approaches and expects the implementer to test.
- `source_sql` plugin query syntax (raw table names with prefix) must match the actual dump. Task 3's audit validates this.
- Media file URIs assume the rsync target is `public://migrated/` — the file migration's `uri` process must produce this exact prefix.
