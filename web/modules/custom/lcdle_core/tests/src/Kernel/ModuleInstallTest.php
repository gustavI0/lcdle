<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group lcdle_core
 */
final class ModuleInstallTest extends KernelTestBase {

  protected static $modules = ['lcdle_core'];

  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_core'),
      'lcdle_core module is enabled.',
    );
  }

}
