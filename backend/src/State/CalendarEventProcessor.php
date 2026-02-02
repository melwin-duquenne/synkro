<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Calendar\CalendarEventOutput;
use App\Dto\Calendar\CreateCalendarEventInput;
use App\Dto\Calendar\UpdateCalendarEventInput;
use App\Entity\CalendarEvent;
use App\Entity\CalendarEventParticipant;
use App\Entity\Room;
use App\Entity\User;
use App\Security\RoomAccessChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CalendarEventProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private RoomAccessChecker $accessChecker
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        if ($operation instanceof Post) {
            return $this->create($data, $user);
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $uriVariables['id'], $user);
        }

        if ($operation instanceof Delete) {
            return $this->delete($uriVariables['id'], $user);
        }

        return null;
    }

    private function create(CreateCalendarEventInput $data, User $user): CalendarEventOutput
    {
        if (!$user->getEntreprise()) {
            throw new BadRequestHttpException('User must belong to an enterprise');
        }

        $event = new CalendarEvent();
        $event->setUser($user);
        $event->setEntreprise($user->getEntreprise());
        $event->setTitle($data->title);
        $event->setDescription($data->description);
        $event->setEventType($data->eventType);
        $event->setStartDate(new \DateTime($data->startDate));
        $event->setEndDate(new \DateTime($data->endDate));
        $event->setIsAllDay($data->isAllDay);
        $event->setRecurrence($data->recurrence);
        $event->setColor($data->color);
        $event->setLocation($data->location);
        $event->setIsPrivate($data->isPrivate);

        // Handle room association
        if ($data->roomId !== null) {
            $room = $this->entityManager->getRepository(Room::class)->find($data->roomId);
            if (!$room) {
                throw new NotFoundHttpException('Room not found');
            }
            if (!$this->accessChecker->canAccess($user, $room)) {
                throw new AccessDeniedHttpException('Access denied to this room');
            }
            $event->setRoom($room);
        }

        $this->entityManager->persist($event);

        // Add participants
        $this->updateParticipants($event, $data->participantIds, $user);

        $this->entityManager->flush();

        return CalendarEventOutput::fromEntity($event);
    }

    private function update(UpdateCalendarEventInput $data, int $id, User $user): CalendarEventOutput
    {
        $event = $this->entityManager->getRepository(CalendarEvent::class)->find($id);

        if (!$event) {
            throw new NotFoundHttpException('Event not found');
        }

        // Check ownership or admin
        if ($event->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You can only edit your own events');
        }

        // Update fields if provided
        if ($data->title !== null) {
            $event->setTitle($data->title);
        }
        if ($data->description !== null) {
            $event->setDescription($data->description);
        }
        if ($data->eventType !== null) {
            $event->setEventType($data->eventType);
        }
        if ($data->startDate !== null) {
            $event->setStartDate(new \DateTime($data->startDate));
        }
        if ($data->endDate !== null) {
            $event->setEndDate(new \DateTime($data->endDate));
        }
        if ($data->isAllDay !== null) {
            $event->setIsAllDay($data->isAllDay);
        }
        if ($data->recurrence !== null) {
            $event->setRecurrence($data->recurrence);
        }
        if ($data->color !== null) {
            $event->setColor($data->color);
        }
        if ($data->location !== null) {
            $event->setLocation($data->location);
        }
        if ($data->isPrivate !== null) {
            $event->setIsPrivate($data->isPrivate);
        }

        // Update participants if provided
        if ($data->participantIds !== null) {
            $this->updateParticipants($event, $data->participantIds, $user);
        }

        $this->entityManager->flush();

        return CalendarEventOutput::fromEntity($event);
    }

    private function updateParticipants(CalendarEvent $event, array $participantIds, User $currentUser): void
    {
        $entreprise = $currentUser->getEntreprise();

        // Remove existing participants
        $event->clearParticipants();

        // Add new participants
        foreach ($participantIds as $userId) {
            $participantUser = $this->entityManager->getRepository(User::class)->find($userId);

            // Only add if user exists and belongs to the same enterprise
            if ($participantUser && $participantUser->getEntreprise()?->getId() === $entreprise?->getId()) {
                $participant = new CalendarEventParticipant();
                $participant->setUser($participantUser);
                $participant->setStatus(CalendarEventParticipant::STATUS_PENDING);
                $event->addParticipant($participant);
            }
        }
    }

    private function delete(int $id, User $user): null
    {
        $event = $this->entityManager->getRepository(CalendarEvent::class)->find($id);

        if (!$event) {
            throw new NotFoundHttpException('Event not found');
        }

        // Check ownership
        if ($event->getUser()->getId() !== $user->getId()) {
            throw new AccessDeniedHttpException('You can only delete your own events');
        }

        $this->entityManager->remove($event);
        $this->entityManager->flush();

        return null;
    }
}
