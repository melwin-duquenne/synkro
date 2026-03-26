<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Task\CreateTaskInput;
use App\Dto\Task\ReorderTasksInput;
use App\Dto\Task\TaskOutput;
use App\Dto\Task\UpdateTaskInput;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskProcessor implements ProcessorInterface
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
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        $roomId = $uriVariables['roomId'] ?? null;

        if ($operation instanceof Post) {
            // Check if this is a reorder operation
            if ($data instanceof ReorderTasksInput) {
                return $this->reorder($data, $user, $roomId);
            }
            return $this->create($data, $user, $roomId);
        }

        if ($operation instanceof Put || $operation instanceof Patch) {
            return $this->update($data, $user, $roomId, $uriVariables['id']);
        }

        if ($operation instanceof Delete) {
            return $this->delete($user, $roomId, $uriVariables['id']);
        }

        return null;
    }

    private function create(CreateTaskInput $data, User $user, int $roomId): TaskOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        // Resolve column
        $column = null;
        if ($data->columnId) {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->find($data->columnId);
            if (!$column || $column->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException('Invalid column');
            }
        } else {
            // Default to first column
            $column = $this->entityManager->getRepository(KanbanColumn::class)->findOneBy(
                ['room' => $room],
                ['position' => 'ASC']
            );
            if (!$column) {
                throw new BadRequestHttpException('No kanban columns exist for this room');
            }
        }

        // Get max position for the column
        $maxPosition = $this->entityManager->createQueryBuilder()
            ->select('MAX(t.position)')
            ->from(Task::class, 't')
            ->where('t.column = :column')
            ->andWhere('t.type = :active')
            ->setParameter('column', $column)
            ->setParameter('active', Task::TYPE_ACTIVE)
            ->getQuery()
            ->getSingleScalarResult() ?? -1;

        $task = new Task();
        $task->setRoom($room);
        $task->setTitle($data->title);
        $task->setDescription($data->description);
        $task->setColumn($column);
        $task->setPosition($maxPosition + 1);

        if ($data->assignedToId) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($data->assignedToId);
            if ($assignedTo) {
                $task->setAssignedTo($assignedTo);
            }
        }

        if ($data->estimation !== null) {
            $task->setEstimation($data->estimation);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }

    private function update(UpdateTaskInput $data, User $user, int $roomId, int $taskId): TaskOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || $task->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Tâche introuvable');
        }

        if ($data->title !== null) {
            $task->setTitle($data->title);
        }
        if ($data->description !== null) {
            $task->setDescription($data->description);
        }
        if ($data->columnId !== null) {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->find($data->columnId);
            if (!$column || $column->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException('Invalid column');
            }
            $task->setColumn($column);
        }
        if ($data->position !== null) {
            $task->setPosition($data->position);
        }
        if ($data->assignedToId !== null) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($data->assignedToId);
            $task->setAssignedTo($assignedTo);
        }
        if ($data->estimation !== null) {
            $task->setEstimation($data->estimation);
        }
        if ($data->type !== null) {
            $task->setType($data->type);
        }

        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }

    private function delete(User $user, int $roomId, int $taskId): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || $task->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Tâche introuvable');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return null;
    }

    private function reorder(ReorderTasksInput $data, User $user, int $roomId): object
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        foreach ($data->tasks as $taskData) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskData['id']);
            if ($task && $task->getRoom()->getId() === $roomId) {
                $column = $this->entityManager->getRepository(KanbanColumn::class)->find($taskData['columnId']);
                if ($column && $column->getRoom()->getId() === $roomId) {
                    $task->setColumn($column);
                }
                $task->setPosition($taskData['position']);
            }
        }

        $this->entityManager->flush();

        return new class {
            public string $message = 'Tasks reordered successfully';
        };
    }
}
