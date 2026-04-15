<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;

/**
 * Tests role-based workflow permissions via the UI.
 *
 * @group lcdle_core
 */
final class WorkflowPermissionsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lcdle_core'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests contributor_new cannot publish directly (only submit for review).
   */
  public function testContributorNewCannotPublishDirectly(): void {
    $user = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_new']]);
    $user->addRole('contributor_new');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    // The moderation widget shows destination state labels, not transition
    // labels. contributor_new may only reach needs_review ("En relecture") and
    // draft ("Brouillon") — the published state ("Publié") must be absent.
    $this->assertSession()->pageTextNotContains('Publié');
    $this->assertSession()->pageTextContains('En relecture');
  }

  /**
   * Tests contributor_trusted can publish an article directly.
   */
  public function testContributorTrustedCanPublishDirectly(): void {
    $user = $this->drupalCreateUser([], NULL, FALSE, ['roles' => ['contributor_trusted']]);
    $user->addRole('contributor_trusted');
    $user->save();
    $this->drupalLogin($user);

    $this->drupalGet('node/add/article');
    $this->assertSession()->statusCodeEquals(200);
    // contributor_trusted has the publish transition, so the published state
    // label ("Publié") must appear in the moderation state select widget.
    $this->assertSession()->pageTextContains('Publié');
  }

  /**
   * Tests an editor can transition a needs_review node to published.
   */
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
    // Editor can transition needs_review → published, so "Publié" must appear.
    $this->assertSession()->pageTextContains('Publié');
  }

}
