<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testIsAtLeastReturnsFalseWhenUserRoleIsBelowRequired(): void
    {
        $user = new User();
        $user->setRole('user');

        $this->assertFalse($user->isAtLeast('editor'));
    }

    public function testIsAtLeastReturnsTrueWhenUserRoleMatchesOrExceeds(): void
    {
        $user = new User();
        $user->setRole('owner');

        $this->assertTrue($user->isAtLeast('editor'));
    }

    public function testCanAssignRoleOwnerCannotAssignAdminRole(): void
    {
        $user = new User();
        $user->setRole('owner');

        $this->assertFalse($user->canAssignRole('admin'));
    }
}
