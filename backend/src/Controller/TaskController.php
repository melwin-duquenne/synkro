<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/rooms/{roomId}/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(int $roomId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $tasks = $this->entityManager->getRepository(Task::class)->findBy(
            ['room' => $room],
            ['position' => 'ASC']
        );

        $data = array_map(fn(Task $task) => $this->serializeTask($task), $tasks);

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_tasks_create', methods: ['POST'])]
    public function create(int $roomId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['title'])) {
            return new JsonResponse(['error' => 'Title is required'], Response::HTTP_BAD_REQUEST);
        }

        // Get max position for the status column
        $maxPosition = $this->entityManager->createQueryBuilder()
            ->select('MAX(t.position)')
            ->from(Task::class, 't')
            ->where('t.room = :room')
            ->andWhere('t.status = :status')
            ->setParameter('room', $room)
            ->setParameter('status', $data['status'] ?? Task::STATUS_TODO)
            ->getQuery()
            ->getSingleScalarResult() ?? -1;

        $task = new Task();
        $task->setRoom($room);
        $task->setTitle($data['title']);
        $task->setDescription($data['description'] ?? null);
        $task->setStatus($data['status'] ?? Task::STATUS_TODO);
        $task->setPosition($maxPosition + 1);

        if (!empty($data['assignedToId'])) {
            $assignedTo = $this->entityManager->getRepository(User::class)->find($data['assignedToId']);
            if ($assignedTo) {
                $task->setAssignedTo($assignedTo);
            }
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return new JsonResponse($this->serializeTask($task), Response::HTTP_CREATED);
    }

    #[Route('/{taskId}', name: 'api_tasks_get', methods: ['GET'])]
    public function get(int $roomId, int $taskId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getRoom()->getId() !== $roomId) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse($this->serializeTask($task));
    }

    #[Route('/{taskId}', name: 'api_tasks_update', methods: ['PUT', 'PATCH'])]
    public function update(int $roomId, int $taskId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getRoom()->getId() !== $roomId) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) {
            $task->setTitle($data['title']);
        }
        if (array_key_exists('description', $data)) {
            $task->setDescription($data['description']);
        }
        if (isset($data['status'])) {
            $task->setStatus($data['status']);
        }
        if (isset($data['position'])) {
            $task->setPosition($data['position']);
        }
        if (array_key_exists('assignedToId', $data)) {
            if ($data['assignedToId']) {
                $assignedTo = $this->entityManager->getRepository(User::class)->find($data['assignedToId']);
                $task->setAssignedTo($assignedTo);
            } else {
                $task->setAssignedTo(null);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse($this->serializeTask($task));
    }

    #[Route('/{taskId}', name: 'api_tasks_delete', methods: ['DELETE'])]
    public function delete(int $roomId, int $taskId): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $task = $this->entityManager->getRepository(Task::class)->find($taskId);
        if (!$task || $task->getRoom()->getId() !== $roomId) {
            return new JsonResponse(['error' => 'Task not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($task);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/reorder', name: 'api_tasks_reorder', methods: ['POST'])]
    public function reorder(int $roomId, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($roomId);
        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['tasks']) || !is_array($data['tasks'])) {
            return new JsonResponse(['error' => 'Tasks array is required'], Response::HTTP_BAD_REQUEST);
        }

        foreach ($data['tasks'] as $taskData) {
            $task = $this->entityManager->getRepository(Task::class)->find($taskData['id']);
            if ($task && $task->getRoom()->getId() === $roomId) {
                $task->setStatus($taskData['status']);
                $task->setPosition($taskData['position']);
            }
        }

        $this->entityManager->flush();

        return new JsonResponse(['message' => 'Tasks reordered successfully']);
    }

    private function canAccessRoom($user, Room $room): bool
    {
        if ($room->getCreator() === $user) {
            return true;
        }

        if ($room->getVisibility() === Room::VISIBILITY_ENTERPRISE) {
            return $user->getEntreprise() === $room->getEntreprise();
        }

        foreach ($room->getUserPermissions() as $permission) {
            if ($permission->getUser() === $user) {
                return true;
            }
        }

        if ($user->getTeam()) {
            foreach ($room->getTeamPermissions() as $permission) {
                if ($permission->getTeam() === $user->getTeam()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function serializeTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'position' => $task->getPosition(),
            'assignedTo' => $task->getAssignedTo() ? [
                'id' => $task->getAssignedTo()->getId(),
                'displayName' => $task->getAssignedTo()->getDisplayName()
            ] : null,
            'createdAt' => $task->getCreatedAt()->format('c')
        ];
    }
}
