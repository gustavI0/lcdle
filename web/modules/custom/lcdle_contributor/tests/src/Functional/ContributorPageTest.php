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
      'field_bio' => ['value' => 'Bio courte.', 'format' => 'plain_text'],
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
    $admin = $this->drupalCreateUser([], NULL, TRUE);
    $this->drupalLogin($admin);
    $this->drupalGet('/admin');
    $this->assertSession()->statusCodeEquals(200);
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
