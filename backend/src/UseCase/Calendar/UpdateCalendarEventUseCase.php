<?php

namespace App\UseCase\Calendar;

use App\Dto\Calendar\CalendarEventOutput;
use App\Dto\Calendar\UpdateCalendarEventInput;
use App\Entity\CalendarEvent;
use App\Entity\CalendarEventParticipant;
use App\Entity\User;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UpdateCalendarEventUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function execute(UpdateCalendarEventInput $input, int $id, User $user): CalendarEventOutput
    {
        $event = $this->entityManager->getRepository(CalendarEvent::class)->find($id);

        if (!$event) {
            throw new NotFoundHttpException(ErrorMessage::EVENT_NOT_FOUND);
        }

        if ($event->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException(ErrorMessage::EVENT_EDIT_DENIED);
        }

        if ($input->title !== null) {
            $event->setTitle($input->title);
        }
        if ($input->description !== null) {
            $event->setDescription($input->description);
        }
        if ($input->eventType !== null) {
            $event->setEventType($input->eventType);
        }
        if ($input->startDate !== null) {
            $event->setStartDate(new \DateTime($input->startDate));
        }
        if ($input->endDate !== null) {
            $event->setEndDate(new \DateTime($input->endDate));
        }
        if ($input->isAllDay !== null) {
            $event->setIsAllDay($input->isAllDay);
        }
        if ($input->recurrence !== null) {
            $event->setRecurrence($input->recurrence);
        }
        if ($input->color !== null) {
            $event->setColor($input->color);
        }
        if ($input->location !== null) {
            $event->setLocation($input->location);
        }
        if ($input->isPrivate !== null) {
            $event->setIsPrivate($input->isPrivate);
        }

        if ($input->participantIds !== null) {
            $this->syncParticipants($event, $input->participantIds, $user);
        }

        $this->entityManager->flush();

        return CalendarEventOutput::fromEntity($event);
    }

    private function syncParticipants(CalendarEvent $event, array $participantIds, User $currentUser): void
    {
        $entreprise = $currentUser->getEntreprise();
        $existingParticipants = $event->getParticipants();

        $existingUserIds = [];
        foreach ($existingParticipants as $participant) {
            $existingUserIds[] = $participant->getUser()->getId();
        }

        foreach ($existingParticipants as $participant) {
            if (!in_array($participant->getUser()->getId(), $participantIds)) {
                $this->entityManager->remove($participant);
            }
        }

        foreach ($participantIds as $userId) {
            if (in_array($userId, $existingUserIds)) {
                continue;
            }

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
