<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Dto\Task\CreateTaskInput;
use App\Dto\Task\ReorderTasksInput;
use App\Dto\Task\TaskOutput;
use App\Dto\Task\UpdateTaskInput;
use App\State\TaskProcessor;
use App\State\TaskProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'task')]
#[ORM\Index(name: 'idx_task_room', columns: ['room_id'])]
#[ORM\Index(name: 'idx_task_column', columns: ['column_id'])]
#[ORM\Index(name: 'idx_task_type', columns: ['type'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/tasks',
            provider: TaskProvider::class,
            output: TaskOutput::class,
            normalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ]
        ),
        new Get(
            uriTemplate: '/rooms/{roomId}/tasks/{id}',
            provider: TaskProvider::class,
            output: TaskOutput::class,
            normalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: Task::class, identifiers: ['id'])
            ]
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/tasks',
            read: false,
            input: CreateTaskInput::class,
            output: TaskOutput::class,
            processor: TaskProcessor::class,
            normalizationContext: [],
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ]
        ),
        new Put(
            uriTemplate: '/rooms/{roomId}/tasks/{id}',
            input: UpdateTaskInput::class,
            output: TaskOutput::class,
            processor: TaskProcessor::class,
            normalizationContext: [],
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: Task::class, identifiers: ['id'])
            ]
        ),
        new Patch(
            uriTemplate: '/rooms/{roomId}/tasks/{id}',
            input: UpdateTaskInput::class,
            output: TaskOutput::class,
            processor: TaskProcessor::class,
            normalizationContext: [],
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: Task::class, identifiers: ['id'])
            ]
        ),
        new Delete(
            uriTemplate: '/rooms/{roomId}/tasks/{id}',
            processor: TaskProcessor::class,
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: Task::class, identifiers: ['id'])
            ]
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/tasks/reorder',
            read: false,
            input: ReorderTasksInput::class,
            processor: TaskProcessor::class,
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ],
            name: 'reorder_tasks'
        )
    ],
    normalizationContext: ['groups' => ['task:read']],
    denormalizationContext: ['groups' => ['task:write']]
)]
class Task
{
    public const TYPE_ACTIVE = 'active';
    public const TYPE_ARCHIVED = 'archived';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['task:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:read', 'task:write'])]
    private ?Room $room = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: KanbanColumn::class, inversedBy: 'tasks')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['task:read', 'task:write'])]
    private ?KanbanColumn $column = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['task:read', 'task:write'])]
    private string $type = self::TYPE_ACTIVE;

    #[ORM\Column(type: 'integer')]
    #[Groups(['task:read', 'task:write'])]
    private int $position = 0;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'assignedTasks')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?User $assignedTo = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['task:read', 'task:write'])]
    private ?int $estimation = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['task:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
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

    public function getColumn(): ?KanbanColumn
    {
        return $this->column;
    }

    public function setColumn(?KanbanColumn $column): self
    {
        $this->column = $column;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }

    public function getAssignedTo(): ?User
    {
        return $this->assignedTo;
    }

    public function setAssignedTo(?User $assignedTo): self
    {
        $this->assignedTo = $assignedTo;
        return $this;
    }

    public function getEstimation(): ?int
    {
        return $this->estimation;
    }

    public function setEstimation(?int $estimation): self
    {
        $this->estimation = $estimation;
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
}
