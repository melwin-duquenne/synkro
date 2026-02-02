<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Room\CreateRoomInput;
use App\Dto\Room\RoomOutput;
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

        if ($operation instanceof Post) {
            return $this->create($data, $user);
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

        // Give owner permission to creator
        $permission = new UserRoomPermission();
        $permission->setRoom($room);
        $permission->setUser($user);
        $permission->setRole(UserRoomPermission::ROLE_OWNER);
        $this->entityManager->persist($permission);

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
}
