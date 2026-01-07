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
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
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
            throw new AccessDeniedHttpException('Not authenticated');
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
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        // Get max position for the status column
        $maxPosition = $this->entityManager->createQueryBuilder()
            ->select('MAX(t.position)')
            ->from(Task::class, 't')
            ->where('t.room = :room')
            ->andWhere('t.status = :status')
            ->setParameter('room', $room)
            ->setParameter('status', $data->status)
            ->getQuery()
            ->getSingleScalarResult() ?? -1;

        $task = new Task();
        $task->setRoom($room);
        $task->setTitle($data->title);
        $task->setDescription($data->description);
        $task->setStatus($data->status);
        $task->setPosition($maxPosition + 1);

        if ($data->assignedToId) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($data->assignedToId);
            if ($assignedTo) {
                $task->setAssignedTo($assignedTo);
            }
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }

    private function update(UpdateTaskInput $data, User $user, int $roomId, int $taskId): TaskOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || $task->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Task not found');
        }

        if ($data->title !== null) {
            $task->setTitle($data->title);
        }
        if ($data->description !== null) {
            $task->setDescription($data->description);
        }
        if ($data->status !== null) {
            $task->setStatus($data->status);
        }
        if ($data->position !== null) {
            $task->setPosition($data->position);
        }
        if ($data->assignedToId !== null) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($data->assignedToId);
            $task->setAssignedTo($assignedTo);
        }

        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }

    private function delete(User $user, int $roomId, int $taskId): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || $task->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException('Task not found');
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return null;
    }

    private function reorder(ReorderTasksInput $data, User $user, int $roomId): object
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        foreach ($data->tasks as $taskData) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskData['id']);
            if ($task && $task->getRoom()->getId() === $roomId) {
                $task->setStatus($taskData['status']);
                $task->setPosition($taskData['position']);
            }
        }

        $this->entityManager->flush();

        return new class {
            public string $message = 'Tasks reordered successfully';
        };
    }
}
