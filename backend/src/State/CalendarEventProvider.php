<?php

namespace App\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Calendar\CalendarEventOutput;
use App\Entity\CalendarEvent;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use App\Service\EntrepriseContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarEventProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RequestStack $requestStack,
        private RoomAccessChecker $accessChecker,
        private EntrepriseContext $entrepriseContext
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        if ($operation instanceof CollectionOperationInterface) {
            return $this->getCollection($user, $uriVariables, $context);
        }

        return $this->getItem($uriVariables['id'], $user);
    }

    private function getCollection(User $user, array $uriVariables, array $context): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $entreprise = $this->entrepriseContext->getEntreprise();
        $qb = $this->entityManager->createQueryBuilder();

        $qb->select('e')
            ->from(CalendarEvent::class, 'e')
            ->where('e.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise);

        // Filter by room (from URI or query param)
        $roomId = $uriVariables['roomId'] ?? $request?->query->get('roomId');
        if ($roomId !== null) {
            $room = $this->entityManager->getRepository(Room::class)->find($roomId);
            if (!$room) {
                throw new NotFoundHttpException('Salon introuvable');
            }
            if (!$this->accessChecker->canAccess($user, $room)) {
                throw new AccessDeniedHttpException('Accès refusé');
            }
            // Room calendar: show all public enterprise events (not just room-specific ones)
            // Privacy filter below handles hiding private events from other users
        }

        // Personal calendar: show personal events + room events where user is author or participant
        if ($request?->query->get('personal') === 'true') {
            $qb->leftJoin('e.participants', 'p')
                ->andWhere('(e.room IS NULL AND e.user = :user) OR e.user = :user OR p.user = :user')
                ->setParameter('user', $user);
        }

        // Filter by user
        $userId = $request?->query->get('userId');
        if ($userId !== null) {
            $qb->andWhere('e.user = :userId')
                ->setParameter('userId', $userId);
        }

        // Filter by event type
        $eventType = $request?->query->get('eventType');
        if ($eventType !== null) {
            $qb->andWhere('e.eventType = :eventType')
                ->setParameter('eventType', $eventType);
        }

        // Filter by date range
        $startDate = $request?->query->get('startDate');
        $endDate = $request?->query->get('endDate');
        if ($startDate !== null) {
            $qb->andWhere('e.endDate >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }
        if ($endDate !== null) {
            $qb->andWhere('e.startDate <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate));
        }

        // Hide private events from other users (unless in same room)
        $qb->andWhere('e.isPrivate = false OR e.user = :currentUser')
            ->setParameter('currentUser', $user);

        $qb->orderBy('e.startDate', 'ASC');

        $events = $qb->getQuery()->getResult();

        return array_map(
            fn(CalendarEvent $event) => CalendarEventOutput::fromEntity($event),
            $events
        );
    }

    private function getItem(int $id, User $user): CalendarEventOutput
    {
        $event = $this->entityManager->getRepository(CalendarEvent::class)->find($id);

        if (!$event) {
            throw new NotFoundHttpException('Ressource introuvable');
        }

        // Check access — user must be member of the event's enterprise
        if (!$user->hasEntreprise($event->getEntreprise())) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        // Check room access if event is linked to a room
        if ($event->getRoom() && !$this->accessChecker->canAccess($user, $event->getRoom())) {
            throw new AccessDeniedHttpException('Accès refusé');
        }

        // Check private event access
        if ($event->isPrivate() && $event->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('Cet événement est privé');
        }

        return CalendarEventOutput::fromEntity($event);
    }
}
