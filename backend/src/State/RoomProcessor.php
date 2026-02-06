<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Room\CreateRoomInput;
use App\Dto\Room\RoomOutput;
use App\Entity\KanbanColumn;
use App\Entity\Module;
use App\Entity\ModuleRoom;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\UserRoomPermission;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomProcessor implements ProcessorInterface
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

        // Handle custom operations via context FIRST before checking operation type
        $request = $context['request'] ?? null;
        if ($request && str_contains($request->getPathInfo(), '/reorder-modules')) {
            return $this->reorderModules($data, $uriVariables['id'], $user, $context);
        }

        if ($operation instanceof Post) {
            return $this->create($data, $user);
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $uriVariables['id'], $user);
        }

        if ($operation instanceof Delete) {
            return $this->delete($uriVariables['id'], $user);
        }

        return null;
    }

    private function create(CreateRoomInput $data, User $user): RoomOutput
    {
        if (!$user->getEntreprise()) {
            throw new BadRequestHttpException('Vous devez être associé à une entreprise pour créer une room');
        }

        // Check user has editor role or higher
        if (!$this->accessChecker->canCreateRoom($user)) {
            throw new AccessDeniedHttpException('Vous devez avoir le rôle éditeur ou supérieur pour créer une room');
        }

        $room = new Room();
        $room->setName($data->name);
        $room->setCreator($user);
        $room->setVisibility($data->visibility);
        $room->setIsTemporary($data->isTemporary);
        $room->setEntreprise($user->getEntreprise());

        $this->entityManager->persist($room);

        // Add modules to room
        $moduleRepo = $this->entityManager->getRepository(Module::class);
        foreach ($data->modules as $moduleCode) {
            $module = $moduleRepo->findOneBy(['code' => $moduleCode]);
            if ($module) {
                $moduleRoom = new ModuleRoom();
                $moduleRoom->setRoom($room);
                $moduleRoom->setModule($module);
                $this->entityManager->persist($moduleRoom);
            }
        }

        // Create permission for creator
        $creatorPermission = new UserRoomPermission();
        $creatorPermission->setRoom($room);
        $creatorPermission->setUser($user);
        $this->entityManager->persist($creatorPermission);

        // If private room, add permissions for invited members
        if ($data->visibility === Room::VISIBILITY_PRIVATE && !empty($data->memberIds)) {
            $userRepo = $this->entityManager->getRepository(User::class);
            foreach ($data->memberIds as $memberId) {
                // Skip if it's the creator (already has permission)
                if ($memberId === $user->getId()) {
                    continue;
                }

                $member = $userRepo->find($memberId);
                // Only add members from the same enterprise
                if ($member && $member->getEntreprise() === $user->getEntreprise()) {
                    $memberPermission = new UserRoomPermission();
                    $memberPermission->setRoom($room);
                    $memberPermission->setUser($member);
                    $this->entityManager->persist($memberPermission);
                }
            }
        }

        // Create default kanban columns if tasks module is enabled
        if (in_array('tasks', $data->modules)) {
            $defaultColumns = [
                ['name' => 'À faire', 'color' => 'bg-slate-500', 'position' => 0],
                ['name' => 'En cours', 'color' => 'bg-blue-500', 'position' => 1],
                ['name' => 'Terminé', 'color' => 'bg-green-500', 'position' => 2],
            ];

            foreach ($defaultColumns as $colData) {
                $kanbanColumn = new KanbanColumn();
                $kanbanColumn->setRoom($room);
                $kanbanColumn->setName($colData['name']);
                $kanbanColumn->setColor($colData['color']);
                $kanbanColumn->setPosition($colData['position']);
                $this->entityManager->persist($kanbanColumn);
            }
        }

        $this->entityManager->flush();

        return RoomOutput::fromEntity($room);
    }

    private function update(mixed $data, int $id, User $user): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException('La room n\'a pas été trouvée');
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new AccessDeniedHttpException('Vous n\'avez pas la permission de modifier cette room');
        }

        // Update name if provided
        if (isset($data->name) && $data->name !== null) {
            $room->setName($data->name);
        }

        // Update layoutType if provided
        if (isset($data->layoutType) && $data->layoutType !== null) {
            $room->setLayoutType($data->layoutType);
        }

        // Handle visibility change
        if (isset($data->visibility) && $data->visibility !== null && $data->visibility !== $room->getVisibility()) {
            $oldVisibility = $room->getVisibility();
            $newVisibility = $data->visibility;

            $room->setVisibility($newVisibility);

            // If changing from enterprise to private, remove all permissions except creator
            if ($oldVisibility === Room::VISIBILITY_ENTERPRISE && $newVisibility === Room::VISIBILITY_PRIVATE) {
                foreach ($room->getUserPermissions() as $permission) {
                    if ($permission->getUser() !== $room->getCreator()) {
                        $this->entityManager->remove($permission);
                    }
                }
                // Remove team permissions too
                foreach ($room->getTeamPermissions() as $permission) {
                    $this->entityManager->remove($permission);
                }
            }
        }

        $this->entityManager->flush();

        return RoomOutput::fromEntity($room);
    }

    private function delete(int $id, User $user): null
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canDelete($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        $this->entityManager->remove($room);
        $this->entityManager->flush();

        return null;
    }

    private function reorderModules(mixed $data, int $id, User $user, array $context = []): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException('La room n\'a pas été trouvée');
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new AccessDeniedHttpException('Vous n\'avez pas la permission de modifier cette room');
        }

        // Get request body
        $request = $context['request'] ?? null;
        $requestData = [];
        if ($request) {
            $content = $request->getContent();
            $requestData = json_decode($content, true) ?? [];
        }

        $moduleOrder = $requestData['moduleOrder'] ?? [];

        foreach ($room->getModuleRooms() as $moduleRoom) {
            $position = array_search($moduleRoom->getId(), $moduleOrder);
            if ($position !== false) {
                $moduleRoom->setDisplayOrder($position);
            }
        }

        $this->entityManager->flush();

        return RoomOutput::fromEntity($room);
    }
}
