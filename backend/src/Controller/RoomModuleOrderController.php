<?php

namespace App\Controller;

use App\Entity\Room;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Routing\Annotation\Route;

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
            throw new HttpException(401, 'Vous devez être connecté');
        }

        $room = $this->entityManager->getRepository(Room::class)->find($id);
        if (!$room) {
            throw new HttpException(404, 'La room n\'a pas été trouvée');
        }

        if (!$this->accessChecker->canEdit($user, $room)) {
            throw new HttpException(403, 'Vous n\'avez pas la permission de modifier cette room');
        }

        $data = json_decode($request->getContent(), true);
        $moduleOrder = $data['moduleOrder'] ?? [];

        if (empty($moduleOrder)) {
            throw new HttpException(400, 'L\'ordre des modules est requis');
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
