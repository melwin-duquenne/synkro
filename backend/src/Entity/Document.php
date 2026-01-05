<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'document')]
#[ApiResource(
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
    private ?string $contentMarkdown = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $yjsState = null;

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

    public function getContentMarkdown(): ?string
    {
        return $this->contentMarkdown;
    }

    public function setContentMarkdown(?string $contentMarkdown): self
    {
        $this->contentMarkdown = $contentMarkdown;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getYjsState()
    {
        return $this->yjsState;
    }

    public function setYjsState($yjsState): self
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
