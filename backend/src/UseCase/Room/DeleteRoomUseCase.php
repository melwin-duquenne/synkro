<?php

namespace App\UseCase\Room;

use App\Entity\Room;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DeleteRoomUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(int $id, User $user): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canDelete($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ROOM_DELETE_DENIED);
        }

        $this->entityManager->remove($room);
        $this->entityManager->flush();

        return null;
    }
}
