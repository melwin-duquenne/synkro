<?php

namespace App\UseCase\Calendar;

use App\Dto\Calendar\CalendarEventOutput;
use App\Dto\Calendar\CreateCalendarEventInput;
use App\Entity\CalendarEvent;
use App\Entity\CalendarEventParticipant;
use App\Entity\Room;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CreateCalendarEventUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RoomAccessChecker $accessChecker
    ) {}

    public function execute(CreateCalendarEventInput $input, User $user): CalendarEventOutput
    {
        if (!$user->getEntreprise()) {
            throw new BadRequestHttpException(ErrorMessage::EVENT_REQUIRES_ENTREPRISE);
        }

        $event = new CalendarEvent();
        $event->setUser($user);
        $event->setEntreprise($user->getEntreprise());
        $event->setTitle($input->title);
        $event->setDescription($input->description);
        $event->setEventType($input->eventType);
        $event->setStartDate(new \DateTime($input->startDate));
        $event->setEndDate(new \DateTime($input->endDate));
        $event->setIsAllDay($input->isAllDay);
        $event->setRecurrence($input->recurrence);
        $event->setColor($input->color);
        $event->setLocation($input->location);
        $event->setIsPrivate($input->isPrivate);

        if ($input->roomId !== null) {
            $room = $this->entityManager->getRepository(Room::class)->find($input->roomId);
            if (!$room) {
                throw new NotFoundHttpException(ErrorMessage::ROOM_NOT_FOUND);
            }
            if (!$this->accessChecker->canAccess($user, $room)) {
                throw new AccessDeniedHttpException(ErrorMessage::ROOM_ACCESS_DENIED);
            }
            $event->setRoom($room);
        }

        $this->entityManager->persist($event);

        $this->addParticipants($event, $input->participantIds, $user);

        $this->entityManager->flush();

        return CalendarEventOutput::fromEntity($event);
    }

    private function addParticipants(CalendarEvent $event, array $participantIds, User $currentUser): void
    {
        $entreprise = $currentUser->getEntreprise();

        foreach ($participantIds as $userId) {
            $participantUser = $this->entityManager->getRepository(User::class)->find($userId);

            if ($participantUser && $participantUser->getEntreprise()?->getId() === $entreprise?->getId()) {
                $participant = new CalendarEventParticipant();
                $participant->setUser($participantUser);
                $participant->setStatus(CalendarEventParticipant::STATUS_PENDING);
                $event->addParticipant($participant);
                $this->entityManager->persist($participant);
            }
        }
    }
}
