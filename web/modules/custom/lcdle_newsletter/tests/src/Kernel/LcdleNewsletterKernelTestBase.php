<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_newsletter\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Shared bootstrap for lcdle_newsletter kernel tests.
 */
abstract class LcdleNewsletterKernelTestBase extends KernelTestBase {

  /**
   * {@inheritdoc}
   *
   * @var list<string>
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'options',
    'lcdle_newsletter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('newsletter_subscriber');
    $this->installConfig(['system']);
  }

}
