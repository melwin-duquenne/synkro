<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Whiteboard\WhiteboardOutput;
use App\Dto\Whiteboard\UpdateWhiteboardInput;
use App\Entity\Whiteboard;
use App\Entity\Room;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WhiteboardProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if (!$roomId) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_ID_REQUIRED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $this->update($data, $room);
        }

        return null;
    }

    private function update(UpdateWhiteboardInput $data, Room $room): WhiteboardOutput
    {
        // Get or create whiteboard
        $whiteboard = $this->entityManager->getRepository(Whiteboard::class)->findOneBy(['room' => $room]);

        if (!$whiteboard) {
            $whiteboard = new Whiteboard();
            $whiteboard->setRoom($room);
        }

        if ($data->strokes !== null) {
            $whiteboard->setStrokes($data->strokes);
        }

        $this->entityManager->persist($whiteboard);
        $this->entityManager->flush();

        return WhiteboardOutput::fromEntity($whiteboard);
    }
}
