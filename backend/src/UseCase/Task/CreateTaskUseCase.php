<?php

namespace App\UseCase\Task;

use App\Dto\Task\CreateTaskInput;
use App\Dto\Task\TaskOutput;
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

class CreateTaskUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(CreateTaskInput $input, int $roomId, User $user): TaskOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ACCESS_DENIED);
        }

        $column = null;
        if ($input->columnId) {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->find($input->columnId);
            if (!$column || $column->getRoom()->getId() !== $roomId) {
                throw new BadRequestHttpException(ErrorMessage::KANBAN_COLUMN_INVALID);
            }
        } else {
            $column = $this->entityManager->getRepository(KanbanColumn::class)->findOneBy(
                ['room' => $room],
                ['position' => 'ASC']
            );
            if (!$column) {
                throw new BadRequestHttpException(ErrorMessage::KANBAN_NO_COLUMN);
            }
        }

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
        $task->setTitle($input->title);
        $task->setDescription($input->description);
        $task->setColumn($column);
        $task->setPosition($maxPosition + 1);

        if ($input->assignedToId) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($input->assignedToId);
            if ($assignedTo) {
                $task->setAssignedTo($assignedTo);
            }
        }

        if ($input->estimation !== null) {
            $task->setEstimation($input->estimation);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return TaskOutput::fromEntity($task);
    }
}
