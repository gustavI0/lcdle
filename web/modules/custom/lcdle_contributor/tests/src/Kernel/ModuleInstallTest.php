<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

/**
 * Tests that the lcdle_contributor module installs successfully.
 *
 * @group lcdle_contributor
 */
final class ModuleInstallTest extends LcdleContributorKernelTestBase {

  /**
   * Tests the module is enabled after installation.
   */
  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_contributor'),
      'lcdle_contributor module is enabled.',
    );
  }

}
