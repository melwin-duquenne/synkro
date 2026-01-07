<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Room\RoomOutput;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RoomProvider implements ProviderInterface
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
            throw new AccessDeniedHttpException('Not authenticated');
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollection($user);
        }

        return $this->getItem($user, $uriVariables['id']);
    }

    private function getCollection(User $user): array
    {
        $entreprise = $user->getEntreprise();

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

        return array_map(fn(Room $room) => RoomOutput::fromEntity($room), $rooms);
    }

    private function getItem(User $user, int $id): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException('Room not found');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Access denied');
        }

        return RoomOutput::fromEntity($room, true);
    }
}
