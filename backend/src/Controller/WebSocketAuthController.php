<?php

namespace App\Controller;

use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

class WebSocketAuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private RoomAccessChecker $accessChecker,
    ) {}

    /**
     * Appelé par le serveur WebSocket (Node) pour savoir si l'utilisateur
     * porteur du JWT peut accéder à la room donnée.
     */
    #[Route(
        '/api/ws/authorize/{roomId}',
        name: 'ws_authorize',
        methods: ['GET'],
        requirements: ['roomId' => '\d+'],
    )]
    public function authorize(int $roomId, #[CurrentUser] ?User $user): JsonResponse
    {
        // Le firewall ^/api renvoie déjà 401 si le token est absent/invalide ;
        // cette garde couvre le cas défensif + permet le test unitaire.
        if (!$user instanceof User) {
            return new JsonResponse(['error' => 'Unauthorized'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $room = $this->em->getRepository(Room::class)->find($roomId);
        if (!$room instanceof Room) {
            return new JsonResponse(['error' => 'Room not found'], JsonResponse::HTTP_NOT_FOUND);
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            return new JsonResponse(['error' => 'Forbidden'], JsonResponse::HTTP_FORBIDDEN);
        }

        return new JsonResponse([
            'userId' => $user->getId(),
            'displayName' => $user->getDisplayName(),
        ]);
    }
}
