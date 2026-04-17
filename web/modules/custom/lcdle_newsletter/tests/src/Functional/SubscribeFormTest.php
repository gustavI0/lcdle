<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for the /newsletter subscribe form.
 *
 * @group lcdle_newsletter
 */
class SubscribeFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   *
   * @var array<int, string>
   */
  protected static $modules = ['lcdle_newsletter'];

  /**
   * Tests that the subscribe form is accessible at /newsletter.
   */
  public function testFormIsAccessible(): void {
    $this->drupalGet('/newsletter');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->fieldExists('email');
  }

  /**
   * Tests that submitting the form creates a subscriber with correct fields.
   */
  public function testSubmitCreatesSubscriber(): void {
    $this->drupalGet('/newsletter');
    $this->submitForm(
      ['email' => 'test@example.com', 'consent' => 1],
      "S'abonner"
    );

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $subscribers = $entity_type_manager->getStorage('newsletter_subscriber')
      ->loadByProperties(['email' => 'test@example.com']);

    $this->assertCount(1, $subscribers, 'One subscriber should have been created.');

    $subscriber = reset($subscribers);
    $this->assertEquals('pending', $subscriber->get('status_value')->value);
    $this->assertNotEmpty($subscriber->get('token')->value);
    $this->assertEquals('form', $subscriber->get('source')->value);
  }

  /**
   * Tests that a duplicate email submission does not create a second subscriber.
   */
  public function testDuplicateEmailRejected(): void {
    // First submission.
    $this->drupalGet('/newsletter');
    $this->submitForm(
      ['email' => 'duplicate@example.com', 'consent' => 1],
      "S'abonner"
    );

    // Second submission with the same email.
    $this->drupalGet('/newsletter');
    $this->submitForm(
      ['email' => 'duplicate@example.com', 'consent' => 1],
      "S'abonner"
    );

    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = \Drupal::entityTypeManager();
    $subscribers = $entity_type_manager->getStorage('newsletter_subscriber')
      ->loadByProperties(['email' => 'duplicate@example.com']);

    $this->assertCount(1, $subscribers, 'Only one subscriber should exist for a duplicate email.');
  }

  /**
   * Tests that the confirmation link is displayed after successful submission.
   */
  public function testConfirmationLinkDisplayed(): void {
    $this->drupalGet('/newsletter');
    $this->submitForm(
      ['email' => 'confirm@example.com', 'consent' => 1],
      "S'abonner"
    );

    $this->assertSession()->pageTextContains('/newsletter/confirm/');
  }

}
