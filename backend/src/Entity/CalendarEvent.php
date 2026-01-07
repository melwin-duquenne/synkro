<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Calendar\CalendarEventOutput;
use App\Dto\Calendar\CreateCalendarEventInput;
use App\Dto\Calendar\UpdateCalendarEventInput;
use App\State\CalendarEventProcessor;
use App\State\CalendarEventProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'calendar_event')]
#[ORM\Index(name: 'idx_event_entreprise', columns: ['entreprise_id'])]
#[ORM\Index(name: 'idx_event_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_event_dates', columns: ['start_date', 'end_date'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/calendar-events',
            provider: CalendarEventProvider::class,
            output: CalendarEventOutput::class
        ),
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/calendar-events',
            provider: CalendarEventProvider::class,
            output: CalendarEventOutput::class
        ),
        new Get(
            uriTemplate: '/calendar-events/{id}',
            provider: CalendarEventProvider::class,
            output: CalendarEventOutput::class
        ),
        new Post(
            uriTemplate: '/calendar-events',
            input: CreateCalendarEventInput::class,
            output: CalendarEventOutput::class,
            processor: CalendarEventProcessor::class
        ),
        new Patch(
            uriTemplate: '/calendar-events/{id}',
            input: UpdateCalendarEventInput::class,
            output: CalendarEventOutput::class,
            processor: CalendarEventProcessor::class
        ),
        new Delete(
            uriTemplate: '/calendar-events/{id}',
            processor: CalendarEventProcessor::class
        )
    ]
)]
class CalendarEvent
{
    public const TYPE_MEETING = 'meeting';
    public const TYPE_ABSENCE = 'absence';
    public const TYPE_BLOCKED = 'blocked';
    public const TYPE_REMINDER = 'reminder';
    public const TYPE_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'calendarEvents')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'calendarEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'calendarEvents')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $eventType = self::TYPE_OTHER;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isAllDay = false;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $recurrence = null;

    #[ORM\Column(type: 'string', length: 7, nullable: true)]
    private ?string $color = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isPrivate = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: CalendarEventParticipant::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $participants;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->participants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): self
    {
        $this->room = $room;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getEventType(): string
    {
        return $this->eventType;
    }

    public function setEventType(string $eventType): self
    {
        $this->eventType = $eventType;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): self
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isAllDay(): bool
    {
        return $this->isAllDay;
    }

    public function setIsAllDay(bool $isAllDay): self
    {
        $this->isAllDay = $isAllDay;
        return $this;
    }

    public function getRecurrence(): ?string
    {
        return $this->recurrence;
    }

    public function setRecurrence(?string $recurrence): self
    {
        $this->recurrence = $recurrence;
        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(?string $color): self
    {
        $this->color = $color;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->isPrivate;
    }

    public function setIsPrivate(bool $isPrivate): self
    {
        $this->isPrivate = $isPrivate;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(CalendarEventParticipant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setEvent($this);
        }
        return $this;
    }

    public function removeParticipant(CalendarEventParticipant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            if ($participant->getEvent() === $this) {
                $participant->setEvent(null);
            }
        }
        return $this;
    }

    public function clearParticipants(): self
    {
        $this->participants->clear();
        return $this;
    }
}
