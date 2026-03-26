<?php

namespace App\Controller;

use App\Entity\Room;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class RoomModuleOrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    #[Route('/api/rooms/{id}/reorder-modules', name: 'api_room_reorder_modules', methods: ['POST'])]
    public function reorderModules(int $id, Request $request): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $room = $this->entityManager->getRepository(Room::class)->find($id);
        if (!$room) {
            throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new AccessDeniedHttpException(ErrorMessage::ROOM_EDIT_DENIED);
        }

        $data = json_decode($request->getContent(), true);
        $moduleOrder = $data['moduleOrder'] ?? [];

        if (empty($moduleOrder)) {
            throw new BadRequestHttpException(ErrorMessage::INVALID_DATA);
        }

        // Update displayOrder for each module
        foreach ($room->getModuleRooms() as $moduleRoom) {
            $position = array_search($moduleRoom->getId(), $moduleOrder);
            if ($position !== false) {
                $moduleRoom->setDisplayOrder($position);
            }
        }

        $this->entityManager->flush();

        // Return updated room data
        return $this->json([
            'id' => $room->getId(),
            'name' => $room->getName(),
            'visibility' => $room->getVisibility(),
            'isTemporary' => $room->isTemporary(),
            'createdAt' => $room->getCreatedAt()->format('c'),
            'layoutType' => $room->getLayoutType(),
            'creator' => [
                'id' => $room->getCreator()->getId(),
                'displayName' => $room->getCreator()->getDisplayName()
            ],
            'moduleRooms' => array_map(function($mr) {
                return [
                    'id' => $mr->getId(),
                    'displayOrder' => $mr->getDisplayOrder(),
                    'module' => [
                        'id' => $mr->getModule()->getId(),
                        'name' => $mr->getModule()->getName(),
                        'code' => $mr->getModule()->getCode()
                    ]
                ];
            }, $room->getModuleRooms()->toArray())
        ]);
    }
}
