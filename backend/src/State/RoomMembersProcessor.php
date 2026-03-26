<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Room\ManageRoomMembersInput;
use App\Dto\Room\RoomMemberOutput;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\UserRoomPermission;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomMembersProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        $roomId = $uriVariables['id'] ?? null;
        if (!$roomId) {
            throw new NotFoundHttpException('Identifiant du salon requis');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canManageMembers($user, $room)) {
            throw new AccessDeniedHttpException('Vous n\'avez pas la permission de gérer les membres de cette room');
        }

        if ($room->getVisibility() !== Room::VISIBILITY_PRIVATE) {
            throw new BadRequestHttpException('Les membres ne peuvent être gérés que pour les rooms privées');
        }

        $creatorId = $room->getCreator()->getId();

        // Remove users
        if ($data instanceof ManageRoomMembersInput && !empty($data->removeUserIds)) {
            foreach ($data->removeUserIds as $userIdToRemove) {
                // Cannot remove creator
                if ($userIdToRemove === $creatorId) {
                    continue;
                }

                foreach ($room->getUserPermissions() as $permission) {
                    if ($permission->getUser()->getId() === $userIdToRemove) {
                        $this->entityManager->remove($permission);
                        break;
                    }
                }
            }
        }

        // Add users
        if ($data instanceof ManageRoomMembersInput && !empty($data->addUserIds)) {
            $userRepo = $this->entityManager->getRepository(User::class);
            $existingUserIds = [];
            foreach ($room->getUserPermissions() as $permission) {
                $existingUserIds[] = $permission->getUser()->getId();
            }

            foreach ($data->addUserIds as $userIdToAdd) {
                // Skip if already has permission
                if (in_array($userIdToAdd, $existingUserIds)) {
                    continue;
                }

                $memberToAdd = $userRepo->find($userIdToAdd);
                // Only add members from the same enterprise
                if ($memberToAdd && $memberToAdd->getEntreprise() === $room->getEntreprise()) {
                    $permission = new UserRoomPermission();
                    $permission->setRoom($room);
                    $permission->setUser($memberToAdd);
                    $this->entityManager->persist($permission);
                }
            }
        }

        $this->entityManager->flush();

        // Refresh room to get updated permissions
        $this->entityManager->refresh($room);

        // Return updated members list
        $members = [];
        $members[] = RoomMemberOutput::fromEntity($room->getCreator(), true);

        foreach ($room->getUserPermissions() as $permission) {
            $permUser = $permission->getUser();
            if ($permUser->getId() !== $creatorId) {
                $members[] = RoomMemberOutput::fromEntity($permUser, false);
            }
        }

        return $members;
    }
}
