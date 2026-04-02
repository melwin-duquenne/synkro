<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'message')]
#[ORM\Index(name: 'idx_message_room', columns: ['room_id'])]
#[ORM\Index(name: 'idx_message_created', columns: ['created_at'])]
#[ApiResource(
    normalizationContext: ['groups' => ['message:read']],
    denormalizationContext: ['groups' => ['message:write']]
)]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['message:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read', 'message:write'])]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['message:read'])]
    private ?User $user = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['message:read', 'message:write'])]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['message:read'])]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;
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
