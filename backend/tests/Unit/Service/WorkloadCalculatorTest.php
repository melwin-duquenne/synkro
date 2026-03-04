<?php

namespace App\Tests\Unit\Service;

use App\Entity\CalendarEvent;
use App\Entity\User;
use App\Service\WorkloadCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;

class WorkloadCalculatorTest extends TestCase
{
    private function buildCalculator(array $events): WorkloadCalculator
    {
        $query = $this->createStub(Query::class);
        $query->method('getResult')->willReturn($events);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('where')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('orderBy')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $calendarRepo = $this->createStub(EntityRepository::class);
        $calendarRepo->method('createQueryBuilder')->willReturn($qb);

        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($calendarRepo);

        return new WorkloadCalculator($em);
    }

    private function makeEvent(int $hours): CalendarEvent
    {
        $start = new \DateTime('2026-03-04 09:00:00');
        $end = (clone $start)->modify('+' . $hours . ' hours');

        $event = new CalendarEvent();
        $event->setEventType(CalendarEvent::TYPE_BLOCKED);
        $event->setTitle('Test event');
        $event->setStartDate($start);
        $event->setEndDate($end);

        return $event;
    }

    public function testCalculateDailyWorkloadReturnsNormalWhenLoadIsBelow80Percent(): void
    {
        $calculator = $this->buildCalculator([$this->makeEvent(7)]); // 7/9 = 77.8%

        $result = $calculator->calculateDailyWorkload(new User(), new \DateTime('2026-03-04'));

        $this->assertSame('normal', $result['status']);
    }

    public function testCalculateDailyWorkloadReturnsBusyWhenLoadIsBetween80And100Percent(): void
    {
        $calculator = $this->buildCalculator([$this->makeEvent(8)]); // 8/9 = 88.9%

        $result = $calculator->calculateDailyWorkload(new User(), new \DateTime('2026-03-04'));

        $this->assertSame('busy', $result['status']);
    }

    public function testCalculateDailyWorkloadReturnsOverloadedWhenLoadReaches100Percent(): void
    {
        $calculator = $this->buildCalculator([$this->makeEvent(9)]); // 9/9 = 100%

        $result = $calculator->calculateDailyWorkload(new User(), new \DateTime('2026-03-04'));

        $this->assertSame('overloaded', $result['status']);
    }
}
