---
id: PLAN-0C1
type: implementation-plan
status: draft
last-updated: 2026-04-15
spec: SPEC-001
phase: "0C-1 — Contributor Profile"
---

# Phase 0C-1 — Contributor Profile Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `lcdle_contributor` custom module that provisions a Profile type `contributor_profile` (via `drupal/profile` contrib), enforces a per-contributor URL `slug` (unique + blacklist-validated), exposes the public route `/{slug}` rendering the contributor's profile and published articles, and re-bases the article Pathauto pattern on the new slug.

**Architecture:** One custom module `lcdle_contributor` depending on `drupal/profile` (contrib) and `lcdle_core` (internal). Contains config-install YAMLs for the Profile type + 7 fields + 2 displays, one validation constraint plugin (`ContributorSlugBlacklist`) enforcing the reserved-slug rule, and one controller (`ContributorPageController`) resolving `/{slug}` → ProfileContributor → rendered page (profile body + published articles by that user). The article Pathauto pattern is updated in the same module's `config/install/` so its install re-imports the pattern (replacing the temporary one from Plan 0B).

**Tech Stack:** Drupal 11, PHP 8.4, `drupal/profile` ^1.13, `drupal/pathauto` (already installed), Symfony Validator (for the constraint), Views (for articles listing rendered in the controller).

---

## Scope Check

Plan 0C-1 covers Contributor Profile only. The NewsletterSubscriber entity + double opt-in are in a separate plan (**0C-2**) because they share no code with this plan and deliver a separate, testable subsystem.

---

## File Structure

### Created

```
web/modules/custom/lcdle_contributor/
├── lcdle_contributor.info.yml
├── lcdle_contributor.routing.yml
├── lcdle_contributor.services.yml
├── src/
│   ├── Controller/
│   │   └── ContributorPageController.php
│   ├── ParamConverter/
│   │   └── ContributorSlugConverter.php
│   └── Plugin/
│       └── Validation/
│           └── Constraint/
│               ├── ContributorSlugBlacklist.php
│               └── ContributorSlugBlacklistValidator.php
├── config/
│   └── install/
│       ├── profile.type.contributor_profile.yml
│       ├── field.storage.profile.field_slug.yml
│       ├── field.storage.profile.field_bio.yml
│       ├── field.storage.profile.field_avatar.yml
│       ├── field.storage.profile.field_banner.yml
│       ├── field.storage.profile.field_social_links.yml
│       ├── field.storage.profile.field_accent_color.yml
│       ├── field.storage.profile.field_display_name.yml
│       ├── field.field.profile.contributor_profile.field_slug.yml
│       ├── field.field.profile.contributor_profile.field_bio.yml
│       ├── field.field.profile.contributor_profile.field_avatar.yml
│       ├── field.field.profile.contributor_profile.field_banner.yml
│       ├── field.field.profile.contributor_profile.field_social_links.yml
│       ├── field.field.profile.contributor_profile.field_accent_color.yml
│       ├── field.field.profile.contributor_profile.field_display_name.yml
│       ├── core.entity_form_display.profile.contributor_profile.default.yml
│       └── core.entity_view_display.profile.contributor_profile.default.yml
├── config/
│   └── optional/
│       └── pathauto.pattern.article.yml      # overrides lcdle_core's temporary pattern
└── tests/
    └── src/
        ├── Kernel/
        │   ├── LcdleContributorKernelTestBase.php
        │   ├── ProfileTypeInstallTest.php
        │   ├── SlugConstraintTest.php
        │   └── PathautoPatternUpdatedTest.php
        └── Functional/
            └── ContributorPageTest.php
```

### Modified

- `composer.json` + `composer.lock` — require `drupal/profile:^1.13`.
- `web/modules/custom/lcdle_core/config/install/pathauto.pattern.article.yml` — **not** modified here; the new pattern is shipped under `lcdle_contributor/config/install/` and overrides it at install time. (See Task 8 for ordering.)

### Files existing

- `docs/superpowers/specs/2026-04-14-lcdle-migration-design.md` — the spec.
- `docs/superpowers/plans/2026-04-15-phase-0c1-contributor-profile.md` — this plan.
- `web/modules/custom/lcdle_core/` — Plan 0B module.

---

## Conventions

- **TDD** strict: Red → Green → Commit.
- **Machine names English**, UI labels French.
- **Kernel tests extend** a shared `LcdleContributorKernelTestBase` (introduced in Task 2) so future tasks don't drift on `$modules` arrays — lesson learned from Plan 0B.
- **Commits orientés "pourquoi"**, no Conventional Commits prefix, one commit per task.
- **Inside DDEV**: `ddev drush`, `ddev exec "vendor/bin/phpunit ..."`.
- **Terms strictement alignés sur `docs/domain/glossary.md`** : « Contributeur », « Profil contributeur », « Slug », « Espace contributeur ».

---

## Task 1 — Module skeleton + composer require drupal/profile + install test

**Files:**
- Modify: `composer.json`, `composer.lock`
- Create: `web/modules/custom/lcdle_contributor/lcdle_contributor.info.yml`
- Create: `web/modules/custom/lcdle_contributor/tests/src/Kernel/ModuleInstallTest.php`

- [ ] **Step 1: Add `drupal/profile` via composer**

Run: `ddev composer require drupal/profile:^1.13 -W --no-interaction`
Expected: package downloaded to `web/modules/contrib/profile/`.

- [ ] **Step 2: Write the failing kernel test**

Create `web/modules/custom/lcdle_contributor/tests/src/Kernel/ModuleInstallTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_contributor
 */
final class ModuleInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_contributor'];

  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_contributor'),
      'lcdle_contributor module is enabled.',
    );
  }

}
```

- [ ] **Step 3: Run — expect failure**

Run: `ddev exec "vendor/bin/phpunit --testsuite=kernel --filter=ModuleInstallTest.*lcdle_contributor"`
Expected: FAIL ("Unable to find the module lcdle_contributor").

Note: the existing `ModuleInstallTest` in `lcdle_core` has the same class name — we filter by file path or FQCN.
Simpler: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ModuleInstallTest.php"`

- [ ] **Step 4: Create `lcdle_contributor.info.yml`**

```yaml
name: 'LCDLE Contributor'
type: module
description: 'Contributor profiles (Profile type + slug + /{slug} route) for La Culture de l''Écran.'
package: 'LCDLE'
core_version_requirement: ^11
dependencies:
  - drupal:user
  - drupal:node
  - drupal:image
  - drupal:file
  - drupal:link
  - drupal:path_alias
  - lcdle_core:lcdle_core
  - profile:profile
  - pathauto:pathauto
```

- [ ] **Step 5: Run — expect pass**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ModuleInstallTest.php"`
Expected: PASS.

- [ ] **Step 6: Verify drush install**

Run: `ddev drush pm:install lcdle_contributor -y`
Expected: `[success] Successfully enabled: lcdle_contributor` (and transitive install of `profile` if not already).

Run: `ddev drush pm:list --status=enabled --format=list | grep -E '^(profile|lcdle_contributor)$'`
Expected: both print.

- [ ] **Step 7: Commit**

```bash
git add composer.json composer.lock web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Scaffold lcdle_contributor module with install test

Adds drupal/profile as a composer dep and ships the minimum info.yml
required to install a child module that depends on profile + lcdle_core.
First kernel test establishes the TDD baseline for the contributor
profile sub-project."
```

---

## Task 2 — Shared kernel test base class

**Files:**
- Create: `web/modules/custom/lcdle_contributor/tests/src/Kernel/LcdleContributorKernelTestBase.php`

**Rationale:** Plan 0B's kernel tests drifted on `$modules` arrays — each new task had to amend existing tests. Introducing a shared base here prevents that drift for Plan 0C-1 and teaches the pattern for 0C-2 and beyond.

- [ ] **Step 1: Create the abstract base class**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Shared bootstrap for lcdle_contributor kernel tests.
 *
 * Central place to list the modules and config imports every kernel test in
 * this module needs. New dependencies are added here once, not in each test.
 */
abstract class LcdleContributorKernelTestBase extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'text',
    'field',
    'taxonomy',
    'file',
    'image',
    'link',
    'media',
    'media_library',
    'views',
    'path',
    'path_alias',
    'token',
    'pathauto',
    'workflows',
    'content_moderation',
    'profile',
    'lcdle_core',
    'lcdle_contributor',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('profile');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'system',
      'field',
      'filter',
      'node',
      'user',
      'profile',
      'lcdle_core',
      'lcdle_contributor',
    ]);
  }

}
```

- [ ] **Step 2: Refactor `ModuleInstallTest` to extend the base**

Replace the class body with:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

/**
 * @group lcdle_contributor
 */
final class ModuleInstallTest extends LcdleContributorKernelTestBase {

  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_contributor'),
      'lcdle_contributor module is enabled.',
    );
  }

}
```

- [ ] **Step 3: Run to verify nothing broke**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/"`
Expected: 1 test, 1 assertion, PASS.

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_contributor/tests/
git -c commit.gpgsign=false commit -m "Add shared kernel test base for lcdle_contributor

Centralises the \$modules list and installConfig calls every kernel
test in this module needs, so future tasks don't have to amend every
existing test when a dependency is added. Retrospective cleanup applied
to ModuleInstallTest as the first consumer."
```

---

## Task 3 — Profile type `contributor_profile` (no fields yet)

**Files:**
- Create: `web/modules/custom/lcdle_contributor/config/install/profile.type.contributor_profile.yml`
- Create: `web/modules/custom/lcdle_contributor/tests/src/Kernel/ProfileTypeInstallTest.php`

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\ProfileType;

/**
 * @group lcdle_contributor
 */
final class ProfileTypeInstallTest extends LcdleContributorKernelTestBase {

  public function testContributorProfileTypeExists(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertNotNull($type, 'Profile type contributor_profile exists.');
    $this->assertSame('Profil contributeur', $type->label());
  }

  public function testContributorProfileTypeIsMultiple(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertFalse(
      $type->allowsMultiple(),
      'contributor_profile allows only one profile per user.',
    );
  }

  public function testContributorProfileTypeIsRoleBound(): void {
    $type = ProfileType::load('contributor_profile');
    $roles = $type->getRoles();
    sort($roles);
    $this->assertSame(
      ['contributor_new', 'contributor_trusted', 'editor'],
      $roles,
      'contributor_profile is bound to the three editorial roles.',
    );
  }

}
```

- [ ] **Step 2: Run — expect fail**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ProfileTypeInstallTest.php"`
Expected: FAIL.

- [ ] **Step 3: Create profile type YAML**

`profile.type.contributor_profile.yml`:

```yaml
langcode: fr
status: true
dependencies:
  enforced:
    module:
      - lcdle_contributor
  module:
    - lcdle_core
    - user
id: contributor_profile
label: 'Profil contributeur'
description: 'Identité publique d''un contributeur : nom affiché, slug d''URL, bio, avatar, bannière, liens sociaux.'
multiple: false
registration: false
roles:
  - contributor_new
  - contributor_trusted
  - editor
```

- [ ] **Step 4: Re-install lcdle_contributor and run test**

Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ProfileTypeInstallTest.php"`
Expected: PASS (3 tests).

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Add profile type contributor_profile

Single profile per user (multiple: false) — a contributor has exactly
one editorial identity. Scoped to the three editorial roles
(contributor_new / contributor_trusted / editor): readers don't have a
profile, nor do admins qua admins."
```

---

## Task 4 — `field_slug` with UniqueField core constraint

**Files:**
- Create: `web/modules/custom/lcdle_contributor/config/install/field.storage.profile.field_slug.yml`
- Create: `web/modules/custom/lcdle_contributor/config/install/field.field.profile.contributor_profile.field_slug.yml`
- Create: `web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php`

- [ ] **Step 1: Write failing test (existence + uniqueness only — blacklist is Task 5)**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\Profile;
use Drupal\user\Entity\User;

/**
 * @group lcdle_contributor
 */
final class SlugConstraintTest extends LcdleContributorKernelTestBase {

  public function testFieldSlugExists(): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('profile', 'contributor_profile');
    $this->assertArrayHasKey('field_slug', $fields);
    $this->assertTrue($fields['field_slug']->isRequired());
  }

  public function testSlugMustBeUnique(): void {
    $user_a = User::create(['name' => 'alice', 'mail' => 'alice@example.test', 'status' => 1]);
    $user_a->save();
    $user_b = User::create(['name' => 'bob', 'mail' => 'bob@example.test', 'status' => 1]);
    $user_b->save();

    $profile_a = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_a->id(),
      'field_slug' => 'alice',
    ]);
    $violations_a = $profile_a->validate();
    $this->assertCount(0, $violations_a, 'First alice slug is valid.');
    $profile_a->save();

    $profile_b = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user_b->id(),
      'field_slug' => 'alice',
    ]);
    $violations_b = $profile_b->validate();
    $this->assertGreaterThan(0, $violations_b->count(), 'Duplicate slug is rejected.');
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php"`
Expected: FAIL.

- [ ] **Step 3: Create storage + instance YAML**

`field.storage.profile.field_slug.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - profile
id: profile.field_slug
field_name: field_slug
entity_type: profile
type: string
settings:
  max_length: 60
  is_ascii: true
  case_sensitive: false
module: core
locked: false
cardinality: 1
translatable: false
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.field.profile.contributor_profile.field_slug.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_slug
    - profile.type.contributor_profile
id: profile.contributor_profile.field_slug
field_name: field_slug
entity_type: profile
bundle: contributor_profile
label: Slug
description: 'Identifiant d''URL unique — /{slug}. Lettres minuscules, chiffres et tirets uniquement. Réservé à certains mots (voir blacklist).'
required: true
translatable: false
default_value: {  }
default_value_callback: ''
settings: {  }
field_type: string
constraints:
  UniqueField: {  }
  Regex:
    pattern: '/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/'
    message: 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets, et doit commencer et finir par un caractère alphanumérique (3 à 60 caractères).'
```

- [ ] **Step 4: Re-install and run test**

Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php"`
Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Add field_slug with uniqueness + format constraints

Core UniqueField constraint prevents two contributors from claiming the
same slug. Regex restricts to the ASCII URL-safe alphabet required by
Pathauto. Blacklist enforcement comes in the next task (custom
ContributorSlugBlacklist constraint)."
```

---

## Task 5 — Custom constraint `ContributorSlugBlacklist`

**Files:**
- Create: `web/modules/custom/lcdle_contributor/src/Plugin/Validation/Constraint/ContributorSlugBlacklist.php`
- Create: `web/modules/custom/lcdle_contributor/src/Plugin/Validation/Constraint/ContributorSlugBlacklistValidator.php`
- Modify: `web/modules/custom/lcdle_contributor/config/install/field.field.profile.contributor_profile.field_slug.yml` (add the new constraint)
- Modify: `web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php` (add blacklist assertions)

- [ ] **Step 1: Extend the kernel test with a blacklist scenario**

Append to `SlugConstraintTest`:

```php
  /**
   * @dataProvider provideBlacklistedSlugs
   */
  public function testSlugBlacklistRejectsReserved(string $slug): void {
    $user = User::create(['name' => 'eve_' . $slug, 'mail' => $slug . '@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => $slug,
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(
      0,
      $violations->count(),
      "Reserved slug '{$slug}' must be rejected.",
    );
  }

  public static function provideBlacklistedSlugs(): array {
    return [
      ['admin'],
      ['user'],
      ['node'],
      ['api'],
      ['theme'],
      ['newsletter'],
      ['contribute'],
      ['feed'],
      ['rss'],
      ['login'],
    ];
  }

  public function testShortSlugIsRejected(): void {
    $user = User::create(['name' => 'shortie', 'mail' => 'shortie@example.test', 'status' => 1]);
    $user->save();
    $profile = Profile::create([
      'type' => 'contributor_profile',
      'uid' => $user->id(),
      'field_slug' => 'a',
    ]);
    $violations = $profile->validate();
    $this->assertGreaterThan(0, $violations->count(), 'Too-short slug rejected.');
  }
```

- [ ] **Step 2: Run — expect the blacklist tests to FAIL, short-slug test to PASS (regex handles it)**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php"`
Expected: 10 of 13 assertions pass (reserved slugs still accepted because no blacklist yet); the 10 blacklist data rows FAIL.

- [ ] **Step 3: Create the constraint plugin**

`src/Plugin/Validation/Constraint/ContributorSlugBlacklist.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Rejects reserved URL slugs that would clash with framework or system routes.
 */
#[Constraint(
  id: 'ContributorSlugBlacklist',
  label: 'Contributor slug blacklist',
  type: 'string',
)]
final class ContributorSlugBlacklist extends Constraint {

  public string $message = "Le slug « @slug » est réservé et ne peut pas être utilisé.";

  /**
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
```

`src/Plugin/Validation/Constraint/ContributorSlugBlacklistValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class ContributorSlugBlacklistValidator extends ConstraintValidator {

  public function validate(mixed $value, Constraint $constraint): void {
    if (!$constraint instanceof ContributorSlugBlacklist) {
      return;
    }

    $slug = $this->extractSlug($value);
    if ($slug === NULL) {
      return;
    }

    if (in_array($slug, ContributorSlugBlacklist::RESERVED, TRUE)) {
      $this->context->addViolation($constraint->message, ['@slug' => $slug]);
    }
  }

  private function extractSlug(mixed $value): ?string {
    if (is_string($value)) {
      return strtolower($value);
    }
    if (is_object($value) && method_exists($value, 'getValue')) {
      $items = $value->getValue();
      if (!empty($items[0]['value']) && is_string($items[0]['value'])) {
        return strtolower($items[0]['value']);
      }
    }
    return NULL;
  }

}
```

- [ ] **Step 4: Reference the constraint in the slug field YAML**

Modify `field.field.profile.contributor_profile.field_slug.yml` — change the `constraints:` block to:

```yaml
constraints:
  UniqueField: {  }
  Regex:
    pattern: '/^[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$/'
    message: 'Le slug ne peut contenir que des lettres minuscules, des chiffres et des tirets, et doit commencer et finir par un caractère alphanumérique (3 à 60 caractères).'
  ContributorSlugBlacklist: {  }
```

- [ ] **Step 5: Clear caches and run tests**

Run: `ddev drush cr`
Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/SlugConstraintTest.php"`
Expected: all blacklist scenarios PASS.

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Reject reserved slugs via ContributorSlugBlacklist constraint

A contributor trying to claim a slug that would clash with framework
routes (admin, node, api…) or product routes (newsletter, contribute…)
gets a validation error at save. The list lives in RESERVED and is
deliberately short — extend only with deliberate product decisions, not
speculative future routes (YAGNI)."
```

---

## Task 6 — Remaining profile fields (bio, avatar, banner, social_links, accent_color, display_name)

**Files:**
- Create storage YAMLs: `field.storage.profile.{field_bio,field_avatar,field_banner,field_social_links,field_accent_color,field_display_name}.yml`
- Create instance YAMLs: `field.field.profile.contributor_profile.{field_bio,field_avatar,field_banner,field_social_links,field_accent_color,field_display_name}.yml`

- [ ] **Step 1: Write failing test extending existing test file**

Append to `ProfileTypeInstallTest.php`:

```php
  /**
   * @dataProvider provideExpectedFields
   */
  public function testContributorProfileHasField(string $fieldName, string $expectedType, int $expectedCardinality): void {
    $fields = \Drupal::service('entity_field.manager')->getFieldDefinitions('profile', 'contributor_profile');
    $this->assertArrayHasKey($fieldName, $fields);
    $this->assertSame($expectedType, $fields[$fieldName]->getType());
    $this->assertSame(
      $expectedCardinality,
      $fields[$fieldName]->getFieldStorageDefinition()->getCardinality(),
    );
  }

  public static function provideExpectedFields(): array {
    return [
      'field_display_name' => ['field_display_name', 'string', 1],
      'field_bio' => ['field_bio', 'text_long', 1],
      'field_avatar' => ['field_avatar', 'image', 1],
      'field_banner' => ['field_banner', 'image', 1],
      'field_social_links' => ['field_social_links', 'link', -1],
      'field_accent_color' => ['field_accent_color', 'string', 1],
    ];
  }
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ProfileTypeInstallTest.php"`
Expected: 6 failing data rows.

- [ ] **Step 3: Create storage YAMLs**

`field.storage.profile.field_display_name.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - profile
id: profile.field_display_name
field_name: field_display_name
entity_type: profile
type: string
settings:
  max_length: 120
  is_ascii: false
  case_sensitive: false
module: core
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.profile.field_bio.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - profile
    - text
id: profile.field_bio
field_name: field_bio
entity_type: profile
type: text_long
settings: {  }
module: text
locked: false
cardinality: 1
translatable: true
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.profile.field_avatar.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - file
    - image
    - profile
id: profile.field_avatar
field_name: field_avatar
entity_type: profile
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

`field.storage.profile.field_banner.yml`: identical to `field_avatar` but `id: profile.field_banner`, `field_name: field_banner`.

`field.storage.profile.field_social_links.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - link
    - profile
id: profile.field_social_links
field_name: field_social_links
entity_type: profile
type: link
settings: {  }
module: link
locked: false
cardinality: -1
translatable: false
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

`field.storage.profile.field_accent_color.yml`:

```yaml
langcode: fr
status: true
dependencies:
  module:
    - profile
id: profile.field_accent_color
field_name: field_accent_color
entity_type: profile
type: string
settings:
  max_length: 7
  is_ascii: true
  case_sensitive: false
module: core
locked: false
cardinality: 1
translatable: false
indexes: {  }
persist_with_no_fields: false
custom_storage: false
```

- [ ] **Step 4: Create instance YAMLs**

`field.field.profile.contributor_profile.field_display_name.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_display_name
    - profile.type.contributor_profile
id: profile.contributor_profile.field_display_name
field_name: field_display_name
entity_type: profile
bundle: contributor_profile
label: 'Nom affiché'
description: 'Nom public affiché sur l''espace contributeur et les cards d''articles.'
required: true
translatable: true
default_value: {  }
default_value_callback: ''
settings: {  }
field_type: string
```

`field.field.profile.contributor_profile.field_bio.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_bio
    - profile.type.contributor_profile
  module:
    - text
id: profile.contributor_profile.field_bio
field_name: field_bio
entity_type: profile
bundle: contributor_profile
label: Bio
description: 'Présentation publique du contributeur (plusieurs paragraphes possibles).'
required: false
translatable: true
default_value: {  }
default_value_callback: ''
settings:
  allowed_formats: {  }
field_type: text_long
```

`field.field.profile.contributor_profile.field_avatar.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_avatar
    - profile.type.contributor_profile
  module:
    - image
id: profile.contributor_profile.field_avatar
field_name: field_avatar
entity_type: profile
bundle: contributor_profile
label: Avatar
description: 'Photo de profil carrée.'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  file_directory: 'contributors/avatars'
  file_extensions: 'png jpg jpeg webp avif'
  max_filesize: '5 MB'
  max_resolution: 2000x2000
  min_resolution: 200x200
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

`field.field.profile.contributor_profile.field_banner.yml`: identical body, with `id: profile.contributor_profile.field_banner`, `field_name: field_banner`, `label: Bannière`, `description: 'Image de bannière en tête de l''espace contributeur.'`, `file_directory: 'contributors/banners'`, `max_resolution: 3200x1200`, `min_resolution: 1200x400`.

`field.field.profile.contributor_profile.field_social_links.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_social_links
    - profile.type.contributor_profile
  module:
    - link
id: profile.contributor_profile.field_social_links
field_name: field_social_links
entity_type: profile
bundle: contributor_profile
label: 'Liens sociaux'
description: 'Mastodon, site personnel, et autres liens externes affichés publiquement.'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings:
  title: 1
  link_type: 16
field_type: link
```

(`link_type: 16` = LinkItemInterface::LINK_EXTERNAL. `title: 1` = optional title.)

`field.field.profile.contributor_profile.field_accent_color.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.storage.profile.field_accent_color
    - profile.type.contributor_profile
id: profile.contributor_profile.field_accent_color
field_name: field_accent_color
entity_type: profile
bundle: contributor_profile
label: 'Couleur d''accent'
description: 'Format hexadécimal #RRGGBB. Préparé pour la personnalisation visuelle légère (phase 5+) — ignoré au MVP.'
required: false
translatable: false
default_value: {  }
default_value_callback: ''
settings: {  }
field_type: string
constraints:
  Regex:
    pattern: '/^#[0-9a-fA-F]{6}$/'
    message: 'La couleur d''accent doit être au format #RRGGBB.'
```

- [ ] **Step 5: Re-install and run tests**

Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/ProfileTypeInstallTest.php"`
Expected: 9 tests pass (3 existing + 6 data rows).

- [ ] **Step 6: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Add contributor profile fields: display_name, bio, avatar, banner, social_links, accent_color

Seven fields total including field_slug from Task 4. display_name is
required (public identity must be explicit). Avatar/banner restricted to
modern image formats (webp/avif) with alt required for a11y. Social
links are external-only, unlimited cardinality. Accent color is hex-hash
format, phase 5+ use — shipped dormant."
```

---

## Task 7 — Form & view displays for `contributor_profile`

**Files:**
- Create: `web/modules/custom/lcdle_contributor/config/install/core.entity_form_display.profile.contributor_profile.default.yml`
- Create: `web/modules/custom/lcdle_contributor/config/install/core.entity_view_display.profile.contributor_profile.default.yml`

No new test; Task 9 (functional test on the /{slug} route) exercises the view display end-to-end.

- [ ] **Step 1: Create the form display**

`core.entity_form_display.profile.contributor_profile.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.profile.contributor_profile.field_accent_color
    - field.field.profile.contributor_profile.field_avatar
    - field.field.profile.contributor_profile.field_banner
    - field.field.profile.contributor_profile.field_bio
    - field.field.profile.contributor_profile.field_display_name
    - field.field.profile.contributor_profile.field_slug
    - field.field.profile.contributor_profile.field_social_links
    - profile.type.contributor_profile
  module:
    - image
    - link
    - text
id: profile.contributor_profile.default
targetEntityType: profile
bundle: contributor_profile
mode: default
content:
  field_display_name:
    type: string_textfield
    weight: 0
    region: content
    settings:
      size: 60
      placeholder: ''
    third_party_settings: {  }
  field_slug:
    type: string_textfield
    weight: 1
    region: content
    settings:
      size: 60
      placeholder: 'alice'
    third_party_settings: {  }
  field_avatar:
    type: image_image
    weight: 2
    region: content
    settings:
      progress_indicator: throbber
      preview_image_style: thumbnail
    third_party_settings: {  }
  field_banner:
    type: image_image
    weight: 3
    region: content
    settings:
      progress_indicator: throbber
      preview_image_style: medium
    third_party_settings: {  }
  field_bio:
    type: text_textarea
    weight: 4
    region: content
    settings:
      rows: 8
      placeholder: ''
    third_party_settings: {  }
  field_social_links:
    type: link_default
    weight: 5
    region: content
    settings:
      placeholder_url: 'https://mastodon.example/@alice'
      placeholder_title: 'Mastodon'
    third_party_settings: {  }
  field_accent_color:
    type: string_textfield
    weight: 6
    region: content
    settings:
      size: 8
      placeholder: '#3B82F6'
    third_party_settings: {  }
hidden:
  created: true
  uid: true
```

- [ ] **Step 2: Create the view display**

`core.entity_view_display.profile.contributor_profile.default.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.profile.contributor_profile.field_avatar
    - field.field.profile.contributor_profile.field_banner
    - field.field.profile.contributor_profile.field_bio
    - field.field.profile.contributor_profile.field_display_name
    - field.field.profile.contributor_profile.field_social_links
    - profile.type.contributor_profile
  module:
    - image
    - link
    - text
id: profile.contributor_profile.default
targetEntityType: profile
bundle: contributor_profile
mode: default
content:
  field_banner:
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
  field_avatar:
    type: image
    label: hidden
    settings:
      image_style: medium
      image_link: ''
      image_loading:
        attribute: lazy
    weight: 1
    region: content
    third_party_settings: {  }
  field_display_name:
    type: string
    label: hidden
    settings:
      link_to_entity: false
    weight: 2
    region: content
    third_party_settings: {  }
  field_bio:
    type: text_default
    label: hidden
    settings: {  }
    weight: 3
    region: content
    third_party_settings: {  }
  field_social_links:
    type: link
    label: 'visually_hidden'
    settings:
      trim_length: null
      url_only: false
      url_plain: false
      rel: 'me'
      target: _blank
    weight: 4
    region: content
    third_party_settings: {  }
hidden:
  field_accent_color: true
  field_slug: true
```

Note: `rel: 'me'` on social links — IndieWeb-friendly rel=me for identity verification when a h-card is present (cf. spec §9.1).

- [ ] **Step 3: Re-install, verify displays exist**

Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev drush ev "print_r(array_keys(\Drupal\Core\Entity\Entity\EntityFormDisplay::loadMultiple()));"` — verify contains `profile.contributor_profile.default`.

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Add form and view displays for contributor_profile

Form display orders fields by editorial priority (display_name → slug →
identity visuals → bio → social → accent). View display puts banner +
avatar above the fold and adds rel=\"me\" to social links so future
IndieWeb verification can piggy-back on the existing markup. Slug and
accent color are hidden from the default view — they're metadata, not
content."
```

---

## Task 8 — Route `/{slug}` + controller + ParamConverter

**Files:**
- Create: `web/modules/custom/lcdle_contributor/lcdle_contributor.routing.yml`
- Create: `web/modules/custom/lcdle_contributor/src/Controller/ContributorPageController.php`
- Create: `web/modules/custom/lcdle_contributor/src/ParamConverter/ContributorSlugConverter.php`
- Create: `web/modules/custom/lcdle_contributor/lcdle_contributor.services.yml`
- Create: `web/modules/custom/lcdle_contributor/tests/src/Functional/ContributorPageTest.php`

- [ ] **Step 1: Write the failing functional test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\profile\Entity\Profile;

/**
 * @group lcdle_contributor
 */
final class ContributorPageTest extends BrowserTestBase {

  protected static $modules = ['lcdle_contributor'];

  protected $defaultTheme = 'stark';

  public function testContributorPageRendersForKnownSlug(): void {
    $alice = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_trusted']]);
    $alice->addRole('contributor_trusted');
    $alice->save();

    Profile::create([
      'type' => 'contributor_profile',
      'uid' => $alice->id(),
      'field_slug' => 'alice',
      'field_display_name' => 'Alice Contributrice',
      'field_bio' => ['value' => 'Bio courte.', 'format' => 'basic_html'],
    ])->save();

    $this->drupalGet('/alice');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Alice Contributrice');
    $this->assertSession()->pageTextContains('Bio courte.');
  }

  public function testUnknownSlugReturns404(): void {
    $this->drupalGet('/does-not-exist');
    $this->assertSession()->statusCodeEquals(404);
  }

  public function testReservedSlugIsNotMatchedByRoute(): void {
    // /admin must keep its system route, not be captured by /{slug}.
    $admin = $this->drupalCreateUser(['access administration pages']);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('Administration');
  }

  public function testContributorPageListsPublishedArticles(): void {
    $alice = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_trusted']]);
    $alice->addRole('contributor_trusted');
    $alice->save();

    Profile::create([
      'type' => 'contributor_profile',
      'uid' => $alice->id(),
      'field_slug' => 'alice',
      'field_display_name' => 'Alice',
    ])->save();

    Node::create([
      'type' => 'article',
      'title' => 'Premier article publié',
      'uid' => $alice->id(),
      'moderation_state' => 'published',
    ])->save();

    Node::create([
      'type' => 'article',
      'title' => 'Brouillon privé',
      'uid' => $alice->id(),
      'moderation_state' => 'draft',
    ])->save();

    $this->drupalGet('/alice');
    $this->assertSession()->pageTextContains('Premier article publié');
    $this->assertSession()->pageTextNotContains('Brouillon privé');
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Functional/ContributorPageTest.php"`
Expected: all 4 tests fail (route not defined).

- [ ] **Step 3: Create the routing**

`lcdle_contributor.routing.yml`:

```yaml
lcdle_contributor.contributor_page:
  path: '/{contributor_slug}'
  defaults:
    _controller: '\Drupal\lcdle_contributor\Controller\ContributorPageController::view'
    _title_callback: '\Drupal\lcdle_contributor\Controller\ContributorPageController::title'
  requirements:
    _access: 'TRUE'
    contributor_slug: '^(?!admin|user|users|node|taxonomy|api|jsonapi|theme|chronique|newsletter|contribute|about|rss|feed|sitemap|robots|login|search|media|files|system|contact)[a-z0-9][a-z0-9-]{1,58}[a-z0-9]$'
  options:
    parameters:
      contributor_slug:
        type: 'lcdle_contributor_slug'
```

Note: the `requirements: contributor_slug:` regex embeds the blacklist directly in the route pattern so `/admin` never hits this route. Duplicates the `ContributorSlugBlacklist` list — kept in sync by the constraint's constant being the single source of truth (Task 10 adds a test asserting they agree).

- [ ] **Step 4: Create the ParamConverter**

`src/ParamConverter/ContributorSlugConverter.php`:

```php
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

  public function convert(mixed $value, mixed $definition, string $name, array $defaults): mixed {
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

  public function applies(mixed $definition, string $name, Route $route): bool {
    return ($definition['type'] ?? NULL) === 'lcdle_contributor_slug';
  }

}
```

- [ ] **Step 5: Register the ParamConverter as a service**

`lcdle_contributor.services.yml`:

```yaml
services:
  lcdle_contributor.slug_converter:
    class: Drupal\lcdle_contributor\ParamConverter\ContributorSlugConverter
    arguments:
      - '@entity_type.manager'
    tags:
      - { name: paramconverter }
```

- [ ] **Step 6: Create the controller**

`src/Controller/ContributorPageController.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContributorPageController extends ControllerBase {

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Page callback: render the profile + published articles of a contributor.
   */
  public function view(?ProfileInterface $contributor_slug = NULL): array {
    if ($contributor_slug === NULL) {
      throw new NotFoundHttpException();
    }

    $profile_view = $this->entityTypeManager
      ->getViewBuilder('profile')
      ->view($contributor_slug, 'default');

    $articles = $this->renderArticlesByAuthor((int) $contributor_slug->getOwnerId());

    return [
      '#prefix' => '<div class="contributor-page h-card">',
      '#suffix' => '</div>',
      'profile' => $profile_view,
      'articles' => $articles,
      '#cache' => [
        'tags' => [
          'profile:' . $contributor_slug->id(),
          'node_list:article',
        ],
      ],
    ];
  }

  /**
   * Title callback: display name of the contributor.
   */
  public function title(?ProfileInterface $contributor_slug = NULL): string {
    if ($contributor_slug === NULL) {
      return '';
    }
    $name_items = $contributor_slug->get('field_display_name')->getValue();
    return $name_items[0]['value'] ?? '';
  }

  /**
   * @return array<string, mixed>
   */
  private function renderArticlesByAuthor(int $uid): array {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'article')
      ->condition('uid', $uid)
      ->condition('status', 1)
      ->sort('created', 'DESC')
      ->range(0, 20)
      ->execute();

    if (empty($nids)) {
      return [
        '#markup' => '<p class="contributor-articles__empty">Aucun article publié pour le moment.</p>',
      ];
    }

    $nodes = $storage->loadMultiple($nids);
    return [
      '#theme' => 'item_list',
      '#title' => 'Articles publiés',
      '#list_type' => 'ul',
      '#attributes' => ['class' => ['contributor-articles']],
      '#items' => array_map(
        static fn($node): array => [
          '#type' => 'link',
          '#title' => $node->label(),
          '#url' => $node->toUrl(),
          '#attributes' => ['class' => ['contributor-articles__item', 'h-entry']],
        ],
        $nodes,
      ),
    ];
  }

}
```

- [ ] **Step 7: Clear caches and run tests**

Run: `ddev drush cr`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Functional/ContributorPageTest.php"`
Expected: all 4 tests pass.

- [ ] **Step 8: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Add /{slug} route for contributor pages

A custom ParamConverter resolves {contributor_slug} to a published
ProfileContributor — unknown slugs become 404. The route's requirement
regex embeds the reserved-slug blacklist so system paths like /admin
are never shadowed. Controller renders the profile (as h-card markup
via the view display) plus a list of the author's published articles
(limited to 20). Cache tags keyed on the profile and on article_list so
updates invalidate correctly.

Drafts are deliberately hidden (status: 1 filter) — contributors see
their own drafts elsewhere via the node overview."
```

---

## Task 9 — Update Pathauto pattern for articles to use author slug

**Files:**
- Create: `web/modules/custom/lcdle_contributor/config/install/pathauto.pattern.article.yml` (overrides the one from `lcdle_core`)
- Create: `web/modules/custom/lcdle_contributor/tests/src/Kernel/PathautoPatternUpdatedTest.php`

**Why shipping under lcdle_contributor**: the new pattern references `[node:author:profile_contributor_profile:slug]` which depends on the Profile contributor_profile existing — a dependency not resolvable from `lcdle_core` alone. Installing `lcdle_contributor` replaces the placeholder at import time because Drupal's config installer processes `config/install/` files in the order modules are enabled, and re-imports an existing config if it belongs to the enabling module's install set.

Actually — that last claim is **wrong in Drupal**. A module's `config/install/` refuses to install if a config entity with the same id already exists (PreExistingConfigException). We therefore use **`config/optional/`** + an explicit update via `hook_install`.

- [ ] **Step 1: Write failing test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\pathauto\Entity\PathautoPattern;

/**
 * @group lcdle_contributor
 */
final class PathautoPatternUpdatedTest extends LcdleContributorKernelTestBase {

  public function testArticlePatternUsesAuthorSlug(): void {
    $pattern = PathautoPattern::load('article');
    $this->assertNotNull($pattern);
    $this->assertSame(
      '[node:author:profile_contributor_profile:slug]/[node:title]',
      $pattern->getPattern(),
      'article pathauto pattern is now author-slug aware.',
    );
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/PathautoPatternUpdatedTest.php"`
Expected: FAIL — existing pattern is still `[node:title]`.

- [ ] **Step 3: Create the updated pattern as optional config**

Create `web/modules/custom/lcdle_contributor/config/optional/pathauto.pattern.article.yml`:

```yaml
langcode: fr
status: true
dependencies:
  config:
    - field.field.profile.contributor_profile.field_slug
    - node.type.article
    - profile.type.contributor_profile
  module:
    - node
    - profile
id: article
label: 'Article — /{author-slug}/{article-title}'
type: 'canonical_entities:node'
pattern: '[node:author:profile_contributor_profile:slug]/[node:title]'
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

- [ ] **Step 4: Add `hook_install` that replaces the existing pattern**

Create `web/modules/custom/lcdle_contributor/lcdle_contributor.install`:

```php
<?php

declare(strict_types=1);

/**
 * @file
 * Install and uninstall functions for lcdle_contributor.
 */

use Drupal\Core\Serialization\Yaml;

/**
 * Implements hook_install().
 *
 * Replaces the placeholder pathauto.pattern.article shipped by lcdle_core
 * with the author-slug aware version that requires this module.
 */
function lcdle_contributor_install(): void {
  $module_path = \Drupal::service('extension.list.module')->getPath('lcdle_contributor');
  $yaml_path = $module_path . '/config/optional/pathauto.pattern.article.yml';

  if (!file_exists($yaml_path)) {
    return;
  }

  $data = Yaml::decode(file_get_contents($yaml_path));
  $storage = \Drupal::entityTypeManager()->getStorage('pathauto_pattern');
  $existing = $storage->load('article');

  if ($existing !== NULL) {
    $existing->set('pattern', $data['pattern']);
    $existing->set('label', $data['label']);
    $existing->save();
  }
  else {
    $storage->create($data)->save();
  }
}
```

- [ ] **Step 5: Clear caches, re-install lcdle_contributor, run test**

Run: `ddev drush cr`
Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_contributor/tests/src/Kernel/PathautoPatternUpdatedTest.php"`
Expected: PASS.

- [ ] **Step 6: Manual smoke test**

```
ddev drush ev "
\$user = \Drupal\user\Entity\User::create(['name' => 'alice', 'mail' => 'alice@test.fr', 'status' => 1]);
\$user->save();
\Drupal\profile\Entity\Profile::create(['type' => 'contributor_profile', 'uid' => \$user->id(), 'field_slug' => 'alice', 'field_display_name' => 'Alice'])->save();
\$node = \Drupal\node\Entity\Node::create(['type' => 'article', 'title' => 'my-article', 'uid' => \$user->id(), 'moderation_state' => 'published']);
\$node->save();
print \$node->toUrl()->toString() . PHP_EOL;
"
```

Expected output: `/alice/my-article` (author slug prefix in effect).

Cleanup:
```
ddev drush ev "
\Drupal\node\Entity\Node::load((int)\$_GET['nid'] ?? 0)->delete();
"
```
(Adjust to the created node id; or just `drush cr` and let the test DB reset.)

- [ ] **Step 7: Commit**

```bash
git add web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Replace article Pathauto pattern with author-slug-aware version

Installing lcdle_contributor upgrades the placeholder pattern from Plan
0B ([node:title]) to [node:author:profile_contributor_profile:slug]/[node:title].
Because module config/install/ refuses to overwrite existing config,
the new pattern ships in config/optional and hook_install rewrites the
live entity in place.

Result: a new article URL is /alice/my-article, aligned with spec §6.1
URL scheme."
```

---

## Task 10 — Sync: route blacklist regex ≡ constraint blacklist

**Files:**
- Create: `web/modules/custom/lcdle_contributor/tests/src/Unit/BlacklistSyncTest.php`

**Why:** Task 8's route regex and Task 5's `RESERVED` constant duplicate the blacklist. A unit test ensures they stay in sync — drift here would cause subtle bugs (a slug accepted by the validator but rejected by the route regex, or vice versa).

- [ ] **Step 1: Write the unit test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Unit;

use Drupal\lcdle_contributor\Plugin\Validation\Constraint\ContributorSlugBlacklist;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @group lcdle_contributor
 */
final class BlacklistSyncTest extends TestCase {

  public function testRouteRegexCoversAllReservedSlugs(): void {
    $routing_file = __DIR__ . '/../../../../lcdle_contributor.routing.yml';
    $data = Yaml::parseFile($routing_file);
    $regex = $data['lcdle_contributor.contributor_page']['requirements']['contributor_slug'] ?? '';
    $this->assertNotSame('', $regex, 'route regex is present.');

    foreach (ContributorSlugBlacklist::RESERVED as $reserved) {
      $this->assertSame(
        0,
        preg_match('#' . $regex . '#', $reserved),
        "Reserved slug '{$reserved}' is excluded by the route regex.",
      );
    }
  }

  public function testRouteRegexAcceptsValidSlugs(): void {
    $routing_file = __DIR__ . '/../../../../lcdle_contributor.routing.yml';
    $data = Yaml::parseFile($routing_file);
    $regex = $data['lcdle_contributor.contributor_page']['requirements']['contributor_slug'] ?? '';

    foreach (['alice', 'bob-doe', 'roland-derudet', 'user42-a'] as $valid) {
      $this->assertSame(
        1,
        preg_match('#' . $regex . '#', $valid),
        "Valid slug '{$valid}' is accepted.",
      );
    }
  }

}
```

- [ ] **Step 2: Run — expect PASS if everything is in sync (or FAIL to reveal a gap to fix now)**

Run: `ddev exec "vendor/bin/phpunit --testsuite=unit web/modules/custom/lcdle_contributor/tests/src/Unit/BlacklistSyncTest.php"`
Expected: PASS. If the test fails, it's a real finding — fix the routing regex so it excludes every entry in `RESERVED`, then re-run.

- [ ] **Step 3: Commit**

```bash
git add web/modules/custom/lcdle_contributor/tests/
git -c commit.gpgsign=false commit -m "Test route blacklist regex matches ContributorSlugBlacklist::RESERVED

The /{slug} route regex and the field constraint's RESERVED constant
are the two places the blacklist lives. This unit test — purely static,
no Drupal bootstrap — fails fast if they drift apart. A future
extension of RESERVED without updating the regex is caught before
merge."
```

---

## Task 11 — Final verification, config export, tag

- [ ] **Step 1: Clean reinstall**

Run: `ddev drush pm:uninstall lcdle_contributor -y && ddev drush pm:install lcdle_contributor -y`
If PreExistingConfigException errors: `ddev drush config:delete <id> -y` per conflict, then retry.

- [ ] **Step 2: Run the full suite**

```bash
ddev exec "vendor/bin/phpcs --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse --memory-limit=1G"
ddev exec "vendor/bin/phpunit --testsuite=unit"
ddev exec "vendor/bin/phpunit --testsuite=kernel"
ddev exec "vendor/bin/phpunit --testsuite=functional"
```

All five exit 0.

- [ ] **Step 3: Export config**

Run: `ddev drush config:export -y`

- [ ] **Step 4: Commit exported config**

```bash
git add config/sync/ web/modules/custom/lcdle_contributor/
git -c commit.gpgsign=false commit -m "Export active config after Phase 0C-1 module install

config/sync/ now reflects the contributor_profile type, its fields,
displays, the updated pathauto pattern, and lcdle_contributor in
core.extension. The site can be rebuilt from scratch via drush
site:install --existing-config."
```

- [ ] **Step 5: Tag**

```bash
git tag -a phase-0c1-complete -m "Phase 0C-1 contributor profile complete

lcdle_contributor ships: profile type contributor_profile with 7
fields (display_name, slug, bio, avatar, banner, social_links,
accent_color), slug validation (unique + regex + blacklist), public
route /{slug}, controller rendering profile + published articles,
author-slug-aware pathauto pattern for articles, shared kernel test
base, blacklist sync unit test.

Covered by 4 kernel test classes + 1 functional test class (4 scenarios)
+ 1 unit test. Ready for Phase 0C-2 (NewsletterSubscriber)."

git push origin main
git push origin phase-0c1-complete
```

---

## Self-Review (post-plan, before execution)

**Spec coverage:**

| Spec reference | Task |
|---|---|
| §4.1 ContributorProfile — entité Profile type | 3 |
| §4.1 Champs: display_name, slug, bio, avatar, banner, social_links, accent_color | 4, 6 |
| §4.1 Slug réservé (blacklist) | 5 |
| §4.1 Slug unicité | 4 (UniqueField) |
| §6.1 Schéma URL `/{slug}` | 8 |
| §6.1 Article pattern `/{author-slug}/{article-slug}` | 9 |
| §5.4 Phase 2 onboarding | Hors 0C-1 (phase 3 projet, pas 0C) |
| §9.1 h-card microformats (prerequis IndieWeb) | 7 (view display), 8 (controller wrapper class `h-card`) |
| §11 Tests + negative space | All tasks, Task 10 (sync) |

**Placeholder scan:** no `TBD` / `TODO` strings. Every step has either code, a YAML block, or an exact command.

**Type consistency:** `contributor_profile` used consistently. `field_slug`, `field_display_name`, `field_bio`, `field_avatar`, `field_banner`, `field_social_links`, `field_accent_color` — names identical across storage / instance / displays / controller / tests. Route parameter name `contributor_slug` (matching the ParamConverter applies-to) consistent in routing.yml, ParamConverter, and controller method signature.

**Risks & mitigations:**
- The route regex `(?!admin|user|...)` is negative lookahead inside the requirements constraint — must actually be interpreted by Drupal's route matcher. Drupal 11 supports it; Task 10 asserts behaviour. If this ever breaks, fall back to a `_custom_access` callback that rejects reserved slugs programmatically.
- Pathauto pattern override via `hook_install` assumes the pattern entity is loadable by its storage. Task 9 Step 5 validates end-to-end.
