<?php

namespace App\Dto\Calendar;

use App\Dto\UserSimpleOutput;
use App\Entity\CalendarEvent;

final class CalendarEventOutput
{
    public int $id;
    public string $title;
    public ?string $description;
    public string $eventType;
    public string $startDate;
    public string $endDate;
    public bool $isAllDay;
    public ?string $recurrence;
    public ?string $color;
    public ?string $location;
    public bool $isPrivate;
    public ?int $roomId;
    public UserSimpleOutput $user;
    public string $createdAt;
    /** @var ParticipantOutput[] */
    public array $participants = [];

    public static function fromEntity(CalendarEvent $event): self
    {
        $output = new self();
        $output->id = $event->getId();
        $output->title = $event->getTitle();
        $output->description = $event->getDescription();
        $output->eventType = $event->getEventType();
        $output->startDate = $event->getStartDate()->format('c');
        $output->endDate = $event->getEndDate()->format('c');
        $output->isAllDay = $event->isAllDay();
        $output->recurrence = $event->getRecurrence();
        $output->color = $event->getColor();
        $output->location = $event->getLocation();
        $output->isPrivate = $event->isPrivate();
        $output->roomId = $event->getRoom()?->getId();
        $output->user = UserSimpleOutput::fromEntity($event->getUser());
        $output->createdAt = $event->getCreatedAt()->format('c');

        $output->participants = array_map(
            fn($p) => ParticipantOutput::fromEntity($p),
            $event->getParticipants()->toArray()
        );

        return $output;
    }
}
