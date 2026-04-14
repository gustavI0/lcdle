<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_core\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;

/**
 * @group lcdle_core
 */
final class RolesInstallTest extends KernelTestBase {

  protected static $modules = ['system', 'user', 'node', 'text', 'field', 'file', 'image', 'media', 'taxonomy', 'lcdle_core'];

  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['lcdle_core']);
  }

  public function testAllProjectRolesExist(): void {
    foreach (['reader', 'contributor_new', 'contributor_trusted', 'editor'] as $rid) {
      $role = Role::load($rid);
      $this->assertNotNull($role, "Role {$rid} is installed.");
    }
  }

}
