<?php

namespace App\UseCase\Task;

use App\Dto\Task\ReorderTasksInput;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReorderTasksUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(ReorderTasksInput $input, int $roomId, User $user): object
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        foreach ($input->tasks as $taskData) {
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
