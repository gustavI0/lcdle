<?php

declare(strict_types=1);

namespace Drupal\lcdle_contributor\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ContributorPageController extends ControllerBase {

  /**
   * Page callback: render the profile + published articles of a contributor.
   */
  public function view(?ProfileInterface $contributor_slug = NULL): array {
    if ($contributor_slug === NULL) {
      throw new NotFoundHttpException();
    }

    $profile_view = $this->entityTypeManager()
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
    $storage = $this->entityTypeManager()->getStorage('node');
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
