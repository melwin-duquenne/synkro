<?php

namespace App\Tests\Unit\Controller;

use App\Controller\WebSocketAuthController;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;

class WebSocketAuthControllerTest extends TestCase
{
    private function makeController(?Room $room, RoomAccessChecker $checker): WebSocketAuthController
    {
        $repo = $this->createStub(EntityRepository::class);
        $repo->method('find')->willReturn($room);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repo);

        return new WebSocketAuthController($em, $checker);
    }

    public function testReturns401WhenNoUser(): void
    {
        $controller = $this->makeController(null, $this->createStub(RoomAccessChecker::class));

        $response = $controller->authorize(42, null);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function testReturns404WhenRoomMissing(): void
    {
        $controller = $this->makeController(null, $this->createStub(RoomAccessChecker::class));

        $response = $controller->authorize(42, new User());

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testReturns403WhenAccessDenied(): void
    {
        $checker = $this->createStub(RoomAccessChecker::class);
        $checker->method('canAccess')->willReturn(false);

        $controller = $this->makeController(new Room(), $checker);

        $response = $controller->authorize(42, new User());

        $this->assertSame(403, $response->getStatusCode());
    }

    public function testReturns200WithUserInfoWhenAllowed(): void
    {
        $checker = $this->createStub(RoomAccessChecker::class);
        $checker->method('canAccess')->willReturn(true);

        $user = new User();
        $user->setDisplayName('Alice');

        $controller = $this->makeController(new Room(), $checker);

        $response = $controller->authorize(42, $user);

        $this->assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertSame('Alice', $data['displayName']);
        $this->assertArrayHasKey('userId', $data);
    }
}
