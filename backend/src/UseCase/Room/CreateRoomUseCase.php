<?php

namespace App\UseCase\Room;

use App\Dto\Room\CreateRoomInput;
use App\Dto\Room\RoomOutput;
use App\Entity\KanbanColumn;
use App\Entity\Module;
use App\Entity\ModuleRoom;
use App\Entity\Room;
use App\Entity\User;
use App\Entity\UserRoomPermission;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateRoomUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(CreateRoomInput $input, User $user): RoomOutput
    {
        if (!$user->getEntreprise()) {
            throw new BadRequestHttpException(ErrorMessage::ROOM_CREATE_REQUIRES_ENTREPRISE);
        }

        if (!$this->accessChecker->canCreateRoom($user)) {
            throw new AccessDeniedHttpException(ErrorMessage::ROOM_CREATE_REQUIRES_EDITOR);
        }

        $room = new Room();
        $room->setName($input->name);
        $room->setCreator($user);
        $room->setVisibility($input->visibility);
        $room->setIsTemporary($input->isTemporary);
        $room->setEntreprise($user->getEntreprise());

        $this->entityManager->persist($room);

        $moduleRepo = $this->entityManager->getRepository(Module::class);
        foreach ($input->modules as $moduleCode) {
            $module = $moduleRepo->findOneBy(['code' => $moduleCode]);
            if ($module) {
                $moduleRoom = new ModuleRoom();
                $moduleRoom->setRoom($room);
                $moduleRoom->setModule($module);
                $this->entityManager->persist($moduleRoom);
            }
        }

        $creatorPermission = new UserRoomPermission();
        $creatorPermission->setRoom($room);
        $creatorPermission->setUser($user);
        $this->entityManager->persist($creatorPermission);

        if ($input->visibility === Room::VISIBILITY_PRIVATE && !empty($input->memberIds)) {
            $userRepo = $this->entityManager->getRepository(User::class);
            foreach ($input->memberIds as $memberId) {
                if ($memberId === $user->getId()) {
                    continue;
                }
                $member = $userRepo->find($memberId);
                if ($member && $member->getEntreprise() === $user->getEntreprise()) {
                    $memberPermission = new UserRoomPermission();
                    $memberPermission->setRoom($room);
                    $memberPermission->setUser($member);
                    $this->entityManager->persist($memberPermission);
                }
            }
        }

        if (in_array('tasks', $input->modules)) {
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
}
