<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\KanbanColumn\KanbanColumnOutput;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KanbanColumnProvider implements ProviderInterface
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

        if ($operation instanceof CollectionOperationInterface) {
            $roomId = $uriVariables['roomId'] ?? null;
            if (!$roomId) {
                throw new BadRequestHttpException('roomId is required');
            }
            return $this->getCollection($user, $roomId);
        }

        $roomId = $uriVariables['roomId'] ?? null;
        $columnId = $uriVariables['id'] ?? null;

        return $this->getItem($user, $roomId, $columnId);
    }

    private function getCollection(User $user, int $roomId): array
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $columns = $this->entityManager->getRepository(KanbanColumn::class)->findBy(
            ['room' => $room],
            ['position' => 'ASC']
        );

        return array_map(fn(KanbanColumn $col) => KanbanColumnOutput::fromEntity($col), $columns);
    }

    private function getItem(User $user, int $roomId, int $columnId): KanbanColumnOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $column = $this->entityManager->getRepository(KanbanColumn::class)->find($columnId);

        if (!$column || $column->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Kanban column not found');
        }

        return KanbanColumnOutput::fromEntity($column);
    }
}
