<?php

declare(strict_types=1);

namespace Drupal\Tests\lcdle_contributor\Kernel;

use Drupal\profile\Entity\ProfileType;

/**
 * @group lcdle_contributor
 */
final class ProfileTypeInstallTest extends LcdleContributorKernelTestBase {

  public function testContributorProfileTypeExists(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertNotNull($type, 'Profile type contributor_profile exists.');
    $this->assertSame('Profil contributeur', $type->label());
  }

  public function testContributorProfileTypeIsMultiple(): void {
    $type = ProfileType::load('contributor_profile');
    $this->assertFalse(
      $type->allowsMultiple(),
      'contributor_profile allows only one profile per user.',
    );
  }

  public function testContributorProfileTypeIsRoleBound(): void {
    $type = ProfileType::load('contributor_profile');
    $roles = $type->getRoles();
    sort($roles);
    $this->assertSame(
      ['contributor_new', 'contributor_trusted', 'editor'],
      $roles,
      'contributor_profile is bound to the three editorial roles.',
    );
  }

}
