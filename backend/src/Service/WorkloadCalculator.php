<?php

namespace App\Service;

use App\Entity\CalendarEvent;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class WorkloadCalculator
{
    private const STANDARD_WORK_HOURS = 9;
    private const STANDARD_WEEKLY_HOURS = 45;
    private const HIGH_MEETING_COUNT = 6;
    private const MIN_BREAK_MINUTES = 30;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    /**
     * Calculate workload for a specific day
     */
    public function calculateDailyWorkload(User $user, \DateTimeInterface $date, ?Entreprise $entreprise = null): array
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        $events = $this->getEvents($user, $startOfDay, $endOfDay, $entreprise);

        return $this->calculateWorkload($events, 'day');
    }

    /**
     * Calculate workload for current week
     */
    public function calculateWeeklyWorkload(User $user, \DateTimeInterface $date, ?Entreprise $entreprise = null): array
    {
        $weekStart = (clone $date)->modify('monday this week')->setTime(0, 0, 0);
        $weekEnd = (clone $weekStart)->modify('+6 days')->setTime(23, 59, 59);

        $events = $this->getEvents($user, $weekStart, $weekEnd, $entreprise);

        return $this->calculateWorkload($events, 'week');
    }

    private function getEvents(User $user, \DateTimeInterface $start, \DateTimeInterface $end, ?Entreprise $entreprise = null): array
    {
        $qb = $this->entityManager
            ->getRepository(CalendarEvent::class)
            ->createQueryBuilder('e')
            ->where('e.user = :user')
            ->andWhere('e.startDate <= :end')
            ->andWhere('e.endDate >= :start')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        if ($entreprise !== null) {
            $qb->andWhere('e.entreprise = :entreprise')
               ->setParameter('entreprise', $entreprise);
        }

        return $qb->orderBy('e.startDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function calculateWorkload(array $events, string $period): array
    {
        $totalMinutes = 0;
        $meetingCount = 0;
        $workEvents = [];
        $hasShortBreaks = false;

        foreach ($events as $event) {
            // Exclure les absences du calcul
            if ($event->getEventType() === 'absence') {
                continue;
            }

            $duration = $this->getEventDurationMinutes($event);
            $totalMinutes += $duration;

            if ($event->getEventType() === 'meeting') {
                $meetingCount++;
            }

            // Exclude private events from detailed view
            if (!$event->isPrivate()) {
                $workEvents[] = [
                    'id' => $event->getId(),
                    'title' => $event->getTitle(),
                    'type' => $event->getEventType(),
                    'duration' => $duration,
                    'startDate' => $event->getStartDate()->format('Y-m-d H:i:s')
                ];
            }
        }

        // Check for insufficient breaks between meetings
        $hasShortBreaks = $this->hasInsufficientBreaks($events);

        $totalHours = round($totalMinutes / 60, 1);
        $standardHours = $period === 'day' ? self::STANDARD_WORK_HOURS : self::STANDARD_WEEKLY_HOURS;

        // Calculate base percentage
        $basePercentage = ($totalHours / $standardHours) * 100;

        // Apply modifiers
        $modifiers = 0;

        // Too many meetings penalty
        if ($period === 'day' && $meetingCount > self::HIGH_MEETING_COUNT) {
            $modifiers += 10;
        }

        // Insufficient breaks penalty
        if ($hasShortBreaks) {
            $modifiers += 10;
        }

        $finalPercentage = min(150, $basePercentage + $modifiers); // Cap at 150%

        // Determine status
        $status = 'normal';
        $statusLabel = 'Normal';
        $color = 'green';

        if ($finalPercentage >= 100) {
            $status = 'overloaded';
            $statusLabel = 'Surchargé';
            $color = 'red';
        } elseif ($finalPercentage >= 80) {
            $status = 'busy';
            $statusLabel = 'Chargé';
            $color = 'orange';
        }

        return [
            'status' => $status,
            'statusLabel' => $statusLabel,
            'color' => $color,
            'percentage' => round($finalPercentage, 0),
            'totalHours' => $totalHours,
            'eventCount' => count($events),
            'meetingCount' => $meetingCount,
            'hasShortBreaks' => $hasShortBreaks,
            'standardHours' => $standardHours,
            'period' => $period,
            'events' => $workEvents,
            'modifiers' => [
                'tooManyMeetings' => $meetingCount > self::HIGH_MEETING_COUNT,
                'shortBreaks' => $hasShortBreaks
            ]
        ];
    }

    private function getEventDurationMinutes(CalendarEvent $event): int
    {
        $start = $event->getStartDate();
        $end = $event->getEndDate();
        $diff = $start->diff($end);

        return ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }

    private function hasInsufficientBreaks(array $events): bool
    {
        if (count($events) < 2) {
            return false;
        }

        for ($i = 0; $i < count($events) - 1; $i++) {
            $currentEnd = $events[$i]->getEndDate();
            $nextStart = $events[$i + 1]->getStartDate();

            $breakMinutes = ($nextStart->getTimestamp() - $currentEnd->getTimestamp()) / 60;

            // If there's less than MIN_BREAK_MINUTES between two events
            if ($breakMinutes < self::MIN_BREAK_MINUTES && $breakMinutes > 0) {
                return true;
            }
        }

        return false;
    }
}
