<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\KanbanColumn\CreateKanbanColumnInput;
use App\Dto\KanbanColumn\KanbanColumnOutput;
use App\Dto\KanbanColumn\ReorderKanbanColumnsInput;
use App\Dto\KanbanColumn\UpdateKanbanColumnInput;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KanbanColumnProcessor implements ProcessorInterface
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

        if ($operation instanceof Post) {
            if ($data instanceof ReorderKanbanColumnsInput) {
                return $this->reorder($data, $user, $roomId);
            }
            return $this->create($data, $user, $roomId);
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $user, $roomId, $uriVariables['id']);
        }

        if ($operation instanceof Delete) {
            return $this->delete($user, $roomId, $uriVariables['id']);
        }

        return null;
    }

    private function create(CreateKanbanColumnInput $data, User $user, int $roomId): KanbanColumnOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        $maxPosition = $this->entityManager->createQueryBuilder()
            ->select('MAX(kc.position)')
            ->from(KanbanColumn::class, 'kc')
            ->where('kc.room = :room')
            ->setParameter('room', $room)
            ->getQuery()
            ->getSingleScalarResult() ?? -1;

        $column = new KanbanColumn();
        $column->setRoom($room);
        $column->setName($data->name);
        $column->setColor($data->color);
        $column->setPosition($maxPosition + 1);

        $this->entityManager->persist($column);
        $this->entityManager->flush();

        return KanbanColumnOutput::fromEntity($column);
    }

    private function update(UpdateKanbanColumnInput $data, User $user, int $roomId, int $columnId): KanbanColumnOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        $column = $this->entityManager->getRepository(KanbanColumn::class)->find($columnId);

        if (!$column || $column->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException(ErrorMessage::KANBAN_COLUMN_NOT_FOUND);
        }

        if ($data->name !== null) {
            $column->setName($data->name);
        }
        if ($data->color !== null) {
            $column->setColor($data->color);
        }

        $this->entityManager->flush();

        return KanbanColumnOutput::fromEntity($column);
    }

    private function delete(User $user, int $roomId, int $columnId): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        $column = $this->entityManager->getRepository(KanbanColumn::class)->find($columnId);

        if (!$column || $column->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException(ErrorMessage::KANBAN_COLUMN_NOT_FOUND);
        }

        // Refuse deletion if active tasks are present
        $activeTaskCount = $this->entityManager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from(Task::class, 't')
            ->where('t.column = :column')
            ->andWhere('t.type = :active')
            ->setParameter('column', $column)
            ->setParameter('active', Task::TYPE_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult();

        if ($activeTaskCount > 0) {
            throw new BadRequestHttpException(ErrorMessage::KANBAN_COLUMN_HAS_TASKS);
        }

        $this->entityManager->remove($column);
        $this->entityManager->flush();

        return null;
    }

    private function reorder(ReorderKanbanColumnsInput $data, User $user, int $roomId): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        foreach ($data->columns as $columnData) {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->find($columnData['id']);
            if ($column && $column->getRoom()->getId() === $roomId) {
                $column->setPosition($columnData['position']);
            }
        }

        $this->entityManager->flush();

        return null;
    }
}
