<?php

namespace App\Tests\Unit\Entity;

use App\Entity\Room;
use PHPUnit\Framework\TestCase;

class RoomTest extends TestCase
{
    public function testIsPrivateReturnsTrueWhenVisibilityIsPrivate(): void
    {
        $room = new Room();
        $room->setVisibility(Room::VISIBILITY_PRIVATE);

        $this->assertTrue($room->isPrivate());
    }

    public function testIsPrivateReturnsFalseWhenVisibilityIsEnterprise(): void
    {
        $room = new Room();
        $room->setVisibility(Room::VISIBILITY_ENTERPRISE);

        $this->assertFalse($room->isPrivate());
    }
}
