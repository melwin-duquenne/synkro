<?php

namespace App\UseCase\Task;

use App\Dto\Task\TaskOutput;
use App\Dto\Task\UpdateTaskInput;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateTaskUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(UpdateTaskInput $input, int $roomId, int $taskId, User $user): TaskOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);

        if (!$task || $task->getRoom()->getId() !== $roomId) {
            throw new NotFoundHttpException(ErrorMessage::TASK_NOT_FOUND);
        }

        if ($input->title !== null) {
            $task->setTitle($input->title);
        }
        if ($input->description !== null) {
            $task->setDescription($input->description);
        }
        if ($input->columnId !== null) {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->find($input->columnId);
            if (!$column || $column->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException(ErrorMessage::KANBAN_COLUMN_INVALID);
            }
            $task->setColumn($column);
        }
        if ($input->position !== null) {
            $task->setPosition($input->position);
        }
        if ($input->assignedToId !== null) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($input->assignedToId);
            $task->setAssignedTo($assignedTo);
        }
        if ($input->estimation !== null) {
            $task->setEstimation($input->estimation);
        }
        if ($input->type !== null) {
            $task->setType($input->type);
        }

        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }
}
