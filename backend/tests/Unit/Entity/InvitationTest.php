<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Invitation;
use PHPUnit\Framework\TestCase;

class InvitationTest extends TestCase
{
    public function testIsExpiredReturnsTrueWhenExpiresAtIsInPast(): void
    {
        $invitation = new Invitation();
        $invitation->setExpiresAt(new \DateTime('-1 day'));

        $this->assertTrue($invitation->isExpired());
    }

    public function testIsExpiredReturnsFalseWhenExpiresAtIsInFuture(): void
    {
        $invitation = new Invitation();
        $invitation->setExpiresAt(new \DateTime('+1 day'));

        $this->assertFalse($invitation->isExpired());
    }
}
