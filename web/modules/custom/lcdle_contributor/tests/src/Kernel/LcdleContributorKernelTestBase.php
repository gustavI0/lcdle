<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Shared bootstrap for lcdle_contributor kernel tests.
 *
 * Central place to list the modules and config imports every kernel test in
 * this module needs. New dependencies are added here once, not in each test.
 */
abstract class LcdleContributorKernelTestBase extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'node',
    'text',
    'field',
    'filter',
    'taxonomy',
    'file',
    'image',
    'link',
    'media',
    'media_library',
    'views',
    'path',
    'path_alias',
    'token',
    'pathauto',
    'workflows',
    'content_moderation',
    'profile',
    'lcdle_core',
    'lcdle_contributor',
  ];

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('profile');
    $this->installSchema('node', ['node_access']);
    $this->installConfig([
      'system',
      'field',
      'filter',
      'node',
      'user',
      'profile',
      'lcdle_core',
      'lcdle_contributor',
    ]);
  }

}
