<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Task\TaskOutput;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class TaskProvider implements ProviderInterface
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

        // For collection operations (GET /rooms/{roomId}/tasks)
        if ($operation instanceof CollectionOperationInterface) {
            $roomId = $uriVariables['roomId'] ?? null;
            if (!$roomId) {
                throw new BadRequestHttpException('roomId is required');
            }
            return $this->getCollection($user, $roomId);
        }

        // For item operations (GET /rooms/{roomId}/tasks/{id})
        $roomId = $uriVariables['roomId'] ?? null;
        $taskId = $uriVariables['id'] ?? null;

        return $this->getItem($user, $roomId, $taskId);
    }

    private function getCollection(User $user, int $roomId): array
    {
        $room = $this->entityManager->getRepository(Room::class)->find($roomId);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        $tasks = $this->entityManager->getRepository(Task::class)->findBy(
            ['room' => $room],
            ['position' => 'ASC']
        );

        return array_map(fn(Task $task) => TaskOutput::fromEntity($task), $tasks);
    }

    private function getItem(User $user, int $roomId, int $taskId): TaskOutput
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

        return TaskOutput::fromEntity($task);
    }
}
