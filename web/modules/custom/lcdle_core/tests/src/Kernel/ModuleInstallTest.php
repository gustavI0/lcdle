<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests that lcdle_core installs without errors.
 *
 * @group lcdle_core
 */
final class ModuleInstallTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['lcdle_core'];

  /**
   * Tests the module is enabled after install.
   */
  public function testModuleIsInstallable(): void {
    $this->assertTrue(
      \Drupal::moduleHandler()->moduleExists('lcdle_core'),
      'lcdle_core module is enabled.',
    );
  }

}
