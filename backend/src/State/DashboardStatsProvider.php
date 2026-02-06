<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\DashboardData;
use App\Entity\CalendarEvent;
use App\Entity\Room;
use App\Entity\Task;
use App\Entity\User;
use App\Service\WorkloadCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DashboardStatsProvider implements ProviderInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private WorkloadCalculator $workloadCalculator
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        $now = new \DateTime();
        $startOfWeek = (clone $now)->modify('monday this week')->setTime(0, 0);
        $endOfWeek = (clone $startOfWeek)->modify('+6 days')->setTime(23, 59, 59);
        $startOfDay = (clone $now)->setTime(0, 0);
        $endOfDay = (clone $now)->setTime(23, 59, 59);

        return new DashboardData(
            workload: $this->getWorkloadData($user, $startOfWeek, $endOfWeek),
            upcomingEvents: $this->getUpcomingEvents($user),
            todayEvents: $this->getTodayEvents($user, $startOfDay, $endOfDay),
            tasks: $this->getTasksData($user),
            recentRooms: $this->getRecentRooms($user),
            teamAvailability: $this->getTeamAvailability($user),
            statistics: $this->getStatistics($user, $startOfWeek),
            notifications: $this->getNotifications($user),
        );
    }

    private function getWorkloadData(User $user, \DateTime $start, \DateTime $end): array
    {
        $workloadData = [];
        $currentDate = clone $start;
        $overloadDays = 0;

        while ($currentDate <= $end) {
            $result = $this->workloadCalculator->calculateDailyWorkload($user, $currentDate);
            $workload = $result['percentage'] ?? 0;
            $workloadData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'dayName' => $this->getDayName($currentDate),
                'percentage' => $workload,
                'isOverloaded' => $workload > 100,
            ];

            if ($workload > 100) {
                $overloadDays++;
            }

            $currentDate->modify('+1 day');
        }

        return [
            'daily' => $workloadData,
            'overloadDays' => $overloadDays,
            'hasAlert' => $overloadDays >= 2,
        ];
    }

    private function getUpcomingEvents(User $user): array
    {
        $now = new \DateTime();

        $events = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->where('e.startDate >= :now')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'ASC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        return array_map(fn($event) => [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'startDate' => $event->getStartDate()->format('c'),
            'endDate' => $event->getEndDate()->format('c'),
            'eventType' => $event->getEventType(),
            'room' => $event->getRoom() ? [
                'id' => $event->getRoom()->getId(),
                'name' => $event->getRoom()->getName(),
            ] : null,
        ], $events);
    }

    private function getTodayEvents(User $user, \DateTime $start, \DateTime $end): array
    {
        $events = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->where('e.startDate BETWEEN :start AND :end')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        return array_map(fn($event) => [
            'id' => $event->getId(),
            'title' => $event->getTitle(),
            'startDate' => $event->getStartDate()->format('c'),
            'endDate' => $event->getEndDate()->format('c'),
            'eventType' => $event->getEventType(),
        ], $events);
    }

    private function getTasksData(User $user): array
    {
        $taskRepo = $this->entityManager->getRepository(Task::class);

        // Tâches du jour (pas de filtre dueDate car le champ n'existe pas encore)
        $todayTasks = $taskRepo->createQueryBuilder('t')
            ->where('t.assignedTo = :user')
            ->andWhere('t.type = :active')
            ->setParameter('user', $user)
            ->setParameter('active', Task::TYPE_ACTIVE)
            ->orderBy('t.createdAt', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        // Tâches en retard (champ dueDate n'existe pas encore, on retourne vide)
        $overdueTasks = [];

        // Progression par room
        $roomProgress = $taskRepo->createQueryBuilder('t')
            ->select('IDENTITY(t.room) as roomId, r.name as roomName, COUNT(t.id) as total, SUM(CASE WHEN t.type = :archived THEN 1 ELSE 0 END) as completed')
            ->leftJoin('t.room', 'r')
            ->where('t.assignedTo = :user')
            ->andWhere('t.room IS NOT NULL')
            ->setParameter('user', $user)
            ->setParameter('archived', Task::TYPE_ARCHIVED)
            ->groupBy('t.room, r.name')
            ->getQuery()
            ->getResult();

        return [
            'today' => array_map(fn($t) => $this->formatTask($t), $todayTasks),
            'overdue' => array_map(fn($t) => $this->formatTask($t), $overdueTasks),
            'roomProgress' => array_map(fn($r) => [
                'roomId' => $r['roomId'],
                'roomName' => $r['roomName'],
                'total' => (int)$r['total'],
                'completed' => (int)$r['completed'],
                'percentage' => $r['total'] > 0 ? round(($r['completed'] / $r['total']) * 100) : 0,
            ], $roomProgress),
        ];
    }

    private function getRecentRooms(User $user): array
    {
        // Récupérer l'entreprise de l'utilisateur si elle existe
        $userEntreprise = $user->getEntreprise();

        // Récupérer les rooms où l'utilisateur a accès
        $qb = $this->entityManager->getRepository(Room::class)
            ->createQueryBuilder('r')
            ->leftJoin('r.userPermissions', 'urp')
            ->leftJoin('r.teamPermissions', 'trp')
            ->leftJoin('trp.team', 't')
            ->leftJoin('t.users', 'tm')
            ->leftJoin('r.creator', 'c')
            ->leftJoin('c.entreprise', 'ce')
            ->where('r.creator = :user OR urp.user = :user OR tm = :user')
            ->setParameter('user', $user);

        // Si l'utilisateur a une entreprise, ajouter les rooms enterprise de cette entreprise
        if ($userEntreprise) {
            $qb->orWhere('r.visibility = :enterprise AND ce = :userEntreprise')
               ->setParameter('enterprise', 'enterprise')
               ->setParameter('userEntreprise', $userEntreprise);
        }

        $rooms = $qb->orderBy('r.createdAt', 'DESC')
                    ->setMaxResults(10)
                    ->getQuery()
                    ->getResult();

        return array_map(fn($room) => [
            'id' => $room->getId(),
            'name' => $room->getName(),
            'visibility' => $room->getVisibility(),
            'moduleCount' => $room->getModuleRooms()->count(),
            'layoutType' => $room->getLayoutType(),
            'creator' => $room->getCreator() ? [
                'displayName' => $room->getCreator()->getDisplayName(),
            ] : null,
            'createdAt' => $room->getCreatedAt()?->format('c'),
        ], $rooms);
    }

    private function getTeamAvailability(User $user): array
    {
        if (!$user->getEntreprise()) {
            return [];
        }

        $now = new \DateTime();
        $teamMembers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->where('u.entreprise = :entreprise')
            ->andWhere('u.id != :userId')
            ->setParameter('entreprise', $user->getEntreprise())
            ->setParameter('userId', $user->getId())
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return array_map(function($member) use ($now) {
            $result = $this->workloadCalculator->calculateDailyWorkload($member, $now);
            $workload = $result['percentage'] ?? 0;

            return [
                'id' => $member->getId(),
                'name' => $member->getDisplayName(),
                'email' => $member->getEmail(),
                'avatar' => $member->getAvatarPath(),
                'workload' => $workload,
                'status' => $this->getUserStatus($member, $now),
            ];
        }, $teamMembers);
    }

    private function getStatistics(User $user, \DateTime $startOfWeek): array
    {
        $now = new \DateTime();

        // Tâches complétées cette semaine
        $completedTasks = $this->entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.type = :archived')
            ->andWhere('t.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('archived', Task::TYPE_ARCHIVED)
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();

        // Tâches créées cette semaine
        $createdTasks = $this->entityManager->getRepository(Task::class)
            ->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedTo = :user')
            ->andWhere('t.createdAt >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $startOfWeek)
            ->getQuery()
            ->getSingleScalarResult();

        // Réunions cette semaine
        $meetingsCount = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->select('COUNT(DISTINCT e.id)')
            ->leftJoin('e.participants', 'p')
            ->where('e.startDate >= :start AND e.startDate <= :now')
            ->andWhere('e.eventType = :meeting')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('start', $startOfWeek)
            ->setParameter('now', $now)
            ->setParameter('meeting', 'meeting')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();

        // Durée totale des réunions
        $meetingsDuration = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->where('e.startDate >= :start AND e.startDate <= :now')
            ->andWhere('e.eventType = :meeting')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('start', $startOfWeek)
            ->setParameter('now', $now)
            ->setParameter('meeting', 'meeting')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();

        $totalMinutes = 0;
        foreach ($meetingsDuration as $meeting) {
            $diff = $meeting->getStartDate()->diff($meeting->getEndDate());
            $totalMinutes += ($diff->h * 60) + $diff->i;
        }

        return [
            'tasksCompleted' => (int)$completedTasks,
            'tasksCreated' => (int)$createdTasks,
            'productivity' => $createdTasks > 0 ? round(($completedTasks / $createdTasks) * 100) : 0,
            'meetingsCount' => (int)$meetingsCount,
            'meetingsDuration' => $totalMinutes,
            'meetingsDurationFormatted' => $this->formatDuration($totalMinutes),
        ];
    }

    private function getNotifications(User $user): array
    {
        // Tâches avec deadline proche (champ dueDate n'existe pas encore, on retourne vide)
        $upcomingDeadlines = [];

        return [
            'upcomingDeadlines' => array_map(fn($t) => $this->formatTask($t), $upcomingDeadlines),
        ];
    }

    private function formatTask(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getColumn() ? $task->getColumn()->getName() : 'unknown',
            'type' => $task->getType(),
            'priority' => 'medium', // Default priority
            'dueDate' => null, // Field not available yet
            'room' => $task->getRoom() ? [
                'id' => $task->getRoom()->getId(),
                'name' => $task->getRoom()->getName(),
            ] : null,
        ];
    }

    private function getUserStatus(User $user, \DateTime $now): string
    {
        // Vérifier si en absence
        $absence = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->where('e.eventType = :absence')
            ->andWhere('e.startDate <= :now AND e.endDate >= :now')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('absence', 'absence')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($absence) {
            return 'absent';
        }

        // Vérifier si en réunion
        $meeting = $this->entityManager->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->leftJoin('e.participants', 'p')
            ->where('e.eventType = :meeting')
            ->andWhere('e.startDate <= :now AND e.endDate >= :now')
            ->andWhere('p.user = :user OR e.user = :user')
            ->setParameter('meeting', 'meeting')
            ->setParameter('now', $now)
            ->setParameter('user', $user)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($meeting) {
            return 'busy';
        }

        return 'available';
    }

    private function getDayName(\DateTime $date): string
    {
        $days = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
        return $days[(int)$date->format('w')];
    }

    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0) {
            return $mins > 0 ? "{$hours}h{$mins}" : "{$hours}h";
        }

        return "{$mins}min";
    }
}
