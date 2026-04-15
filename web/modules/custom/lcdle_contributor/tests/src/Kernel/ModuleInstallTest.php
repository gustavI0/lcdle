<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_contributor
 */
final class ModuleInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_contributor'];

  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_contributor'),
      'lcdle_contributor module is enabled.',
    );
  }

}
