<?php

namespace App\Tests\Unit\State;

use ApiPlatform\Metadata\Delete;
use App\Entity\KanbanColumn;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use App\State\KanbanColumnProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class KanbanColumnProcessorTest extends TestCase
{
    private EntityManagerInterface $em;
    private Security $security;
    private RoomAccessChecker $accessChecker;
    private EntityRepository $roomRepo;
    private EntityRepository $columnRepo;

    protected function setUp(): void
    {
        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->security = $this->createStub(Security::class);
        $this->accessChecker = $this->createStub(RoomAccessChecker::class);
        $this->roomRepo = $this->createStub(EntityRepository::class);
        $this->columnRepo = $this->createStub(EntityRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [Room::class, $this->roomRepo],
            [KanbanColumn::class, $this->columnRepo],
        ]);
    }

    /**
     * Sets up common delete scenario: authenticated user, room found, access granted,
     * column found whose room getId() returns $columnRoomId (may differ from $roomId for 404).
     *
     * @return array{0: KanbanColumn}
     */
    private function buildDeleteScenario(int $roomId, int $columnId, int $columnRoomId): array
    {
        $user = new User();
        $user->setRole('owner');
        $this->security->method('getUser')->willReturn($user);

        $room = $this->createStub(Room::class);
        $room->method('getId')->willReturn($roomId);
        $this->roomRepo->method('find')->with($roomId)->willReturn($room);

        $this->accessChecker->method('canAccess')->willReturn(true);

        $columnRoom = $this->createStub(Room::class);
        $columnRoom->method('getId')->willReturn($columnRoomId);

        $column = $this->createStub(KanbanColumn::class);
        $column->method('getRoom')->willReturn($columnRoom);
        $this->columnRepo->method('find')->with($columnId)->willReturn($column);

        return [$column];
    }

    private function setupActiveTaskQuery(int $count): void
    {
        $query = $this->createStub(Query::class);
        $query->method('getSingleScalarResult')->willReturn($count);

        $qb = $this->createStub(QueryBuilder::class);
        $qb->method('select')->willReturn($qb);
        $qb->method('from')->willReturn($qb);
        $qb->method('where')->willReturn($qb);
        $qb->method('andWhere')->willReturn($qb);
        $qb->method('setParameter')->willReturn($qb);
        $qb->method('getQuery')->willReturn($query);

        $this->em->method('createQueryBuilder')->willReturn($qb);
    }

    public function testDeleteThrowsBadRequestExceptionWhenColumnHasActiveTasks(): void
    {
        $this->buildDeleteScenario(1, 42, 1);
        $this->setupActiveTaskQuery(1);

        $processor = new KanbanColumnProcessor($this->em, $this->security, $this->accessChecker);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessageMatches('/Impossible de supprimer cette colonne/');

        $processor->process(null, new Delete(), ['roomId' => 1, 'id' => 42]);
    }

    public function testDeleteRemovesColumnAndFlushesWhenNoActiveTasks(): void
    {
        // createMock needed here to verify remove/flush are called exactly once
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturnMap([
            [Room::class, $this->roomRepo],
            [KanbanColumn::class, $this->columnRepo],
        ]);

        [$column] = $this->buildDeleteScenario(1, 42, 1);
        $this->setupActiveTaskQuery(0);

        $this->em->expects($this->once())->method('remove')->with($column);
        $this->em->expects($this->once())->method('flush');

        $processor = new KanbanColumnProcessor($this->em, $this->security, $this->accessChecker);
        $processor->process(null, new Delete(), ['roomId' => 1, 'id' => 42]);
    }

    public function testDeleteThrowsNotFoundExceptionWhenColumnBelongsToDifferentRoom(): void
    {
        $this->buildDeleteScenario(1, 42, 99); // column is in room 99, not 1

        $processor = new KanbanColumnProcessor($this->em, $this->security, $this->accessChecker);

        $this->expectException(NotFoundHttpException::class);
        $processor->process(null, new Delete(), ['roomId' => 1, 'id' => 42]);
    }
}
