<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Document\DocumentOutput;
use App\Dto\Document\UpdateDocumentInput;
use App\Entity\Document;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class DocumentProcessor implements ProcessorInterface
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

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $this->update($data, $room);
        }

        return null;
    }

    private function update(UpdateDocumentInput $data, Room $room): DocumentOutput
    {
        // Get or create document
        $document = $this->entityManager->getRepository(Document::class)->findOneBy(['room' => $room]);

        if (!$document) {
            $document = new Document();
            $document->setRoom($room);
        }

        if ($data->contentHtml !== null) {
            $document->setContentHtml($data->contentHtml);
        }

        $this->entityManager->persist($document);
        $this->entityManager->flush();

        return DocumentOutput::fromEntity($document);
    }
}
