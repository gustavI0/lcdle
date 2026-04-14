---
id: PLAN-0B
type: implementation-plan
status: draft
last-updated: 2026-04-14
spec: SPEC-001
phase: "0B — Content model"
---

# Phase 0B — Content Model Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `lcdle_core` custom module that provisions the full editorial content model — content type `article` with its fields, 3 taxonomy vocabularies (`themes`, `chroniques`, `tags_legacy`), 4 user roles (`reader`, `contributor_new`, `contributor_trusted`, `editor`), the `article_workflow` Content Moderation workflow, and a minimal Pathauto pattern — all shipped as module-install config and covered by kernel + functional tests.

**Architecture:** Config-driven Drupal module. All content-model artifacts live in `web/modules/custom/lcdle_core/config/install/` so `drush pm:install lcdle_core` provisions them atomically. Tests use `KernelTestBase` with the module enabled to assert structural correctness, and `BrowserTestBase` to validate workflow transitions under real HTTP + session. No custom PHP logic in this phase — only config, info.yml, and tests. That logic (slug validation, profile route, newsletter) belongs to Phase 0C.

**Tech Stack:** Drupal 11 (node, taxonomy, media, image, file, text, datetime, views, workflows, content_moderation, path, path_alias) + `drupal/pathauto`. Tests: PHPUnit 11 via Drupal's kernel/functional suites.

---

## Scope Check

Plan 0B covers a single subsystem (the content model) and produces a self-contained, installable module with passing tests. Out of scope: `ContributorProfile`, `NewsletterSubscriber`, slug validation, route `/{slug}` — all deferred to Plan 0C. The Pathauto pattern in this plan uses `[node:title]` temporarily; Plan 0C updates it to include the author slug.

---

## File Structure

### Created by this plan

```
web/modules/custom/lcdle_core/
├── lcdle_core.info.yml
├── config/
│   └── install/
│       ├── user.role.reader.yml
│       ├── user.role.contributor_new.yml
│       ├── user.role.contributor_trusted.yml
│       ├── user.role.editor.yml
│       ├── taxonomy.vocabulary.themes.yml
│       ├── taxonomy.vocabulary.chroniques.yml
│       ├── taxonomy.vocabulary.tags_legacy.yml
│       ├── media.type.image.yml
│       ├── core.entity_form_display.media.image.default.yml
│       ├── core.entity_view_display.media.image.default.yml
│       ├── node.type.article.yml
│       ├── field.storage.node.field_themes.yml
│       ├── field.storage.node.field_chronique.yml
│       ├── field.storage.node.field_cover_image.yml
│       ├── field.storage.node.field_excerpt.yml
│       ├── field.field.node.article.body.yml
│       ├── field.field.node.article.field_themes.yml
│       ├── field.field.node.article.field_chronique.yml
│       ├── field.field.node.article.field_cover_image.yml
│       ├── field.field.node.article.field_excerpt.yml
│       ├── core.entity_form_display.node.article.default.yml
│       ├── core.entity_view_display.node.article.default.yml
│       ├── core.entity_view_display.node.article.teaser.yml
│       ├── workflows.workflow.article_workflow.yml
│       └── pathauto.pattern.article.yml
└── tests/
    └── src/
        ├── Kernel/
        │   ├── ModuleInstallTest.php
        │   ├── RolesInstallTest.php
        │   ├── VocabulariesInstallTest.php
        │   ├── ArticleContentTypeTest.php
        │   ├── ArticleFieldsTest.php
        │   ├── WorkflowTest.php
        │   └── PathautoPatternTest.php
        └── Functional/
            └── WorkflowPermissionsTest.php
```

### Files modified by this plan

- `config/sync/core.extension.yml` — `lcdle_core: 0` added after module enable.
- `config/sync/*.yml` — full re-export after module install captures all derived config.
- `.github/workflows/ci.yml` — kernel + functional test jobs added.

---

## Conventions Used Throughout

- **TDD**: Red → Green → Refactor → Commit. Every task starts by writing a failing test.
- **Module config is authoritative**: YAML files in `lcdle_core/config/install/` are the source of truth. After a task, `drush pm:install lcdle_core` followed by `drush config:export -y` brings `config/sync/` in line.
- **Kernel tests** for structural assertions (config entities exist with right shape). **Functional tests** for behavior (roles + workflow + HTTP).
- **Inside DDEV**: all Drupal-touching commands run via `ddev drush ...` or `ddev exec "vendor/bin/phpunit ..."`.
- **One task = one commit** unless explicitly noted.

---

## Task 1 — Module skeleton and install test

**Files:**
- Create: `web/modules/custom/lcdle_core/lcdle_core.info.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/ModuleInstallTest.php`

- [ ] **Step 1: Write the failing kernel test**

Create `web/modules/custom/lcdle_core/tests/src/Kernel/ModuleInstallTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_core
 */
final class ModuleInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_core'),
      'lcdle_core module is enabled.',
    );
  }

}
```

- [ ] **Step 2: Run the test — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ModuleInstallTest"`
Expected: FAIL — "Unable to find the module lcdle_core" or similar.

- [ ] **Step 3: Create the module info file**

Create `web/modules/custom/lcdle_core/lcdle_core.info.yml`:

```yaml
name: 'LCDLE Core'
type: module
description: 'Content model for La Culture de l''Écran — article content type, vocabularies, roles, moderation workflow, and URL patterns.'
package: 'LCDLE'
core_version_requirement: ^11
dependencies:
  - drupal:node
  - drupal:taxonomy
  - drupal:text
  - drupal:datetime
  - drupal:media
  - drupal:media_library
  - drupal:image
  - drupal:file
  - drupal:views
  - drupal:workflows
  - drupal:content_moderation
  - drupal:path
  - drupal:path_alias
  - pathauto:pathauto
```

- [ ] **Step 4: Run the test — expect pass**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ModuleInstallTest"`
Expected: PASS (1 test, 1 assertion).

- [ ] **Step 5: Verify install works end-to-end via drush**

Run: `ddev drush pm:install lcdle_core -y`
Expected: `[success] Successfully enabled: lcdle_core`.

Run: `ddev drush pm:list --status=enabled --format=list | grep lcdle_core`
Expected: prints `lcdle_core`.

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Scaffold lcdle_core module with install test

Ships the info.yml with the full list of dependencies this module will
need as content-model config is progressively added. First kernel test
asserts the module is installable — the TDD red/green baseline every
subsequent task builds on."
```

---

## Task 2 — Four user roles

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/user.role.reader.yml`
- Create: `web/modules/custom/lcdle_core/config/install/user.role.contributor_new.yml`
- Create: `web/modules/custom/lcdle_core/config/install/user.role.contributor_trusted.yml`
- Create: `web/modules/custom/lcdle_core/config/install/user.role.editor.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/RolesInstallTest.php`

- [ ] **Step 1: Write the failing test**

Create `web/modules/custom/lcdle_core/tests/src/Kernel/RolesInstallTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group lcdle_core
 */
final class RolesInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testAllProjectRolesExist(): void {
    foreach (['reader', 'contributor_new', 'contributor_trusted', 'editor'] as $rid) {
      $role = Role::load($rid);
      $this->assertNotNull($role, "Role {$rid} is installed.");
    }
  }

  public function testContributorNewHasCreateArticlePermission(): void {
    $role = Role::load('contributor_new');
    $this->assertTrue(
      $role->hasPermission('create article content'),
      'contributor_new can create article content.',
    );
  }

  public function testContributorNewCannotPublishArticle(): void {
    $role = Role::load('contributor_new');
    $this->assertFalse(
      $role->hasPermission('use article_workflow transition publish'),
      'contributor_new cannot publish directly.',
    );
  }

  public function testContributorTrustedCanPublishArticle(): void {
    $role = Role::load('contributor_trusted');
    $this->assertTrue(
      $role->hasPermission('use article_workflow transition publish'),
      'contributor_trusted can publish directly.',
    );
  }

  public function testEditorCanModerateAnyContent(): void {
    $role = Role::load('editor');
    $this->assertTrue(
      $role->hasPermission('view any unpublished content'),
      'editor sees unpublished content for moderation.',
    );
    $this->assertTrue(
      $role->hasPermission('use article_workflow transition publish'),
      'editor can publish.',
    );
  }

}
```

- [ ] **Step 2: Run the test — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=RolesInstallTest"`
Expected: FAIL — roles not yet defined.

- [ ] **Step 3: Create role YAML files**

Create `web/modules/custom/lcdle_core/config/install/user.role.reader.yml`:

```yaml
langcode: fr
status: true
dependencies: {  }
id: reader
label: Lecteur
weight: 2
is_admin: false
permissions:
  - 'access content'
```

Create `web/modules/custom/lcdle_core/config/install/user.role.contributor_new.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - content_moderation
    - node
id: contributor_new
label: 'Contributeur (en probation)'
weight: 3
is_admin: false
permissions:
  - 'access content'
  - 'access content overview'
  - 'create article content'
  - 'delete own article content'
  - 'edit own article content'
  - 'use article_workflow transition create_new_draft'
  - 'use article_workflow transition submit_for_review'
  - 'view own unpublished content'
```

Create `web/modules/custom/lcdle_core/config/install/user.role.contributor_trusted.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - content_moderation
    - node
id: contributor_trusted
label: 'Contributeur'
weight: 4
is_admin: false
permissions:
  - 'access content'
  - 'access content overview'
  - 'create article content'
  - 'delete own article content'
  - 'edit own article content'
  - 'use article_workflow transition create_new_draft'
  - 'use article_workflow transition publish'
  - 'use article_workflow transition submit_for_review'
  - 'view own unpublished content'
```

Create `web/modules/custom/lcdle_core/config/install/user.role.editor.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - content_moderation
    - node
    - taxonomy
id: editor
label: Éditeur
weight: 5
is_admin: false
permissions:
  - 'access content'
  - 'access content overview'
  - 'administer taxonomy'
  - 'create article content'
  - 'delete any article content'
  - 'delete own article content'
  - 'edit any article content'
  - 'edit own article content'
  - 'use article_workflow transition archive'
  - 'use article_workflow transition create_new_draft'
  - 'use article_workflow transition publish'
  - 'use article_workflow transition reject'
  - 'use article_workflow transition restore_to_draft'
  - 'use article_workflow transition submit_for_review'
  - 'view all revisions'
  - 'view any unpublished content'
  - 'view latest version'
  - 'view own unpublished content'
```

Note: permissions reference `article content` and `article_workflow transition *` which do not exist yet. Drupal's config import will warn on missing permissions but still create the role. The permissions become live once the article content type (Task 4) and workflow (Task 9) are installed. Kernel tests in Task 2 that check specific permissions will pass only after Tasks 4 and 9 are also applied. **For Task 2, only the "role exists" test asserts will pass; the other four role-permission tests are expected to pass once Tasks 4 + 9 are in — we keep them in place as forward-looking assertions.** To make Task 2 TDD-clean, temporarily restrict the test to existence-only in Step 1 and add the permission-sensitive assertions in Task 9.

**Replace Step 1 with this restricted version** and keep the fuller assertions for Task 9:

Amend `RolesInstallTest.php` — keep only `testAllProjectRolesExist` in this task. Remove the other three methods. They will be re-added in Task 9 when the workflow permissions actually exist.

- [ ] **Step 4: Clean up RolesInstallTest to only check existence**

Replace the test file content with:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group lcdle_core
 */
final class RolesInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testAllProjectRolesExist(): void {
    foreach (['reader', 'contributor_new', 'contributor_trusted', 'editor'] as $rid) {
      $role = Role::load($rid);
      $this->assertNotNull($role, "Role {$rid} is installed.");
    }
  }

}
```

- [ ] **Step 5: Re-install the module for fresh config import**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Expected: both commands succeed.

- [ ] **Step 6: Run the test — expect pass**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=RolesInstallTest"`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add four project user roles via lcdle_core

reader, contributor_new, contributor_trusted, editor — each with the
permissions matching their editorial responsibility (spec §5.1). The
workflow-transition permissions reference article_workflow which will be
created in a later task; they remain inactive until then and become
effective automatically once the workflow is installed."
```

---

## Task 3 — Three taxonomy vocabularies

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/taxonomy.vocabulary.themes.yml`
- Create: `web/modules/custom/lcdle_core/config/install/taxonomy.vocabulary.chroniques.yml`
- Create: `web/modules/custom/lcdle_core/config/install/taxonomy.vocabulary.tags_legacy.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/VocabulariesInstallTest.php`

- [ ] **Step 1: Write the failing test**

Create `VocabulariesInstallTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * @group lcdle_core
 */
final class VocabulariesInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  /**
   * @dataProvider provideVocabularyIds
   */
  public function testVocabularyExists(string $vid): void {
    $vocabulary = Vocabulary::load($vid);
    $this->assertNotNull($vocabulary, "Vocabulary {$vid} is installed.");
  }

  public static function provideVocabularyIds(): array {
    return [
      'themes' => ['themes'],
      'chroniques' => ['chroniques'],
      'tags_legacy' => ['tags_legacy'],
    ];
  }

}
```

- [ ] **Step 2: Run the test — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=VocabulariesInstallTest"`
Expected: FAIL (3 failures, one per data row).

- [ ] **Step 3: Create vocabulary YAML files**

`taxonomy.vocabulary.themes.yml`:

```yaml
langcode: fr
status: true
dependencies: {  }
name: Thèmes
vid: themes
description: 'Catégories transversales (musique, cinéma, série, livres…). Gérés par les éditeurs.'
weight: 0
```

`taxonomy.vocabulary.chroniques.yml`:

```yaml
langcode: fr
status: true
dependencies: {  }
name: Chroniques
vid: chroniques
description: 'Séries récurrentes dans l''espace d''un contributeur (optionnel). Créables par les contributeurs.'
weight: 1
```

`taxonomy.vocabulary.tags_legacy.yml`:

```yaml
langcode: fr
status: true
dependencies: {  }
name: 'Tags (legacy WP)'
vid: tags_legacy
description: 'Quarantaine pour les tags importés depuis WordPress, en attente de triage manuel.'
weight: 2
```

- [ ] **Step 4: Re-install module and run tests**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=VocabulariesInstallTest"`
Expected: PASS (3 tests, 3 assertions).

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add three taxonomy vocabularies via lcdle_core

themes (transversal categories, editor-managed), chroniques (optional
per-contributor series), tags_legacy (WP import quarantine). Matches
spec §4 content model."
```

---

## Task 4 — Media type `image`

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/media.type.image.yml`
- Create: `web/modules/custom/lcdle_core/config/install/core.entity_form_display.media.image.default.yml`
- Create: `web/modules/custom/lcdle_core/config/install/core.entity_view_display.media.image.default.yml`

No dedicated test file — this is structural plumbing that Task 7 (field_cover_image) implicitly covers. A small assertion is added to the next task's test.

- [ ] **Step 1: Create the media image type**

`media.type.image.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - image
id: image
label: Image
description: 'Fichier image téléversé (cover, bannière, contenu illustré).'
source: image
source_configuration:
  source_field: field_media_image
field_map:
  name: name
new_revision: true
queue_thumbnail_downloads: false
```

- [ ] **Step 2: Create the source field storage + instance**

Create `web/modules/custom/lcdle_core/config/install/field.storage.media.field_media_image.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - file
    - image
    - media
id: media.field_media_image
field_name: field_media_image
entity_type: media
type: image
settings:
  target_type: file
  display_field: false
  display_default: false
  uri_scheme: public
  default_image:
    uuid: null
    alt: ''
    title: ''
    width: null
    height: null
module: image
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

Create `web/modules/custom/lcdle_core/config/install/field.field.media.image.field_media_image.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.media.field_media_image
    - media.type.image
  module:
    - image
id: media.image.field_media_image
field_name: field_media_image
entity_type: media
bundle: image
label: Image
description: ''
required: true
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  file_directory: '[date:custom:Y]-[date:custom:m]'
  file_extensions: 'png gif jpg jpeg webp avif'
  max_filesize: '20 MB'
  max_resolution: 4000x4000
  min_resolution: ''
  alt_field: true
  alt_field_required: true
  title_field: false
  title_field_required: false
  default_image:
    uuid: null
    alt: ''
    title: ''
    width: null
    height: null
  handler: 'default:file'
  handler_settings: {  }
field_type: image
```

- [ ] **Step 3: Create form + view displays for the media type**

`core.entity_form_display.media.image.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.media.image.field_media_image
    - media.type.image
  module:
    - image
id: media.image.default
targetEntityType: media
bundle: image
mode: default
content:
  created:
    type: datetime_timestamp
    weight: 10
    region: content
    settings: {  }
    third_party_settings: {  }
  field_media_image:
    type: image_image
    weight: 1
    region: content
    settings:
      progress_indicator: throbber
      preview_image_style: medium
    third_party_settings: {  }
  name:
    type: string_textfield
    weight: 0
    region: content
    settings:
      size: 60
      placeholder: ''
    third_party_settings: {  }
  status:
    type: boolean_checkbox
    weight: 100
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  uid:
    type: entity_reference_autocomplete
    weight: 5
    region: content
    settings:
      match_operator: CONTAINS
      match_limit: 10
      size: 60
      placeholder: ''
    third_party_settings: {  }
hidden: {  }
```

`core.entity_view_display.media.image.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.media.image.field_media_image
    - media.type.image
  module:
    - image
id: media.image.default
targetEntityType: media
bundle: image
mode: default
content:
  field_media_image:
    type: image
    label: hidden
    settings:
      image_style: large
      image_link: ''
      image_loading:
        attribute: lazy
    weight: 0
    region: content
    third_party_settings: {  }
hidden:
  created: true
  name: true
  thumbnail: true
  uid: true
```

- [ ] **Step 4: Re-install module and verify media type**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`

Run: `ddev drush ev "print_r(array_keys(\Drupal\media\Entity\MediaType::loadMultiple()));"`
Expected: output includes `image`.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add media type 'image' used by article cover images

Ships the media type with its source field (field_media_image),
form/view displays, and alt-text required — a11y baseline for every
cover image. Source scope limited to image MIME types including modern
formats (webp, avif) to match ADR-004 responsive image strategy."
```

---

## Task 5 — Article content type and body field

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/node.type.article.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.field.node.article.body.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/ArticleContentTypeTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * @group lcdle_core
 */
final class ArticleContentTypeTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testArticleNodeTypeExists(): void {
    $type = NodeType::load('article');
    $this->assertNotNull($type, 'Content type article exists.');
    $this->assertSame('Article', $type->label());
  }

  public function testArticleHasBodyField(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $this->assertArrayHasKey('body', $fields, 'article has body field.');
  }

}
```

- [ ] **Step 2: Run — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ArticleContentTypeTest"`
Expected: FAIL.

- [ ] **Step 3: Create node type and body field**

`node.type.article.yml`:

```yaml
langcode: fr
status: true
dependencies: {  }
third_party_settings: {  }
name: Article
type: article
description: 'Contenu éditorial principal — écrit par un contributeur, rattaché à 0..n thèmes et 0..1 chronique.'
help: ''
new_revision: true
preview_mode: 1
display_submitted: true
```

`field.field.node.article.body.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.node.body
    - node.type.article
  module:
    - text
id: node.article.body
field_name: body
entity_type: node
bundle: article
label: Corps
description: ''
required: false
translatable: true
default_value: {  }
default_value_callback: ''
settings:
  display_summary: false
  required_summary: false
  allowed_formats: {  }
field_type: text_with_summary
```

- [ ] **Step 4: Re-install and run test**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ArticleContentTypeTest"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add article content type with body field

Article is the primary editorial unit (spec §4). New revisions enabled
by default so Content Moderation (added later) gets the revision
lineage it requires. Body uses Drupal's standard text_with_summary field
storage shared with any future recipes that need it."
```

---

## Task 6 — Custom fields on article: themes, chronique, excerpt, cover_image

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/field.storage.node.field_themes.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.storage.node.field_chronique.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.storage.node.field_cover_image.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.storage.node.field_excerpt.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.field.node.article.field_themes.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.field.node.article.field_chronique.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.field.node.article.field_cover_image.yml`
- Create: `web/modules/custom/lcdle_core/config/install/field.field.node.article.field_excerpt.yml`
- Create: `web/modules/custom/lcdle_core/config/install/core.entity_form_display.node.article.default.yml`
- Create: `web/modules/custom/lcdle_core/config/install/core.entity_view_display.node.article.default.yml`
- Create: `web/modules/custom/lcdle_core/config/install/core.entity_view_display.node.article.teaser.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/ArticleFieldsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_core
 */
final class ArticleFieldsTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  /**
   * @dataProvider provideExpectedFields
   */
  public function testArticleHasField(string $fieldName, string $expectedType): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $this->assertArrayHasKey($fieldName, $fields, "article has field {$fieldName}.");
    $this->assertSame(
      $expectedType,
      $fields[$fieldName]->getType(),
      "article.{$fieldName} is type {$expectedType}.",
    );
  }

  public static function provideExpectedFields(): array {
    return [
      'field_themes (multi)' => ['field_themes', 'entity_reference'],
      'field_chronique (single)' => ['field_chronique', 'entity_reference'],
      'field_cover_image (media)' => ['field_cover_image', 'entity_reference'],
      'field_excerpt (text)' => ['field_excerpt', 'string_long'],
    ];
  }

  public function testFieldThemesIsMultiValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_themes']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(-1, $cardinality, 'field_themes is unlimited cardinality.');
  }

  public function testFieldChroniqueIsSingleValue(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('node', 'article');
    $cardinality = $fields['field_chronique']->getFieldStorageDefinition()->getCardinality();
    $this->assertSame(1, $cardinality, 'field_chronique is single value.');
  }

}
```

- [ ] **Step 2: Run — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ArticleFieldsTest"`
Expected: FAIL.

- [ ] **Step 3: Create field storage YAMLs**

`field.storage.node.field_themes.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - node
    - taxonomy
id: node.field_themes
field_name: field_themes
entity_type: node
type: entity_reference
settings:
  target_type: taxonomy_term
module: core
locked: false
cardinality: -1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.node.field_chronique.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - node
    - taxonomy
id: node.field_chronique
field_name: field_chronique
entity_type: node
type: entity_reference
settings:
  target_type: taxonomy_term
module: core
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.node.field_cover_image.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - media
    - node
id: node.field_cover_image
field_name: field_cover_image
entity_type: node
type: entity_reference
settings:
  target_type: media
module: core
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.node.field_excerpt.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - node
id: node.field_excerpt
field_name: field_excerpt
entity_type: node
type: string_long
settings:
  case_sensitive: false
module: core
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

- [ ] **Step 4: Create field instance YAMLs**

`field.field.node.article.field_themes.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.node.field_themes
    - node.type.article
    - taxonomy.vocabulary.themes
id: node.article.field_themes
field_name: field_themes
entity_type: node
bundle: article
label: Thèmes
description: 'Catégories transversales de l''article.'
required: true
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  handler: 'default:taxonomy_term'
  handler_settings:
    target_bundles:
      themes: themes
    sort:
      field: name
      direction: asc
    auto_create: false
    auto_create_bundle: ''
field_type: entity_reference
```

`field.field.node.article.field_chronique.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.node.field_chronique
    - node.type.article
    - taxonomy.vocabulary.chroniques
id: node.article.field_chronique
field_name: field_chronique
entity_type: node
bundle: article
label: Chronique
description: 'Série récurrente (optionnelle) dans laquelle s''inscrit cet article.'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  handler: 'default:taxonomy_term'
  handler_settings:
    target_bundles:
      chroniques: chroniques
    sort:
      field: name
      direction: asc
    auto_create: true
    auto_create_bundle: chroniques
field_type: entity_reference
```

`field.field.node.article.field_cover_image.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.node.field_cover_image
    - media.type.image
    - node.type.article
id: node.article.field_cover_image
field_name: field_cover_image
entity_type: node
bundle: article
label: 'Image de couverture'
description: 'Image affichée en tête d''article et dans les cards.'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  handler: 'default:media'
  handler_settings:
    target_bundles:
      image: image
    sort:
      field: _none
      direction: ASC
    auto_create: false
    auto_create_bundle: ''
field_type: entity_reference
```

`field.field.node.article.field_excerpt.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.node.field_excerpt
    - node.type.article
id: node.article.field_excerpt
field_name: field_excerpt
entity_type: node
bundle: article
label: Résumé
description: 'Texte court pour les cards, la newsletter et les métadonnées OpenGraph.'
required: false
translatable: true
default_value: {  }
default_value_callback: ''
settings:
  case_sensitive: false
field_type: string_long
```

- [ ] **Step 5: Create form and view displays for article**

`core.entity_form_display.node.article.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.node.article.body
    - field.field.node.article.field_chronique
    - field.field.node.article.field_cover_image
    - field.field.node.article.field_excerpt
    - field.field.node.article.field_themes
    - node.type.article
  module:
    - content_moderation
    - media_library
    - path
    - text
id: node.article.default
targetEntityType: node
bundle: article
mode: default
content:
  body:
    type: text_textarea_with_summary
    weight: 11
    region: content
    settings:
      rows: 20
      summary_rows: 5
      placeholder: ''
      show_summary: false
    third_party_settings: {  }
  created:
    type: datetime_timestamp
    weight: 120
    region: content
    settings: {  }
    third_party_settings: {  }
  field_chronique:
    type: entity_reference_autocomplete
    weight: 14
    region: content
    settings:
      match_operator: CONTAINS
      match_limit: 10
      size: 60
      placeholder: ''
    third_party_settings: {  }
  field_cover_image:
    type: media_library_widget
    weight: 12
    region: content
    settings:
      media_types: {  }
    third_party_settings: {  }
  field_excerpt:
    type: string_textarea
    weight: 10
    region: content
    settings:
      rows: 3
      placeholder: ''
    third_party_settings: {  }
  field_themes:
    type: entity_reference_autocomplete
    weight: 13
    region: content
    settings:
      match_operator: CONTAINS
      match_limit: 10
      size: 60
      placeholder: ''
    third_party_settings: {  }
  moderation_state:
    type: moderation_state_default
    weight: 100
    region: content
    settings: {  }
    third_party_settings: {  }
  path:
    type: path
    weight: 30
    region: content
    settings: {  }
    third_party_settings: {  }
  promote:
    type: boolean_checkbox
    weight: 15
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  sticky:
    type: boolean_checkbox
    weight: 16
    region: content
    settings:
      display_label: true
    third_party_settings: {  }
  title:
    type: string_textfield
    weight: 0
    region: content
    settings:
      size: 60
      placeholder: ''
    third_party_settings: {  }
  uid:
    type: entity_reference_autocomplete
    weight: 110
    region: content
    settings:
      match_operator: CONTAINS
      match_limit: 10
      size: 60
      placeholder: ''
    third_party_settings: {  }
hidden: {  }
```

`core.entity_view_display.node.article.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.node.article.body
    - field.field.node.article.field_chronique
    - field.field.node.article.field_cover_image
    - field.field.node.article.field_excerpt
    - field.field.node.article.field_themes
    - node.type.article
  module:
    - media
    - text
    - user
id: node.article.default
targetEntityType: node
bundle: article
mode: default
content:
  body:
    type: text_default
    label: hidden
    settings: {  }
    weight: 30
    region: content
    third_party_settings: {  }
  field_chronique:
    type: entity_reference_label
    label: inline
    settings:
      link: true
    weight: 22
    region: content
    third_party_settings: {  }
  field_cover_image:
    type: entity_reference_entity_view
    label: hidden
    settings:
      view_mode: default
      link: false
    weight: 10
    region: content
    third_party_settings: {  }
  field_excerpt:
    type: basic_string
    label: hidden
    settings: {  }
    weight: 20
    region: content
    third_party_settings: {  }
  field_themes:
    type: entity_reference_label
    label: inline
    settings:
      link: true
    weight: 21
    region: content
    third_party_settings: {  }
  links:
    settings: {  }
    third_party_settings: {  }
    weight: 100
    region: content
hidden: {  }
```

`core.entity_view_display.node.article.teaser.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - core.entity_view_mode.node.teaser
    - field.field.node.article.field_cover_image
    - field.field.node.article.field_excerpt
    - field.field.node.article.field_themes
    - node.type.article
  module:
    - media
    - user
id: node.article.teaser
targetEntityType: node
bundle: article
mode: teaser
content:
  field_cover_image:
    type: entity_reference_entity_view
    label: hidden
    settings:
      view_mode: default
      link: false
    weight: 10
    region: content
    third_party_settings: {  }
  field_excerpt:
    type: basic_string
    label: hidden
    settings: {  }
    weight: 20
    region: content
    third_party_settings: {  }
  field_themes:
    type: entity_reference_label
    label: inline
    settings:
      link: true
    weight: 21
    region: content
    third_party_settings: {  }
  links:
    settings: {  }
    third_party_settings: {  }
    weight: 100
    region: content
hidden:
  body: true
  field_chronique: true
```

- [ ] **Step 6: Re-install and run test**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ArticleFieldsTest"`
Expected: PASS (6 tests, 8 assertions — 4 from dataProvider + 2 cardinality checks).

- [ ] **Step 7: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add four custom fields on article: themes, chronique, cover, excerpt

Multi-value field_themes (required, transversal categories). Single
field_chronique (optional, creatable inline). field_cover_image
references the media 'image' bundle. field_excerpt is plain string_long
for cards, newsletter, and OpenGraph reuse.

Also ships the default form display (Media Library widget for cover,
autocomplete for taxonomy refs, Content Moderation widget visible) plus
default + teaser view displays matching spec §7.3 card layout."
```

---

## Task 7 — Content Moderation workflow `article_workflow`

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/workflows.workflow.article_workflow.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/WorkflowTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * @group lcdle_core
 */
final class WorkflowTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testArticleWorkflowExists(): void {
    $workflow = Workflow::load('article_workflow');
    $this->assertNotNull($workflow, 'article_workflow exists.');
  }

  public function testWorkflowHasExpectedStates(): void {
    $workflow = Workflow::load('article_workflow');
    $states = array_keys($workflow->getTypePlugin()->getStates());
    sort($states);
    $this->assertSame(
      ['archived', 'draft', 'needs_review', 'published'],
      $states,
      'article_workflow has 4 states.',
    );
  }

  public function testWorkflowHasExpectedTransitions(): void {
    $workflow = Workflow::load('article_workflow');
    $transitions = array_keys($workflow->getTypePlugin()->getTransitions());
    sort($transitions);
    $this->assertSame(
      ['archive', 'create_new_draft', 'publish', 'reject', 'restore_to_draft', 'submit_for_review'],
      $transitions,
      'article_workflow has 6 transitions.',
    );
  }

  public function testArticleBundleIsModerated(): void {
    $workflow = Workflow::load('article_workflow');
    $settings = $workflow->getTypePlugin()->getConfiguration();
    $this->assertArrayHasKey('node', $settings['entity_types']);
    $this->assertContains('article', $settings['entity_types']['node']);
  }

}
```

- [ ] **Step 2: Run — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=WorkflowTest"`
Expected: FAIL.

- [ ] **Step 3: Create the workflow YAML**

`workflows.workflow.article_workflow.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - node.type.article
  module:
    - content_moderation
id: article_workflow
label: 'Workflow Article'
type: content_moderation
type_settings:
  states:
    draft:
      label: Brouillon
      weight: 0
      published: false
      default_revision: false
    needs_review:
      label: 'En relecture'
      weight: 1
      published: false
      default_revision: false
    published:
      label: Publié
      weight: 2
      published: true
      default_revision: true
    archived:
      label: Archivé
      weight: 3
      published: false
      default_revision: true
  transitions:
    create_new_draft:
      label: 'Sauvegarder en brouillon'
      from:
        - draft
        - published
      to: draft
      weight: 0
    submit_for_review:
      label: 'Soumettre en relecture'
      from:
        - draft
      to: needs_review
      weight: 1
    publish:
      label: Publier
      from:
        - draft
        - needs_review
        - archived
      to: published
      weight: 2
    reject:
      label: Renvoyer en brouillon
      from:
        - needs_review
      to: draft
      weight: 3
    restore_to_draft:
      label: 'Dépublier (retour en brouillon)'
      from:
        - published
      to: draft
      weight: 4
    archive:
      label: Archiver
      from:
        - published
      to: archived
      weight: 5
  entity_types:
    node:
      - article
  default_moderation_state: draft
```

- [ ] **Step 4: Re-install and run the workflow test**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=WorkflowTest"`
Expected: PASS.

- [ ] **Step 5: Also verify roles test still passes**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=RolesInstallTest"`
Expected: PASS (role permissions referencing `article_workflow transition *` are now live).

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add article_workflow with 4 states and 6 transitions

draft → needs_review → published (+ archive + restore_to_draft). Matches
spec §5.2. The workflow is bound to the article bundle and contributes
per-transition permissions that roles (Task 2) already reference —
enabling this workflow also 'activates' those permissions on the
existing roles."
```

---

## Task 8 — Functional test: role-based workflow permissions

**Files:**
- Create: `web/modules/custom/lcdle_core/tests/src/Functional/WorkflowPermissionsTest.php`

This task is verification-only: no new config. It proves Tasks 2 and 7 interlock correctly under a real HTTP session.

- [ ] **Step 1: Write the functional test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

/**
 * @group lcdle_core
 */
final class WorkflowPermissionsTest extends BrowserTestBase {

  protected static $modules = ['lcdle_core'];

  protected $defaultTheme = 'stark';

  public function testContributorNewCannotPublishDirectly(): void {
    $user = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_new']]);
    $user->addRole('contributor_new');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextNotContains('Publier');
    $this->assertSession()->pageTextContains('Soumettre en relecture');
  }

  public function testContributorTrustedCanPublishDirectly(): void {
    $user = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_trusted']]);
    $user->addRole('contributor_trusted');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Publier');
  }

  public function testEditorCanModerateNeedsReview(): void {
    $editor = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['editor']]);
    $editor->addRole('editor');
    $editor->save();

    $contributor = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_new']]);
    $contributor->addRole('contributor_new');
    $contributor->save();

    $node = Node::create([
      'type' => 'article',
      'title' => 'Pending review',
      'uid' => $contributor->id(),
      'moderation_state' => 'needs_review',
    ]);
    $node->save();

    $this->drupalLogin($editor);
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Publier');
  }

}
```

- [ ] **Step 2: Add kernel + functional job to CI**

Modify `.github/workflows/ci.yml`. Append to the `jobs` map (preserve existing jobs):

```yaml
  kernel-tests:
    name: PHPUnit (kernel)
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: drupal
          POSTGRES_PASSWORD: drupal
          POSTGRES_DB: drupal
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          coverage: none
          tools: composer:v2
          extensions: pdo_pgsql, gd, intl, zip, mbstring
      - name: Install Composer deps
        run: composer install --no-interaction --prefer-dist --no-progress
      - name: Run kernel suite
        env:
          SIMPLETEST_DB: pgsql://drupal:drupal@localhost:5432/drupal
          SIMPLETEST_BASE_URL: http://localhost
        run: vendor/bin/phpunit --testsuite=kernel

  functional-tests:
    name: PHPUnit (functional)
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgres:16
        env:
          POSTGRES_USER: drupal
          POSTGRES_PASSWORD: drupal
          POSTGRES_DB: drupal
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.4"
          coverage: none
          tools: composer:v2
          extensions: pdo_pgsql, gd, intl, zip, mbstring
      - name: Install Composer deps
        run: composer install --no-interaction --prefer-dist --no-progress
      - name: Run PHP built-in server
        run: php -S 127.0.0.1:8080 -t web >/tmp/php-server.log 2>&1 &
      - name: Run functional suite
        env:
          SIMPLETEST_DB: pgsql://drupal:drupal@localhost:5432/drupal
          SIMPLETEST_BASE_URL: http://127.0.0.1:8080
          BROWSERTEST_OUTPUT_DIRECTORY: /tmp/browser_output
        run: |
          mkdir -p /tmp/browser_output
          vendor/bin/phpunit --testsuite=functional
```

- [ ] **Step 3: Run the functional suite locally**

Run: `ddev exec "mkdir -p /var/www/html/web/sites/simpletest/browser_output && vendor/bin/phpunit --testsuite=functional"`
Expected: PASS (3 tests).

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_core/ .github/workflows/ci.yml
git -c commit.gpgsign=false commit -m "Add functional tests for role-based workflow permissions

Three scenarios from spec §5: contributor_new cannot publish directly,
contributor_trusted can, editor can moderate needs_review submissions.
CI pipeline extended with kernel and functional jobs (PostgreSQL 16
service container, PHP built-in server for functional HTTP)."
```

---

## Task 9 — Pathauto pattern for article

**Files:**
- Create: `web/modules/custom/lcdle_core/config/install/pathauto.pattern.article.yml`
- Create: `web/modules/custom/lcdle_core/tests/src/Kernel/PathautoPatternTest.php`

Note: the final pattern (spec §6.1) includes the author slug — `[node:author:field_contributor_profile:slug]/[node:title]`. Since `field_contributor_profile` is not created until Phase 0C, this plan ships a **temporary** pattern `[node:title]` with a comment explaining the deferment. Plan 0C has a dedicated task to update this pattern once the profile field exists.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\pathauto\Entity\PathautoPattern;

/**
 * @group lcdle_core
 */
final class PathautoPatternTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testArticlePatternExists(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertNotNull($pattern, 'pathauto pattern article exists.');
  }

  public function testArticlePatternTargetsNodeArticle(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertSame('canonical_entities:node', $pattern->getType());
    $condition = $pattern->getSelectionCondition('entity_bundle:node');
    $configuration = $condition->getConfiguration();
    $this->assertContains('article', array_keys($configuration['bundles']));
  }

}
```

- [ ] **Step 2: Run — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=PathautoPatternTest"`
Expected: FAIL.

- [ ] **Step 3: Create the pattern YAML**

`pathauto.pattern.article.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - node.type.article
  module:
    - node
id: article
label: 'Article (placeholder — author slug added in Phase 0C)'
type: 'canonical_entities:node'
pattern: '[node:title]'
selection_criteria:
  f19c00f0-6a15-4c9d-b89e-cbbfd5f65d9f:
    id: 'entity_bundle:node'
    bundles:
      article: article
    negate: false
    context_mapping:
      node: node
    uuid: f19c00f0-6a15-4c9d-b89e-cbbfd5f65d9f
selection_logic: and
weight: 0
relationships: {  }
```

- [ ] **Step 4: Re-install and test**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=PathautoPatternTest"`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Add temporary Pathauto pattern for article

Ships a placeholder pattern [node:title] because the author slug token
[node:author:field_contributor_profile:slug] depends on Phase 0C. The
pattern label flags this explicitly so the next plan knows to swap it
rather than add a second one."
```

---

## Task 10 — Final verification and config export

- [ ] **Step 1: Uninstall and reinstall the module one last time**

Run: `ddev drush pm:uninstall lcdle_core -y && ddev drush pm:install lcdle_core -y`
Expected: both succeed.

- [ ] **Step 2: Export active config**

Run: `ddev drush config:export -y`
Expected: `config/sync/` updated with lcdle_core's derived config (core.extension.yml records the module, roles/vocabularies/content types/etc. are captured).

- [ ] **Step 3: Run the full test suite locally**

Run:
```bash
ddev exec "vendor/bin/phpcs --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse --memory-limit=1G"
ddev exec "vendor/bin/phpunit --testsuite=unit"
ddev exec "vendor/bin/phpunit --testsuite=kernel"
ddev exec "vendor/bin/phpunit --testsuite=functional"
```

Expected: all five commands exit 0. Kernel suite runs the 5 kernel test classes. Functional suite runs WorkflowPermissionsTest (3 scenarios).

- [ ] **Step 4: Smoke test via drush**

Run: `ddev drush ev "\$node = \Drupal\node\Entity\Node::create(['type' => 'article', 'title' => 'smoke', 'moderation_state' => 'draft']); \$node->save(); print \$node->id() . PHP_EOL;"`
Expected: prints a numeric node id (e.g., `1`).

Run: `ddev drush ev "print \Drupal\node\Entity\Node::load(1)->toUrl()->toString() . PHP_EOL;"`
Expected: prints `/smoke` (from the Pathauto pattern).

Clean up:

Run: `ddev drush ev "\Drupal\node\Entity\Node::load(1)->delete();"`
Expected: no output.

- [ ] **Step 5: Commit exported config**

```bash
git add config/sync/ web/modules/custom/lcdle_core/
git -c commit.gpgsign=false commit -m "Export active config after Phase 0B module install

Captures the content model in config/sync so the site can be rebuilt
from a clean install with drush site:install --existing-config. The
lcdle_core module entry appears in core.extension; content type, fields,
vocabularies, roles, workflow and pathauto pattern are all represented
in the exported YAML."
```

- [ ] **Step 6: Tag the baseline**

```bash
git tag -a phase-0b-complete -m "Phase 0B content model complete

lcdle_core module ships: 4 roles, 3 vocabularies, 1 content type with
5 fields, 1 media type, 1 Content Moderation workflow (4 states, 6
transitions), 1 temporary Pathauto pattern. All covered by 6 kernel
test classes + 1 functional test class (3 scenarios). CI extended with
kernel + functional jobs running against PostgreSQL 16.

Ready for Phase 0C (custom entities: ContributorProfile + NewsletterSubscriber)."
```

- [ ] **Step 7: Optional — push**

```bash
git push origin main
git push origin phase-0b-complete
```

Verify CI is green for all five jobs on the push.

---

## Self-Review (post-plan, before execution)

**Spec coverage:**

| Spec reference | Task |
|---|---|
| §4.1 User + ContributorProfile | Profile is **Plan 0C** — out of scope here, noted. |
| §4.1 Node: Article + fields | Tasks 5, 6 |
| §4.1 Taxonomy: themes / chroniques / tags_legacy | Task 3 |
| §4.1 NewsletterSubscriber | **Plan 0C** — out of scope, noted. |
| §5.1 Roles (reader, contributor_new, contributor_trusted, editor) | Task 2 |
| §5.2 Workflow (states + transitions + per-role permissions) | Tasks 2, 7, 8 |
| §5.5 Notifications (message_notify / ECA) | Deferred — notification wiring is a small custom service, appropriate for Plan 0C or later. |
| §6.1 URL scheme — article pattern | Task 9 (temporary, finalized in Plan 0C) |
| §11.2 Negative space (role cannot publish etc.) | Task 8 functional tests |

**Placeholder scan:** No `TBD`/`TODO` strings. Task 9 uses "temporary" explicitly with a forward reference to Plan 0C. Task 2 forward-references workflow permissions from Task 7 (documented).

**Type consistency:** machine names used consistently: `article`, `field_themes`, `field_chronique`, `field_cover_image`, `field_excerpt`, `themes`, `chroniques`, `tags_legacy`, `article_workflow`, `reader`, `contributor_new`, `contributor_trusted`, `editor`. All French labels use consistent diacritics and casing.
