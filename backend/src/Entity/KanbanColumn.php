<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\KanbanColumn\CreateKanbanColumnInput;
use App\Dto\KanbanColumn\KanbanColumnOutput;
use App\Dto\KanbanColumn\ReorderKanbanColumnsInput;
use App\Dto\KanbanColumn\UpdateKanbanColumnInput;
use App\State\KanbanColumnProcessor;
use App\State\KanbanColumnProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'kanban_column')]
#[ORM\Index(name: 'idx_kanban_column_room', columns: ['room_id'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/kanban-columns',
            provider: KanbanColumnProvider::class,
            output: KanbanColumnOutput::class,
            normalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ]
        ),
        new Get(
            uriTemplate: '/rooms/{roomId}/kanban-columns/{id}',
            provider: KanbanColumnProvider::class,
            output: KanbanColumnOutput::class,
            normalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: KanbanColumn::class, identifiers: ['id'])
            ]
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/kanban-columns',
            read: false,
            input: CreateKanbanColumnInput::class,
            output: KanbanColumnOutput::class,
            processor: KanbanColumnProcessor::class,
            normalizationContext: [],
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ]
        ),
        new Patch(
            uriTemplate: '/rooms/{roomId}/kanban-columns/{id}',
            input: UpdateKanbanColumnInput::class,
            output: KanbanColumnOutput::class,
            processor: KanbanColumnProcessor::class,
            normalizationContext: [],
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: KanbanColumn::class, identifiers: ['id'])
            ]
        ),
        new Delete(
            uriTemplate: '/rooms/{roomId}/kanban-columns/{id}',
            processor: KanbanColumnProcessor::class,
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id']),
                'id' => new Link(fromClass: KanbanColumn::class, identifiers: ['id'])
            ]
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/kanban-columns/reorder',
            read: false,
            input: ReorderKanbanColumnsInput::class,
            output: false,
            status: 204,
            processor: KanbanColumnProcessor::class,
            denormalizationContext: [],
            uriVariables: [
                'roomId' => new Link(fromClass: Room::class, toProperty: 'room', identifiers: ['id'])
            ],
            name: 'reorder_kanban_columns'
        )
    ]
)]
class KanbanColumn
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'kanbanColumns')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Room $room = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $color = 'bg-slate-500';

    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\OneToMany(mappedBy: 'column', targetEntity: Task::class)]
    private Collection $tasks;

    public function __construct()
    {
        $this->tasks = new ArrayCollection();
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getColor(): string
    {
        return $this->color;
    }

    public function setColor(string $color): self
    {
        $this->color = $color;
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

    public function getTasks(): Collection
    {
        return $this->tasks;
    }
}
