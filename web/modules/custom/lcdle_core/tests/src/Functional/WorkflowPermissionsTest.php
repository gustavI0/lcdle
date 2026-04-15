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
    // The moderation widget shows destination state labels, not transition
    // labels. contributor_new may only reach needs_review ("En relecture") and
    // draft ("Brouillon") — the published state ("Publié") must be absent.
    $this->assertSession()->pageTextNotContains('Publié');
    $this->assertSession()->pageTextContains('En relecture');
  }

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
