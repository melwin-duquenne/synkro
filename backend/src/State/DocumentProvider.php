<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Document\DocumentOutput;
use App\Entity\Document;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentProvider implements ProviderInterface
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
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if (!$roomId) {
            throw new NotFoundHttpException('Room ID is required');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied to this room');
        }

        // Get or create document for this room
        $document = $this->entityManager->getRepository(Document::class)->findOneBy(['room' => $room]);

        if (!$document) {
            // Auto-create document for the room
            $document = new Document();
            $document->setRoom($room);
            $document->setContentHtml('');
            $this->entityManager->persist($document);
            $this->entityManager->flush();
        }

        return DocumentOutput::fromEntity($document);
    }
}
