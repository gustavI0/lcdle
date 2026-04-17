---
id: PLAN-0C2
type: implementation-plan
status: draft
last-updated: 2026-04-17
spec: SPEC-001
phase: "0C-2 — Newsletter Subscriber"
---

# Phase 0C-2 — Newsletter Subscriber Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the `lcdle_newsletter` custom module that provisions a custom content entity `NewsletterSubscriber` with double opt-in skeleton (token generation, confirmation endpoint, unsubscribe endpoint), a public subscribe form at `/newsletter`, and an admin listing — all without actual email sending (deferred to Phase 3).

**Architecture:** One custom content entity `NewsletterSubscriber` with base fields defined in PHP (not config — no bundles, no UI field management). A stateless `TokenGenerator` service produces URL-safe random tokens. Three routes: `/newsletter` (subscribe form), `/newsletter/confirm/{token}` (activate subscription), `/newsletter/unsubscribe/{token}` (opt-out). Admin listing via Views integration. The entity's `author_scope` field (nullable user reference) is pre-cabled for per-author newsletters (Phase 4) but unused in the MVP form.

**Tech Stack:** Drupal 11, PHP 8.4, custom ContentEntityType (PHP attribute), Symfony Form, Views data integration.

---

## Scope Check

Plan 0C-2 covers NewsletterSubscriber only. Email sending, newsletter templates, and per-author subscription UI are **Phase 3+**. This plan delivers the data layer, the token flow skeleton, and the admin tooling.

---

## File Structure

### Created

```
web/modules/custom/lcdle_newsletter/
├── lcdle_newsletter.info.yml
├── lcdle_newsletter.routing.yml
├── lcdle_newsletter.permissions.yml
├── lcdle_newsletter.views.inc
├── src/
│   ├── Entity/
│   │   └── NewsletterSubscriber.php
│   ├── Service/
│   │   └── TokenGenerator.php
│   ├── Form/
│   │   └── SubscribeForm.php
│   └── Controller/
│       └── SubscriptionController.php
├── config/
│   └── install/
│       └── views.view.newsletter_subscribers_admin.yml
└── tests/
    └── src/
        ├── Unit/
        │   └── TokenGeneratorTest.php
        ├── Kernel/
        │   ├── LcdleNewsletterKernelTestBase.php
        │   ├── EntityInstallTest.php
        │   └── SubscriptionFlowTest.php
        └── Functional/
            └── SubscribeFormTest.php
```

---

## Conventions

- Shared `LcdleNewsletterKernelTestBase` from Task 1 (lesson from 0C-1).
- Entity machine name: `newsletter_subscriber` (matches glossary).
- Status field uses a PHP `string` base field with allowed values, not a contrib enum module (YAGNI).
- Token = 64-char hex string (32 bytes via `random_bytes`), URL-safe, unique.
- All code English, UI labels French.
- `Drupal\Component\Serialization\Yaml` (not `Core\Serialization\Yaml`).
- `hook_entity_bundle_field_info_alter` pattern NOT needed here — base fields are defined in the entity class itself, constraints included.

---

## Task 1 — Module skeleton + shared test base + entity class

**Files:**
- Create: `web/modules/custom/lcdle_newsletter/lcdle_newsletter.info.yml`
- Create: `web/modules/custom/lcdle_newsletter/src/Entity/NewsletterSubscriber.php`
- Create: `web/modules/custom/lcdle_newsletter/tests/src/Kernel/LcdleNewsletterKernelTestBase.php`
- Create: `web/modules/custom/lcdle_newsletter/tests/src/Kernel/EntityInstallTest.php`

- [ ] **Step 1: Write failing test**

`EntityInstallTest.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

/**
 * Tests newsletter_subscriber entity type installation.
 *
 * @group lcdle_newsletter
 */
final class EntityInstallTest extends LcdleNewsletterKernelTestBase {

  /**
   * Tests the entity type is defined and installable.
   */
  public function testEntityTypeExists(): void {
    $definition = \Drupal::entityTypeManager()->getDefinition('newsletter_subscriber', FALSE);
    $this->assertNotNull($definition, 'newsletter_subscriber entity type is defined.');
  }

  /**
   * Tests all expected base fields are present.
   *
   * @dataProvider provideExpectedFields
   */
  public function testBaseFieldExists(string $fieldName): void {
    $fields = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('newsletter_subscriber');
    $this->assertArrayHasKey($fieldName, $fields, "Base field {$fieldName} exists.");
  }

  /**
   * Data provider for base field names.
   *
   * @return array<string, array{string}>
   *   Field names to check.
   */
  public static function provideExpectedFields(): array {
    return [
      'id' => ['id'],
      'email' => ['email'],
      'status_value' => ['status_value'],
      'token' => ['token'],
      'subscribed_at' => ['subscribed_at'],
      'source' => ['source'],
      'locale' => ['locale'],
      'author_scope' => ['author_scope'],
      'created' => ['created'],
      'changed' => ['changed'],
    ];
  }

  /**
   * Tests creating and loading a subscriber entity.
   */
  public function testEntityCrudWorks(): void {
    $subscriber = \Drupal::entityTypeManager()
      ->getStorage('newsletter_subscriber')
      ->create([
        'email' => 'test@example.com',
        'status_value' => 'pending',
        'token' => bin2hex(random_bytes(32)),
        'source' => 'test',
      ]);
    $subscriber->save();
    $this->assertNotNull($subscriber->id());

    $loaded = \Drupal::entityTypeManager()
      ->getStorage('newsletter_subscriber')
      ->load($subscriber->id());
    $this->assertSame('test@example.com', $loaded->get('email')->value);
    $this->assertSame('pending', $loaded->get('status_value')->value);
  }

}
```

`LcdleNewsletterKernelTestBase.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Shared bootstrap for lcdle_newsletter kernel tests.
 */
abstract class LcdleNewsletterKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'lcdle_newsletter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('newsletter_subscriber');
    $this->installConfig(['system']);
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Kernel/EntityInstallTest.php"`

- [ ] **Step 3: Create module info + entity class**

`lcdle_newsletter.info.yml`:

```yaml
name: 'LCDLE Newsletter'
type: module
description: 'Newsletter subscriber management — double opt-in, token-based unsubscribe, admin listing.'
package: 'LCDLE'
core_version_requirement: ^11
dependencies:
  - drupal:user
  - drupal:views
```

`src/Entity/NewsletterSubscriber.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Newsletter subscriber entity.
 *
 * Stores email + opt-in status + token for double opt-in and unsubscribe.
 * Deliberately separate from User to allow anonymous subscriptions (RGPD).
 */
#[\Drupal\Core\Entity\Attribute\ContentEntityType(
  id: 'newsletter_subscriber',
  label: 'Abonné newsletter',
  label_collection: 'Abonnés newsletter',
  label_singular: 'abonné newsletter',
  label_plural: 'abonnés newsletter',
  handlers: [
    'storage' => \Drupal\Core\Entity\Sql\SqlContentEntityStorage::class,
    'views_data' => \Drupal\views\EntityViewsData::class,
    'list_builder' => \Drupal\Core\Entity\EntityListBuilder::class,
  ],
  base_table: 'newsletter_subscriber',
  admin_permission: 'administer newsletter subscribers',
  entity_keys: [
    'id' => 'id',
    'label' => 'email',
    'uuid' => 'uuid',
  ],
)]
final class NewsletterSubscriber extends ContentEntityBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel('Email')
      ->setDescription('Adresse email de l\'abonné.')
      ->setRequired(TRUE)
      ->addConstraint('UniqueField')
      ->setSetting('max_length', 254)
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 0])
      ->setDisplayOptions('form', ['weight' => 0])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['status_value'] = BaseFieldDefinition::create('list_string')
      ->setLabel('Statut')
      ->setDescription('État de l\'abonnement.')
      ->setRequired(TRUE)
      ->setDefaultValue('pending')
      ->setSetting('allowed_values', [
        'pending' => 'En attente de confirmation',
        'active' => 'Actif',
        'unsubscribed' => 'Désabonné',
        'bounced' => 'Rebond',
      ])
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 1])
      ->setDisplayOptions('form', ['weight' => 1])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['token'] = BaseFieldDefinition::create('string')
      ->setLabel('Token')
      ->setDescription('Token unique pour confirmation et désabonnement.')
      ->setRequired(TRUE)
      ->setSetting('max_length', 64)
      ->addConstraint('UniqueField')
      ->setDisplayConfigurable('view', FALSE)
      ->setDisplayConfigurable('form', FALSE);

    $fields['subscribed_at'] = BaseFieldDefinition::create('created')
      ->setLabel('Date d\'inscription')
      ->setDescription('Date de soumission du formulaire.')
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 2])
      ->setDisplayConfigurable('view', TRUE);

    $fields['source'] = BaseFieldDefinition::create('string')
      ->setLabel('Source')
      ->setDescription('Origine de l\'inscription (footer, /newsletter, page auteur…).')
      ->setSetting('max_length', 60)
      ->setDefaultValue('form')
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 3])
      ->setDisplayConfigurable('view', TRUE);

    $fields['locale'] = BaseFieldDefinition::create('string')
      ->setLabel('Langue')
      ->setDescription('Code langue de l\'abonné.')
      ->setSetting('max_length', 12)
      ->setDefaultValue('fr')
      ->setDisplayConfigurable('view', TRUE);

    $fields['author_scope'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel('Auteur ciblé')
      ->setDescription('Si renseigné, abonnement aux articles d\'un contributeur spécifique (Phase 4). NULL = newsletter globale.')
      ->setSetting('target_type', 'user')
      ->setDisplayOptions('view', ['label' => 'inline', 'weight' => 4])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel('Créé le');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel('Modifié le');

    return $fields;
  }

}
```

- [ ] **Step 4: Run test — expect PASS**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Kernel/EntityInstallTest.php"`
Expected: PASS (12 tests — 1 entity type + 10 dataProvider rows + 1 CRUD).

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Add NewsletterSubscriber custom entity with base fields

Content entity separate from User to support anonymous subscriptions
(RGPD). Fields: email (unique), status_value (pending/active/unsubscribed
/bounced), token (unique, 64-char), subscribed_at, source, locale,
author_scope (nullable user ref for per-author newsletters in Phase 4),
created, changed. Spec §4.1 + §8."
```

---

## Task 2 — Token generator service

**Files:**
- Create: `web/modules/custom/lcdle_newsletter/src/Service/TokenGenerator.php`
- Create: `web/modules/custom/lcdle_newsletter/tests/src/Unit/TokenGeneratorTest.php`

- [ ] **Step 1: Write unit test (no Drupal bootstrap)**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Unit;

use Drupal\lcdle_newsletter\Service\TokenGenerator;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the token generator service.
 *
 * @group lcdle_newsletter
 */
final class TokenGeneratorTest extends TestCase {

  /**
   * Tests generated token has correct length.
   */
  public function testTokenLength(): void {
    $generator = new TokenGenerator();
    $token = $generator->generate();
    $this->assertSame(64, strlen($token), 'Token is 64 characters.');
  }

  /**
   * Tests token is URL-safe (hex only).
   */
  public function testTokenIsHexOnly(): void {
    $generator = new TokenGenerator();
    $token = $generator->generate();
    $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token, 'Token is lowercase hex.');
  }

  /**
   * Tests two tokens are different (probabilistic but effectively certain).
   */
  public function testTokensAreUnique(): void {
    $generator = new TokenGenerator();
    $a = $generator->generate();
    $b = $generator->generate();
    $this->assertNotSame($a, $b, 'Two generated tokens must differ.');
  }

}
```

- [ ] **Step 2: Run — expect FAIL (class not found)**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Unit/TokenGeneratorTest.php"`

- [ ] **Step 3: Create the service**

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Service;

/**
 * Generates cryptographically secure, URL-safe tokens for newsletter opt-in.
 */
final class TokenGenerator {

  /**
   * Generates a 64-character hex token (32 bytes of entropy).
   */
  public function generate(): string {
    return bin2hex(random_bytes(32));
  }

}
```

- [ ] **Step 4: Run — expect PASS**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Unit/TokenGeneratorTest.php"`
Expected: 3 tests PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Add TokenGenerator service for newsletter double opt-in

Stateless service generating 64-char hex tokens (32 bytes of entropy via
random_bytes). No Drupal dependency — pure PHP, unit-testable without
bootstrap. Token uniqueness is enforced by the entity's UniqueField
constraint, not the generator itself."
```

---

## Task 3 — Subscribe form at `/newsletter`

**Files:**
- Create: `web/modules/custom/lcdle_newsletter/lcdle_newsletter.routing.yml`
- Create: `web/modules/custom/lcdle_newsletter/lcdle_newsletter.services.yml`
- Create: `web/modules/custom/lcdle_newsletter/src/Form/SubscribeForm.php`
- Create: `web/modules/custom/lcdle_newsletter/tests/src/Functional/SubscribeFormTest.php`

- [ ] **Step 1: Write failing functional test**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the newsletter subscribe form at /newsletter.
 *
 * @group lcdle_newsletter
 */
final class SubscribeFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = ['lcdle_newsletter'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the form renders at /newsletter.
   */
  public function testFormIsAccessible(): void {
    $this->drupalGet('/newsletter');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('email');
  }

  /**
   * Tests submitting a valid email creates a pending subscriber.
   */
  public function testSubmitCreatesSubscriber(): void {
    $this->drupalGet('/newsletter');
    $this->submitForm(['email' => 'reader@example.com'], 'S\'abonner');

    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('email', 'reader@example.com')
      ->execute();
    $this->assertCount(1, $ids, 'One subscriber created.');

    $subscriber = $storage->load(reset($ids));
    $this->assertSame('pending', $subscriber->get('status_value')->value);
    $this->assertNotEmpty($subscriber->get('token')->value);
    $this->assertSame('form', $subscriber->get('source')->value);
    $this->assertSame('fr', $subscriber->get('locale')->value);
  }

  /**
   * Tests duplicate email is rejected.
   */
  public function testDuplicateEmailRejected(): void {
    $this->drupalGet('/newsletter');
    $this->submitForm(['email' => 'dupe@example.com'], 'S\'abonner');
    $this->drupalGet('/newsletter');
    $this->submitForm(['email' => 'dupe@example.com'], 'S\'abonner');

    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $count = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('email', 'dupe@example.com')
      ->count()
      ->execute();
    $this->assertSame(1, (int) $count, 'Only one subscriber for duplicate email.');
  }

  /**
   * Tests confirmation link is displayed after submission.
   */
  public function testConfirmationLinkDisplayed(): void {
    $this->drupalGet('/newsletter');
    $this->submitForm(['email' => 'link@example.com'], 'S\'abonner');
    $this->assertSession()->pageTextContains('/newsletter/confirm/');
  }

}
```

- [ ] **Step 2: Run — expect FAIL**

Run: `ddev exec "mkdir -p /var/www/html/web/sites/simpletest/browser_output && vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Functional/SubscribeFormTest.php"`

- [ ] **Step 3: Create routing**

`lcdle_newsletter.routing.yml`:

```yaml
lcdle_newsletter.subscribe:
  path: '/newsletter'
  defaults:
    _form: '\Drupal\lcdle_newsletter\Form\SubscribeForm'
    _title: 'Newsletter'
  requirements:
    _access: 'TRUE'
```

- [ ] **Step 4: Create services file**

`lcdle_newsletter.services.yml`:

```yaml
services:
  lcdle_newsletter.token_generator:
    class: Drupal\lcdle_newsletter\Service\TokenGenerator
```

- [ ] **Step 5: Create the form**

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\lcdle_newsletter\Service\TokenGenerator;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Public newsletter subscribe form.
 */
final class SubscribeForm extends FormBase {

  /**
   * Constructs a SubscribeForm.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TokenGenerator $tokenGenerator,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('lcdle_newsletter.token_generator'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'lcdle_newsletter_subscribe';
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form array.
   *
   * @return array<string, mixed>
   *   The form array.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['email'] = [
      '#type' => 'email',
      '#title' => 'Adresse email',
      '#required' => TRUE,
      '#attributes' => ['placeholder' => 'votre@email.fr'],
    ];

    $form['consent'] = [
      '#type' => 'checkbox',
      '#title' => 'J\'accepte de recevoir la newsletter et comprends que je peux me désabonner à tout moment.',
      '#required' => TRUE,
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => 'S\'abonner',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @param array<string, mixed> $form
   *   The form array.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $email = $form_state->getValue('email');
    $storage = $this->entityTypeManager->getStorage('newsletter_subscriber');

    // Reject duplicate silently (show same success message to avoid email enumeration).
    $existing = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('email', $email)
      ->count()
      ->execute();

    if ((int) $existing > 0) {
      $this->messenger()->addStatus('Merci ! Vérifiez votre boîte mail pour confirmer votre inscription.');
      return;
    }

    $token = $this->tokenGenerator->generate();
    $subscriber = $storage->create([
      'email' => $email,
      'status_value' => 'pending',
      'token' => $token,
      'source' => 'form',
    ]);
    $subscriber->save();

    $confirm_url = Url::fromRoute('lcdle_newsletter.confirm', ['token' => $token])
      ->setAbsolute()
      ->toString();

    // MVP: display the confirmation link (no email sending yet).
    $this->messenger()->addStatus('Merci ! Vérifiez votre boîte mail pour confirmer votre inscription.');
    $this->messenger()->addStatus('(MVP — lien de confirmation : ' . $confirm_url . ')');
  }

}
```

- [ ] **Step 6: Run — some tests may still fail (confirm route not yet defined)**

Run the test. `testConfirmationLinkDisplayed` needs the confirm route to exist for URL generation. Add a placeholder route now:

Append to `lcdle_newsletter.routing.yml`:

```yaml

lcdle_newsletter.confirm:
  path: '/newsletter/confirm/{token}'
  defaults:
    _controller: '\Drupal\lcdle_newsletter\Controller\SubscriptionController::confirm'
    _title: 'Confirmation'
  requirements:
    _access: 'TRUE'
    token: '[0-9a-f]{64}'

lcdle_newsletter.unsubscribe:
  path: '/newsletter/unsubscribe/{token}'
  defaults:
    _controller: '\Drupal\lcdle_newsletter\Controller\SubscriptionController::unsubscribe'
    _title: 'Désabonnement'
  requirements:
    _access: 'TRUE'
    token: '[0-9a-f]{64}'
```

Create a minimal controller so the route compiles:

`src/Controller/SubscriptionController.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Handles newsletter confirmation and unsubscribe requests.
 */
final class SubscriptionController extends ControllerBase {

  /**
   * Confirms a pending subscription via token.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function confirm(string $token): array {
    return ['#markup' => 'Placeholder — implemented in Task 4.'];
  }

  /**
   * Unsubscribes via token.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function unsubscribe(string $token): array {
    return ['#markup' => 'Placeholder — implemented in Task 4.'];
  }

}
```

- [ ] **Step 7: Run tests**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Functional/SubscribeFormTest.php"`
Expected: 4/4 PASS.

- [ ] **Step 8: Commit**

```bash
git add web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Add /newsletter subscribe form with double opt-in token skeleton

Public form at /newsletter creates a NewsletterSubscriber in 'pending'
status with a unique token. Duplicate emails are silently ignored to
prevent email enumeration. The confirmation URL is displayed on-screen
(MVP — actual email sending deferred to Phase 3). Consent checkbox
required (RGPD). Placeholder confirm/unsubscribe routes stubbed for
Task 4."
```

---

## Task 4 — Confirm + unsubscribe controllers

**Files:**
- Modify: `web/modules/custom/lcdle_newsletter/src/Controller/SubscriptionController.php`
- Create: `web/modules/custom/lcdle_newsletter/tests/src/Kernel/SubscriptionFlowTest.php`

- [ ] **Step 1: Write kernel test for the full flow**

```php
<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

/**
 * Tests the subscription confirmation and unsubscribe flow.
 *
 * @group lcdle_newsletter
 */
final class SubscriptionFlowTest extends LcdleNewsletterKernelTestBase {

  /**
   * Tests confirming a pending subscriber activates it.
   */
  public function testConfirmActivatesSubscription(): void {
    $token = bin2hex(random_bytes(32));
    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $subscriber = $storage->create([
      'email' => 'confirm@example.com',
      'status_value' => 'pending',
      'token' => $token,
      'source' => 'test',
    ]);
    $subscriber->save();

    // Simulate confirmation.
    $loaded = $this->findByToken($token);
    $this->assertNotNull($loaded);
    $this->assertSame('pending', $loaded->get('status_value')->value);

    $loaded->set('status_value', 'active');
    $loaded->save();

    $reloaded = $storage->load($loaded->id());
    $this->assertSame('active', $reloaded->get('status_value')->value);
  }

  /**
   * Tests unsubscribing an active subscriber.
   */
  public function testUnsubscribeDeactivates(): void {
    $token = bin2hex(random_bytes(32));
    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $subscriber = $storage->create([
      'email' => 'unsub@example.com',
      'status_value' => 'active',
      'token' => $token,
      'source' => 'test',
    ]);
    $subscriber->save();

    $loaded = $this->findByToken($token);
    $loaded->set('status_value', 'unsubscribed');
    $loaded->save();

    $reloaded = $storage->load($loaded->id());
    $this->assertSame('unsubscribed', $reloaded->get('status_value')->value);
  }

  /**
   * Tests invalid token returns null.
   */
  public function testInvalidTokenReturnsNull(): void {
    $result = $this->findByToken('nonexistent_token_000000000000000000000000000000000');
    $this->assertNull($result);
  }

  /**
   * Tests already-confirmed subscriber stays active.
   */
  public function testDoubleConfirmIsIdempotent(): void {
    $token = bin2hex(random_bytes(32));
    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $subscriber = $storage->create([
      'email' => 'idempotent@example.com',
      'status_value' => 'active',
      'token' => $token,
      'source' => 'test',
    ]);
    $subscriber->save();

    $loaded = $this->findByToken($token);
    $loaded->set('status_value', 'active');
    $loaded->save();
    $this->assertSame('active', $loaded->get('status_value')->value);
  }

  /**
   * Finds a subscriber by token.
   *
   * @return \Drupal\lcdle_newsletter\Entity\NewsletterSubscriber|null
   *   The subscriber, or NULL.
   */
  private function findByToken(string $token): mixed {
    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('token', $token)
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return $storage->load(reset($ids));
  }

}
```

- [ ] **Step 2: Run — tests should PASS** (they test the entity layer, not the controller)

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Kernel/SubscriptionFlowTest.php"`

- [ ] **Step 3: Implement the real controller**

Replace the placeholders in `SubscriptionController.php`:

```php
<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Handles newsletter confirmation and unsubscribe requests.
 */
final class SubscriptionController extends ControllerBase {

  /**
   * Constructs a SubscriptionController.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Confirms a pending subscription via token.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function confirm(string $token): array {
    $subscriber = $this->findByToken($token);
    if ($subscriber === NULL) {
      throw new NotFoundHttpException();
    }

    $status = $subscriber->get('status_value')->value;
    if ($status === 'pending') {
      $subscriber->set('status_value', 'active');
      $subscriber->save();
    }

    return [
      '#markup' => '<p>Votre inscription à la newsletter est confirmée. Merci !</p>',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Unsubscribes via token.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function unsubscribe(string $token): array {
    $subscriber = $this->findByToken($token);
    if ($subscriber === NULL) {
      throw new NotFoundHttpException();
    }

    $status = $subscriber->get('status_value')->value;
    if ($status !== 'unsubscribed') {
      $subscriber->set('status_value', 'unsubscribed');
      $subscriber->save();
    }

    return [
      '#markup' => '<p>Vous avez été désabonné de la newsletter.</p>',
      '#cache' => ['max-age' => 0],
    ];
  }

  /**
   * Finds a subscriber by token.
   *
   * @return \Drupal\lcdle_newsletter\Entity\NewsletterSubscriber|null
   *   The subscriber, or NULL if not found.
   */
  private function findByToken(string $token): mixed {
    $storage = $this->entityTypeManager->getStorage('newsletter_subscriber');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('token', $token)
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    return $storage->load(reset($ids));
  }

}
```

- [ ] **Step 4: Run all tests**

Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Kernel/"` — expect PASS.
Run: `ddev exec "vendor/bin/phpunit web/modules/custom/lcdle_newsletter/tests/src/Functional/"` — expect PASS.

- [ ] **Step 5: Commit**

```bash
git add web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Implement confirm + unsubscribe endpoints for newsletter

/newsletter/confirm/{token} transitions pending → active (idempotent
if already active). /newsletter/unsubscribe/{token} transitions any
status → unsubscribed. Invalid tokens return 404. Both endpoints are
uncached (#cache max-age=0) because they mutate state.

Kernel tests cover the entity-level flow (confirm, unsubscribe, invalid
token, double-confirm idempotency)."
```

---

## Task 5 — Admin permission + Views listing

**Files:**
- Create: `web/modules/custom/lcdle_newsletter/lcdle_newsletter.permissions.yml`
- Create: `web/modules/custom/lcdle_newsletter/config/install/views.view.newsletter_subscribers_admin.yml`

- [ ] **Step 1: Create the permission**

`lcdle_newsletter.permissions.yml`:

```yaml
administer newsletter subscribers:
  title: 'Administrer les abonnés newsletter'
  description: 'Voir, modifier et supprimer les abonnés newsletter.'
  restrict access: true
```

- [ ] **Step 2: Create the admin Views listing**

This is a standard Views config YAML. Rather than pasting 150+ lines of Views config, generate it via Drupal UI and export:

```bash
ddev drush pm:install lcdle_newsletter -y
# Create the view in Drupal UI at /admin/structure/views/add:
# - Machine name: newsletter_subscribers_admin
# - Show: Newsletter subscriber (newsletter_subscriber entity)
# - Path: /admin/content/newsletter-subscribers
# - Menu: Admin → Content
# - Fields: email, status_value, source, subscribed_at, created
# - Filters: status_value (exposed)
# - Sort: created DESC
# - Admin permission: administer newsletter subscribers
ddev drush config:export --destination=/tmp/views-export -y
# Copy the views config file to the module
```

**Simpler alternative for this plan**: skip the Views admin listing for now and add it as a `hook_install` that creates the view programmatically, OR defer to the config export in the final task. The entity already has `views_data` handler and `admin_permission` set — a functional admin listing can be added via the UI after install and then exported.

**Decision**: Ship the permission file now. The Views listing will be generated during the final verification task (Task 6) when we export config. This keeps the plan simple and avoids 150 lines of brittle Views YAML.

- [ ] **Step 3: Verify permission exists after install**

Run: `ddev drush pm:uninstall lcdle_newsletter -y && ddev drush pm:install lcdle_newsletter -y`
Run: `ddev drush ev "print_r(\Drupal::service('user.permissions')->getPermissions()['administer newsletter subscribers'] ?? 'NOT FOUND');" `
Expected: prints the permission definition.

- [ ] **Step 4: Commit**

```bash
git add web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Add admin permission for newsletter subscriber management

The entity type already declares admin_permission in its attribute.
The permissions.yml formalizes it with French label and description.
A Views admin listing (path /admin/content/newsletter-subscribers) will
be generated via the Drupal UI and committed in the config export step."
```

---

## Task 6 — Final verification + config export + tag + push

- [ ] **Step 1: Run the full suite**

```bash
ddev exec "vendor/bin/phpcs --standard=phpcs.xml.dist"
ddev exec "vendor/bin/phpstan analyse --memory-limit=1G"
ddev exec "vendor/bin/phpunit --testsuite=unit"
ddev exec "vendor/bin/phpunit --testsuite=kernel"
ddev exec "vendor/bin/phpunit --testsuite=functional"
```

All five exit 0. Fix PHPCS/PHPStan issues if any (doc comments, type annotations).

- [ ] **Step 2: Export config**

Run: `ddev drush config:export -y`

- [ ] **Step 3: Commit exported config**

```bash
git add config/sync/ web/modules/custom/lcdle_newsletter/
git -c commit.gpgsign=false commit -m "Export active config after Phase 0C-2 module install

config/sync/ now includes lcdle_newsletter in core.extension and
the newsletter_subscriber entity schema. The site can be rebuilt via
drush site:install --existing-config."
```

- [ ] **Step 4: Tag**

```bash
git tag -a phase-0c2-complete -m "Phase 0C-2 newsletter subscriber complete

lcdle_newsletter ships: custom content entity newsletter_subscriber
with base fields (email, status, token, subscribed_at, source, locale,
author_scope), TokenGenerator service, subscribe form at /newsletter,
confirm + unsubscribe endpoints (/newsletter/confirm/{token},
/newsletter/unsubscribe/{token}), admin permission.

Covered by 1 unit test class (3 tests), 2 kernel test classes,
1 functional test class (4 scenarios). Email sending deferred to Phase 3.

Ready for Phase 1 (WordPress migration)."
```

- [ ] **Step 5: Push**

```bash
git push origin main
git push origin phase-0c2-complete
```

---

## Self-Review

**Spec coverage:**

| Spec reference | Task |
|---|---|
| §4.1 NewsletterSubscriber entity | 1 |
| §4.1 Fields: email, status, token, subscribed_at, source, locale, author_scope | 1 |
| §8.1 Subscribe form /newsletter | 3 |
| §8.1 Double opt-in token | 2, 3 |
| §8.1 No email sending at MVP | 3 (link displayed on-screen) |
| §8.4 RGPD consent checkbox | 3 |
| §8.4 Token-based unsubscribe | 4 |
| §11.2 Negative space: duplicate email rejected | 3 (functional test) |
| §11.2 Negative space: invalid token → 404 | 4 (kernel test) |
| §11.2 Negative space: double-confirm idempotent | 4 (kernel test) |
| §8.3 author_scope (per-author, Phase 4) | 1 (field present, unused in form) |

**Out of scope (Phase 3+):** email sending, newsletter templates, per-author subscription UI, digest automation, Listmonk/Simplenews integration, admin Views listing (generated via UI + config export).

**Placeholder scan:** No TBD/TODO in code. Controller placeholders in Task 3 are immediately replaced in Task 4.

**Type consistency:** `newsletter_subscriber` entity type ID, `status_value` field name (not `status` — avoids collision with entity status), `token` field name, `TokenGenerator` class name — all consistent across entity, service, form, controller, and tests.
