<?php

declare(strict_types=1);

namespace Drupal\lcdle_theme_helpers\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Serves a placeholder homepage for Phase 2B1.
 *
 * The real homepage (aggregating latest articles, featured, contributor
 * strip) arrives in Phase 2B2. This placeholder exists only so anonymous
 * visitors can actually see the shell (header + footer + dark mode
 * toggle) during 2B1 development.
 */
final class HomepagePlaceholderController extends ControllerBase {

  /**
   * Builds the placeholder render array.
   *
   * @return array<string, mixed>
   *   Render array.
   */
  public function build(): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['lcdle-system-page']],
      'tag' => [
        '#markup' => '<p class="lcdle-tag">' . $this->t('En construction') . '</p>',
      ],
      'title' => [
        '#markup' => '<h1 class="lcdle-system-page__title">' . $this->t("Bientôt") . '</h1>',
      ],
      'body' => [
        '#markup' => '<p class="lcdle-system-page__body">'
        . $this->t("Les pages arrivent en Phase 2B2. En attendant, ce shell valide que l'en-tête, le pied et le bascule mode sombre fonctionnent.")
        . '</p>',
      ],
    ];
  }

}
