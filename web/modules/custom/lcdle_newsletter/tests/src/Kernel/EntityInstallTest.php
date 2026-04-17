<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

use Drupal\lcdle_newsletter\Entity\NewsletterSubscriber;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Tests that the NewsletterSubscriber entity type installs correctly.
 *
 * @group lcdle_newsletter
 */
final class EntityInstallTest extends LcdleNewsletterKernelTestBase {

  /**
   * Tests that the entity type definition exists.
   */
  public function testEntityTypeExists(): void {
    $definition = \Drupal::entityTypeManager()
      ->getDefinition('newsletter_subscriber', FALSE);
    $this->assertNotNull($definition, 'newsletter_subscriber entity type is defined.');
  }

  /**
   * Data provider: all 10 base fields that must be present.
   *
   * @return array<string, array{string}>
   *   Keyed array of field name arrays for use with DataProvider.
   */
  public static function baseFieldNamesProvider(): array {
    return [
      'id'           => ['id'],
      'uuid'         => ['uuid'],
      'email'        => ['email'],
      'status_value' => ['status_value'],
      'token'        => ['token'],
      'subscribed_at' => ['subscribed_at'],
      'source'       => ['source'],
      'locale'       => ['locale'],
      'author_scope' => ['author_scope'],
      'created'      => ['created'],
      'changed'      => ['changed'],
    ];
  }

  /**
   * Tests that each required base field is present on the entity type.
   */
  #[DataProvider('baseFieldNamesProvider')]
  public function testBaseFieldExists(string $fieldName): void {
    $fieldDefs = \Drupal::service('entity_field.manager')
      ->getBaseFieldDefinitions('newsletter_subscriber');
    $this->assertArrayHasKey(
      $fieldName,
      $fieldDefs,
      "Base field '{$fieldName}' must exist on newsletter_subscriber.",
    );
  }

  /**
   * Tests basic CRUD operations on the NewsletterSubscriber entity.
   */
  public function testCrud(): void {
    $subscriber = NewsletterSubscriber::create([
      'email'        => 'test@example.com',
      'status_value' => 'pending',
      'token'        => str_repeat('a', 64),
      'source'       => 'form',
    ]);
    $subscriber->save();

    $loaded = NewsletterSubscriber::load($subscriber->id());
    $this->assertNotNull($loaded, 'Entity can be loaded after save.');
    $this->assertSame('test@example.com', $loaded->get('email')->value, 'Email field round-trips correctly.');
  }

}
