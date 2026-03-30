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
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollection($user);
        }

        return $this->getItem($user, $uriVariables['id']);
    }

    private function getCollection(User $user): array
    {
        $entreprise = $user->getEntreprise();

        if (!$entreprise) {
            return [];
        }

        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('DISTINCT r')
            ->from(Room::class, 'r')
            ->leftJoin('r.userPermissions', 'up')
            ->leftJoin('r.teamPermissions', 'tp')
            ->where('r.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        // Admin sees all rooms in the enterprise
        if ($user->getRole() === User::ROLE_ADMIN) {
            // No additional filter needed
        } else {
            // Others see: enterprise rooms OR rooms they created OR rooms they have permission to
            $qb->andWhere(
                $qb->expr()->orX(
                    'r.visibility = :enterprise',
                    'r.creator = :user',
                    'up.user = :user',
                    $user->getTeam() ? 'tp.team = :team' : '1=0'
                )
            )
            ->setParameter('enterprise', Room::VISIBILITY_ENTERPRISE)
            ->setParameter('user', $user);

            if ($user->getTeam()) {
                $qb->setParameter('team', $user->getTeam());
            }
        }

        $rooms = $qb->orderBy('r.createdAt', 'DESC')->getQuery()->getResult();

        return array_map(fn(Room $room) => RoomOutput::fromEntity($room), $rooms);
    }

    private function getItem(User $user, int $id): RoomOutput
    {
        $room = $this->entityManager->getRepository(Room::class)->find($id);

        if (!$room) {
            throw new NotFoundHttpException('Salon introuvable');
        }

        if (!$this->accessChecker->canAccess($user, $room)) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        return RoomOutput::fromEntity($room, true);
    }
}
