<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\File\CreateFolderInput;
use App\Dto\File\FileOutput;
use App\Dto\File\UpdateFileInput;
use App\State\FileResourceProcessor;
use App\State\FileResourceProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'file_resource')]
#[ORM\Index(name: 'idx_file_room', columns: ['room_id'])]
#[ORM\Index(name: 'idx_file_parent', columns: ['parent_id'])]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/rooms/{roomId}/files',
            provider: FileResourceProvider::class,
            output: FileOutput::class,
            uriVariables: ['roomId']
        ),
        new Get(
            uriTemplate: '/rooms/{roomId}/files/{id}',
            provider: FileResourceProvider::class,
            output: FileOutput::class,
            uriVariables: ['roomId', 'id']
        ),
        new Get(
            uriTemplate: '/rooms/{roomId}/files/{id}/download',
            provider: FileResourceProvider::class,
            uriVariables: ['roomId', 'id'],
            name: 'download_file'
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/files',
            provider: FileResourceProvider::class,
            processor: FileResourceProcessor::class,
            output: FileOutput::class,
            uriVariables: ['roomId'],
            deserialize: false
        ),
        new Post(
            uriTemplate: '/rooms/{roomId}/files/folder',
            input: CreateFolderInput::class,
            output: FileOutput::class,
            provider: FileResourceProvider::class,
            processor: FileResourceProcessor::class,
            uriVariables: ['roomId'],
            name: 'create_folder'
        ),
        new Patch(
            uriTemplate: '/rooms/{roomId}/files/{id}',
            input: UpdateFileInput::class,
            output: FileOutput::class,
            provider: FileResourceProvider::class,
            processor: FileResourceProcessor::class,
            uriVariables: ['roomId', 'id']
        ),
        new Delete(
            uriTemplate: '/rooms/{roomId}/files/{id}',
            provider: FileResourceProvider::class,
            processor: FileResourceProcessor::class,
            uriVariables: ['roomId', 'id']
        )
    ],
    normalizationContext: ['groups' => ['file:read']],
    denormalizationContext: ['groups' => ['file:write']]
)]
class FileResource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['file:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['file:read', 'file:write'])]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'files')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['file:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['file:read', 'file:write'])]
    private ?string $fileName = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['file:read'])]
    private ?string $filePath = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Groups(['file:read'])]
    private ?string $mimeType = null;

    #[ORM\Column(type: 'integer')]
    #[Groups(['file:read'])]
    private ?int $size = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['file:read'])]
    private bool $isFolder = false;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    #[Groups(['file:read'])]
    private ?self $parent = null;

    #[ORM\OneToMany(targetEntity: self::class, mappedBy: 'parent')]
    private Collection $children;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['file:read'])]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->children = new ArrayCollection();
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

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): self
    {
        $this->filePath = $filePath;
        return $this;
    }

    public function getMimeType(): ?string
    {
        return $this->mimeType;
    }

    public function setMimeType(?string $mimeType): self
    {
        $this->mimeType = $mimeType;
        return $this;
    }

    public function getSize(): ?int
    {
        return $this->size;
    }

    public function setSize(int $size): self
    {
        $this->size = $size;
        return $this;
    }

    public function isFolder(): bool
    {
        return $this->isFolder;
    }

    public function setIsFolder(bool $isFolder): self
    {
        $this->isFolder = $isFolder;
        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getChildren(): Collection
    {
        return $this->children;
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
