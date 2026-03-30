<?php

namespace App\Tests\Unit\Security;

use App\Entity\Entreprise;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use PHPUnit\Framework\TestCase;

class RoomAccessCheckerTest extends TestCase
{
    public function testCanAccessReturnsFalseWhenUserIsFromDifferentEntreprise(): void
    {
        $entrepriseA = new Entreprise();
        $entrepriseB = new Entreprise();

        $user = new User();
        $user->setEntreprise($entrepriseA);

        $room = new Room();
        $room->setEntreprise($entrepriseB);

        $checker = new RoomAccessChecker();

        $this->assertFalse($checker->canAccess($user, $room));
    }

    public function testCanAccessReturnsTrueWhenRoomHasEnterpriseVisibilityAndSameEntreprise(): void
    {
        $entreprise = new Entreprise();

        $user = new User();
        $user->setRole('user');
        $user->setEntreprise($entreprise);

        $room = new Room();
        $room->setEntreprise($entreprise);
        $room->setVisibility(Room::VISIBILITY_ENTERPRISE);

        $checker = new RoomAccessChecker();

        $this->assertTrue($checker->canAccess($user, $room));
    }

    public function testCanManageMembersReturnsFalseWhenRoomVisibilityIsNotPrivate(): void
    {
        $entreprise = new Entreprise();

        $user = new User();
        $user->setRole('editor');
        $user->setEntreprise($entreprise);

        $room = new Room();
        $room->setEntreprise($entreprise);
        $room->setCreator($user);
        $room->setVisibility(Room::VISIBILITY_ENTERPRISE);

        $checker = new RoomAccessChecker();

        $this->assertFalse($checker->canManageMembers($user, $room));
    }
}
