<?php

declare(strict_types=1);

namespace Drupal\lcdle_newsletter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
    $storage = $this->entityTypeManager()->getStorage('newsletter_subscriber');
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
