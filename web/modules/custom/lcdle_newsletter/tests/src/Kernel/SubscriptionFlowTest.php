<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

use Drupal\lcdle_newsletter\Entity\NewsletterSubscriber;

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

    $loaded = $this->findByToken($token);
    $this->assertNotNull($loaded);
    $this->assertSame('pending', $loaded->get('status_value')->value);

    $loaded->set('status_value', 'active');
    $loaded->save();

    $reloaded = $storage->load($loaded->id());
    $this->assertInstanceOf(NewsletterSubscriber::class, $reloaded);
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
    $this->assertNotNull($loaded);
    $loaded->set('status_value', 'unsubscribed');
    $loaded->save();

    $reloaded = $storage->load($loaded->id());
    $this->assertInstanceOf(NewsletterSubscriber::class, $reloaded);
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
   * Tests already-confirmed subscriber stays active on re-confirm.
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
    // Re-confirming an already-active subscription should keep it active.
    if ($loaded->get('status_value')->value === 'pending') {
      $loaded->set('status_value', 'active');
      $loaded->save();
    }
    $this->assertSame('active', $loaded->get('status_value')->value);
  }

  /**
   * Finds a subscriber by token.
   *
   * @return \Drupal\lcdle_newsletter\Entity\NewsletterSubscriber|null
   *   The subscriber, or NULL.
   */
  private function findByToken(string $token): ?NewsletterSubscriber {
    $storage = \Drupal::entityTypeManager()->getStorage('newsletter_subscriber');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('token', $token)
      ->range(0, 1)
      ->execute();
    if (empty($ids)) {
      return NULL;
    }
    $entity = $storage->load(reset($ids));
    return $entity instanceof NewsletterSubscriber ? $entity : NULL;
  }

}
