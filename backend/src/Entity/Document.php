<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Dto\Document\DocumentOutput;
use App\Dto\Document\UpdateDocumentInput;
use App\State\DocumentProcessor;
use App\State\DocumentProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'document')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/rooms/{roomId}/document',
            provider: DocumentProvider::class,
            output: DocumentOutput::class,
            uriVariables: ['roomId']
        ),
        new Put(
            uriTemplate: '/rooms/{roomId}/document',
            input: UpdateDocumentInput::class,
            output: DocumentOutput::class,
            processor: DocumentProcessor::class,
            uriVariables: ['roomId']
        )
    ],
    normalizationContext: ['groups' => ['document:read']],
    denormalizationContext: ['groups' => ['document:write']]
)]
class Document
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['document:read', 'room:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'document', targetEntity: Room::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Groups(['document:read'])]
    private ?Room $room = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['document:read', 'document:write'])]
    private ?string $contentHtml = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private mixed $yjsState = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['document:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
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

    public function getContentHtml(): ?string
    {
        return $this->contentHtml;
    }

    public function setContentHtml(?string $contentHtml): self
    {
        $this->contentHtml = $contentHtml;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getYjsState(): mixed
    {
        return $this->yjsState;
    }

    public function setYjsState(mixed $yjsState): self
    {
        $this->yjsState = $yjsState;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
