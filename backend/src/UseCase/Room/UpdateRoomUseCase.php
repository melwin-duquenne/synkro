<?php

namespace App\UseCase\Room;

use App\Dto\Room\RoomOutput;
use App\Entity\Room;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateRoomUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(mixed $input, int $id, User $user): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ROOM_EDIT_DENIED);
        }

        if (isset($input->name) && $input->name !== null) {
            $room->setName($input->name);
        }

        if (isset($input->layoutType) && $input->layoutType !== null) {
            $room->setLayoutType($input->layoutType);
        }

        if (isset($input->visibility) && $input->visibility !== null && $input->visibility !== $room->getVisibility()) {
            $oldVisibility = $room->getVisibility();
            $newVisibility = $input->visibility;

            $room->setVisibility($newVisibility);

            if ($oldVisibility === Room::VISIBILITY_ENTERPRISE && $newVisibility === Room::VISIBILITY_PRIVATE) {
                foreach ($room->getUserPermissions() as $permission) {
                    if ($permission->getUser() !== $room->getCreator()) {
                        $this->entityManager->remove($permission);
                    }
                }
                foreach ($room->getTeamPermissions() as $permission) {
                    $this->entityManager->remove($permission);
                }
            }
        }

        $this->entityManager->flush();

        return RoomOutput::fromEntity($room);
    }
}
