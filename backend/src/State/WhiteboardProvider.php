<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Whiteboard\WhiteboardOutput;
use App\Entity\Whiteboard;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WhiteboardProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if (!$roomId) {
            throw new NotFoundHttpException('Identifiant du salon requis');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        // Get or create whiteboard for this room
        $whiteboard = $this->entityManager->getRepository(Whiteboard::class)->findOneBy(['room' => $room]);

        if (!$whiteboard) {
            // Auto-create whiteboard for the room
            $whiteboard = new Whiteboard();
            $whiteboard->setRoom($room);
            $whiteboard->setStrokes([]);
            $this->entityManager->persist($whiteboard);
            $this->entityManager->flush();
        }

        return WhiteboardOutput::fromEntity($whiteboard);
    }
}
