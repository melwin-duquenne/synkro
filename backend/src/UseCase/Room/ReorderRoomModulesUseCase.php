<?php

namespace App\UseCase\Room;

use App\Dto\Room\RoomOutput;
use App\Entity\Room;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReorderRoomModulesUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(int $id, User $user, ?Request $request): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ROOM_EDIT_DENIED);
        }

        $moduleOrder = [];
        if ($request) {
            $content = $request->getContent();
            $requestData = json_decode($content, true) ?? [];
            $moduleOrder = $requestData['moduleOrder'] ?? [];
        }

        foreach ($room->getModuleRooms() as $moduleRoom) {
            $position = array_search($moduleRoom->getId(), $moduleOrder);
            if ($position !== false) {
                $moduleRoom->setDisplayOrder($position);
            }
        }

        $this->entityManager->flush();

        return RoomOutput::fromEntity($room);
    }
}
