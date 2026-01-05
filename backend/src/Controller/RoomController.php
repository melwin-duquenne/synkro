<?php

namespace App\Controller;

use App\Entity\Module;
use App\Entity\ModuleRoom;
use App\Entity\Room;
use App\Entity\UserRoomPermission;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/rooms')]
class RoomController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    #[Route('', name: 'api_rooms_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $entreprise = $user->getEntreprise();

        // Get rooms the user can access
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('r')
            ->from(Room::class, 'r')
            ->leftJoin('r.moduleRooms', 'mr')
            ->leftJoin('mr.module', 'm')
            ->where('r.creator = :user')
            ->setParameter('user', $user);

        if ($entreprise) {
            $qb->orWhere('r.entreprise = :entreprise AND r.visibility = :enterprise')
                ->setParameter('entreprise', $entreprise)
                ->setParameter('enterprise', Room::VISIBILITY_ENTERPRISE);
        }

        $rooms = $qb->orderBy('r.createdAt', 'DESC')->getQuery()->getResult();

        $data = array_map(fn(Room $room) => $this->serializeRoom($room), $rooms);

        return new JsonResponse($data);
    }

    #[Route('', name: 'api_rooms_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (empty($data['name'])) {
            return new JsonResponse(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        if (empty($data['modules']) || !is_array($data['modules'])) {
            return new JsonResponse(['error' => 'At least one module is required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if user has an entreprise
        if (!$user->getEntreprise()) {
            return new JsonResponse([
                'error' => 'Vous devez être associé à une entreprise pour créer une room'
            ], Response::HTTP_BAD_REQUEST);
        }

        $room = new Room();
        $room->setName($data['name']);
        $room->setCreator($user);
        $room->setVisibility($data['visibility'] ?? Room::VISIBILITY_ENTERPRISE);
        $room->setIsTemporary($data['isTemporary'] ?? false);
        $room->setEntreprise($user->getEntreprise());

        $this->entityManager->persist($room);

        // Add modules to room
        $moduleRepo = $this->entityManager->getRepository(Module::class);
        foreach ($data['modules'] as $moduleCode) {
            $module = $moduleRepo->findOneBy(['code' => $moduleCode]);
            if ($module) {
                $moduleRoom = new ModuleRoom();
                $moduleRoom->setRoom($room);
                $moduleRoom->setModule($module);
                $this->entityManager->persist($moduleRoom);
            }
        }

        // Give owner permission to creator
        $permission = new UserRoomPermission();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setRole(UserRoomPermission::ROLE_OWNER);
        $this->entityManager->persist($permission);

        $this->entityManager->flush();

        return new JsonResponse($this->serializeRoom($room), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_rooms_get', methods: ['GET'])]
    public function get(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        // Check access
        if (!$this->canAccessRoom($user, $room)) {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse($this->serializeRoom($room, true));
    }

    #[Route('/{id}', name: 'api_rooms_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], Response::HTTP_UNAUTHORIZED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            return new JsonResponse(['error' => 'Room not found'], Response::HTTP_NOT_FOUND);
        }

        // Only creator or admin can delete
        if ($room->getCreator() !== $user && $user->getRole() !== 'admin') {
            return new JsonResponse(['error' => 'Access denied'], Response::HTTP_FORBIDDEN);
        }

        $this->entityManager->remove($room);
        $this->entityManager->flush();

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }

    private function canAccessRoom($user, Room $room): bool
    {
        // Creator always has access
        if ($room->getCreator() === $user) {
            return true;
        }

        // Check enterprise visibility
        if ($room->getVisibility() === Room::VISIBILITY_ENTERPRISE) {
            return $user->getEntreprise() === $room->getEntreprise();
        }

        // Check private room permissions
        foreach ($room->getUserPermissions() as $permission) {
            if ($permission->getUser() === $user) {
                return true;
            }
        }

        // Check team permissions
        if ($user->getTeam()) {
            foreach ($room->getTeamPermissions() as $permission) {
                if ($permission->getTeam() === $user->getTeam()) {
                    return true;
                }
            }
        }

        return false;
    }

    private function serializeRoom(Room $room, bool $detailed = false): array
    {
        $data = [
            'id' => $room->getId(),
            'name' => $room->getName(),
            'visibility' => $room->getVisibility(),
            'isTemporary' => $room->isTemporary(),
            'createdAt' => $room->getCreatedAt()->format('c'),
            'creator' => [
                'id' => $room->getCreator()->getId(),
                'displayName' => $room->getCreator()->getDisplayName()
            ],
            'moduleRooms' => array_map(fn(ModuleRoom $mr) => [
                'id' => $mr->getId(),
                'module' => [
                    'id' => $mr->getModule()->getId(),
                    'code' => $mr->getModule()->getCode(),
                    'name' => $mr->getModule()->getName()
                ],
                'configJson' => $mr->getConfigJson()
            ], $room->getModuleRooms()->toArray())
        ];

        if ($detailed) {
            $data['entreprise'] = $room->getEntreprise() ? [
                'id' => $room->getEntreprise()->getId(),
                'name' => $room->getEntreprise()->getName()
            ] : null;
        }

        return $data;
    }
}
