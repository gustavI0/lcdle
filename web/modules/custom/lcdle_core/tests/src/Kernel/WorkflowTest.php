<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\workflows\Entity\Workflow;

/**
 * @group lcdle_core
 */
final class WorkflowTest extends KernelTestBase {

  protected static $modules = [
    'system','user','node','text','field','taxonomy','file','image','media','media_library','workflows','content_moderation','views','path','lcdle_core',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['node', 'lcdle_core']);
  }

  public function testArticleWorkflowExists(): void {
    $workflow = Workflow::load('article_workflow');
    $this->assertNotNull($workflow, 'article_workflow exists.');
  }

  public function testWorkflowHasExpectedStates(): void {
    $workflow = Workflow::load('article_workflow');
    $states = array_keys($workflow->getTypePlugin()->getStates());
    sort($states);
    $this->assertSame(
      ['archived', 'draft', 'needs_review', 'published'],
      $states,
      'article_workflow has 4 states.',
    );
  }

  public function testWorkflowHasExpectedTransitions(): void {
    $workflow = Workflow::load('article_workflow');
    $transitions = array_keys($workflow->getTypePlugin()->getTransitions());
    sort($transitions);
    $this->assertSame(
      ['archive', 'create_new_draft', 'publish', 'reject', 'restore_to_draft', 'submit_for_review'],
      $transitions,
      'article_workflow has 6 transitions.',
    );
  }

  public function testArticleBundleIsModerated(): void {
    $workflow = Workflow::load('article_workflow');
    $settings = $workflow->getTypePlugin()->getConfiguration();
    $this->assertArrayHasKey('node', $settings['entity_types']);
    $this->assertContains('article', $settings['entity_types']['node']);
  }

}
