<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Room\CreateRoomInput;
use App\Dto\Room\ManageRoomMembersInput;
use App\Dto\Room\RoomMemberOutput;
use App\Dto\Room\RoomOutput;
use App\Dto\Room\UpdateRoomInput;
use App\State\RoomMembersProcessor;
use App\State\RoomMembersProvider;
use App\State\RoomProcessor;
use App\State\RoomProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'room')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms',
            provider: RoomProvider::class,
            output: RoomOutput::class
        ),
        new Get(
            uriTemplate: '/rooms/{id}',
            provider: RoomProvider::class,
            output: RoomOutput::class
        ),
        new Post(
            uriTemplate: '/rooms',
            input: CreateRoomInput::class,
            output: RoomOutput::class,
            processor: RoomProcessor::class
        ),
        new Patch(
            uriTemplate: '/rooms/{id}',
            input: UpdateRoomInput::class,
            output: RoomOutput::class,
            processor: RoomProcessor::class
        ),
        new Post(
            uriTemplate: '/rooms/{id}/reorder-modules',
            output: RoomOutput::class,
            processor: RoomProcessor::class
        ),
        new Delete(
            uriTemplate: '/rooms/{id}',
            processor: RoomProcessor::class
        ),
        new GetCollection(
            uriTemplate: '/rooms/{id}/members',
            output: RoomMemberOutput::class,
            provider: RoomMembersProvider::class
        ),
        new Post(
            uriTemplate: '/rooms/{id}/members',
            input: ManageRoomMembersInput::class,
            output: RoomMemberOutput::class,
            processor: RoomMembersProcessor::class
        )
    ]
)]
class Room
{
    public const VISIBILITY_ENTERPRISE = 'enterprise';
    public const VISIBILITY_PRIVATE = 'private';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['room:read', 'message:read', 'document:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['room:read', 'room:write'])]
    private ?string $name = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['room:read', 'room:write'])]
    private bool $isTemporary = false;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['room:read', 'room:write'])]
    private string $visibility = self::VISIBILITY_ENTERPRISE;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'createdRooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['room:read'])]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class, inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['room:read', 'room:write'])]
    private ?Entreprise $entreprise = null;

    #[ORM\ManyToOne(targetEntity: RoomTemplate::class, inversedBy: 'rooms')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['room:read', 'room:write'])]
    private ?RoomTemplate $template = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['room:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    #[Groups(['room:read', 'room:write'])]
    private string $layoutType = 'tabs';

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: ModuleRoom::class, cascade: ['persist', 'remove'])]
    #[Groups(['room:read'])]
    private Collection $moduleRooms;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: TeamRoomPermission::class, cascade: ['persist', 'remove'])]
    private Collection $teamPermissions;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: UserRoomPermission::class, cascade: ['persist', 'remove'])]
    private Collection $userPermissions;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Message::class, cascade: ['remove'])]
    private Collection $messages;

    #[ORM\OneToOne(mappedBy: 'room', targetEntity: Document::class, cascade: ['persist', 'remove'])]
    private ?Document $document = null;

    #[ORM\OneToOne(mappedBy: 'room', targetEntity: Whiteboard::class, cascade: ['persist', 'remove'])]
    private ?Whiteboard $whiteboard = null;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: FileResource::class, cascade: ['remove'])]
    private Collection $files;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Task::class, cascade: ['remove'])]
    private Collection $tasks;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: KanbanColumn::class, cascade: ['persist', 'remove'])]
    private Collection $kanbanColumns;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: CalendarEvent::class)]
    private Collection $calendarEvents;

    public function __construct()
    {
        $this->moduleRooms = new ArrayCollection();
        $this->teamPermissions = new ArrayCollection();
        $this->userPermissions = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->kanbanColumns = new ArrayCollection();
        $this->calendarEvents = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function isTemporary(): bool
    {
        return $this->isTemporary;
    }

    public function setIsTemporary(bool $isTemporary): self
    {
        $this->isTemporary = $isTemporary;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->visibility === self::VISIBILITY_PRIVATE;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;
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

    public function getTemplate(): ?RoomTemplate
    {
        return $this->template;
    }

    public function setTemplate(?RoomTemplate $template): self
    {
        $this->template = $template;
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

    public function getModuleRooms(): Collection
    {
        return $this->moduleRooms;
    }

    public function addModuleRoom(ModuleRoom $moduleRoom): self
    {
        if (!$this->moduleRooms->contains($moduleRoom)) {
            $this->moduleRooms->add($moduleRoom);
            $moduleRoom->setRoom($this);
        }
        return $this;
    }

    public function removeModuleRoom(ModuleRoom $moduleRoom): self
    {
        if ($this->moduleRooms->removeElement($moduleRoom)) {
            if ($moduleRoom->getRoom() === $this) {
                $moduleRoom->setRoom(null);
            }
        }
        return $this;
    }

    public function getTeamPermissions(): Collection
    {
        return $this->teamPermissions;
    }

    public function addTeamPermission(TeamRoomPermission $permission): self
    {
        if (!$this->teamPermissions->contains($permission)) {
            $this->teamPermissions->add($permission);
            $permission->setRoom($this);
        }
        return $this;
    }

    public function getUserPermissions(): Collection
    {
        return $this->userPermissions;
    }

    public function addUserPermission(UserRoomPermission $permission): self
    {
        if (!$this->userPermissions->contains($permission)) {
            $this->userPermissions->add($permission);
            $permission->setRoom($this);
        }
        return $this;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): self
    {
        if ($document !== null && $document->getRoom() !== $this) {
            $document->setRoom($this);
        }
        $this->document = $document;
        return $this;
    }

    public function getWhiteboard(): ?Whiteboard
    {
        return $this->whiteboard;
    }

    public function setWhiteboard(?Whiteboard $whiteboard): self
    {
        if ($whiteboard !== null && $whiteboard->getRoom() !== $this) {
            $whiteboard->setRoom($this);
        }
        $this->whiteboard = $whiteboard;
        return $this;
    }

    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getKanbanColumns(): Collection
    {
        return $this->kanbanColumns;
    }

    public function addKanbanColumn(KanbanColumn $kanbanColumn): self
    {
        if (!$this->kanbanColumns->contains($kanbanColumn)) {
            $this->kanbanColumns->add($kanbanColumn);
            $kanbanColumn->setRoom($this);
        }
        return $this;
    }

    public function removeKanbanColumn(KanbanColumn $kanbanColumn): self
    {
        if ($this->kanbanColumns->removeElement($kanbanColumn)) {
            if ($kanbanColumn->getRoom() === $this) {
                $kanbanColumn->setRoom(null);
            }
        }
        return $this;
    }

    public function getCalendarEvents(): Collection
    {
        return $this->calendarEvents;
    }

    public function getLayoutType(): string
    {
        return $this->layoutType;
    }

    public function setLayoutType(string $layoutType): self
    {
        $this->layoutType = $layoutType;
        return $this;
    }
}
