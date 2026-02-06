<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Room\RoomMemberOutput;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomMembersProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $roomId = $uriVariables['id'] ?? null;
        if (!$roomId) {
            throw new NotFoundHttpException('Room ID required');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $members = [];
        $creatorId = $room->getCreator()->getId();

        // Add creator first
        $members[] = RoomMemberOutput::fromEntity($room->getCreator(), true);

        // Add other members from permissions
        foreach ($room->getUserPermissions() as $permission) {
            $permUser = $permission->getUser();
            if ($permUser->getId() !== $creatorId) {
                $members[] = RoomMemberOutput::fromEntity($permUser, false);
            }
        }

        return $members;
    }
}
