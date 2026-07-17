<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'entreprise')]
#[ApiResource(
    normalizationContext: ['groups' => ['entreprise:read']],
    denormalizationContext: ['groups' => ['entreprise:write']]
)]
class Entreprise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['entreprise:read', 'user:read', 'team:read', 'room:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['entreprise:read', 'entreprise:write', 'user:read', 'team:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: true)]
    #[Groups(['entreprise:read', 'user:read'])]
    private ?string $slug = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['entreprise:read', 'entreprise:write'])]
    private ?string $domain = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['entreprise:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Team::class)]
    private Collection $teams;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: Room::class)]
    private Collection $rooms;

    #[ORM\OneToMany(mappedBy: 'entreprise', targetEntity: CalendarEvent::class)]
    private Collection $calendarEvents;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['entreprise:read', 'user:read'])]
    private bool $aiEnabled = false;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Groups(['entreprise:read', 'user:read'])]
    private ?string $aiProvider = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $aiApiKey = null;

    #[ORM\Column(type: 'string', length: 20, options: ['default' => 'byok'])]
    #[Groups(['entreprise:read', 'user:read'])]
    private string $aiMode = 'byok';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['entreprise:read', 'user:read'])]
    private int $aiTokensUsed = 0;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Groups(['entreprise:read', 'user:read'])]
    private ?int $aiTokensLimit = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $aiTokensResetAt = null;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->rooms = new ArrayCollection();
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(?string $domain): self
    {
        $this->domain = $domain;
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

    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(Team $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setEntreprise($this);
        }
        return $this;
    }

    public function removeTeam(Team $team): self
    {
        if ($this->teams->removeElement($team)) {
            if ($team->getEntreprise() === $this) {
                $team->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Room $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms->add($room);
            $room->setEntreprise($this);
        }
        return $this;
    }

    public function removeRoom(Room $room): self
    {
        if ($this->rooms->removeElement($room)) {
            if ($room->getEntreprise() === $this) {
                $room->setEntreprise(null);
            }
        }
        return $this;
    }

    public function getCalendarEvents(): Collection
    {
        return $this->calendarEvents;
    }

    public function isAiEnabled(): bool
    {
        return $this->aiEnabled;
    }

    public function setAiEnabled(bool $aiEnabled): self
    {
        $this->aiEnabled = $aiEnabled;
        return $this;
    }

    public function getAiProvider(): ?string
    {
        return $this->aiProvider;
    }

    public function setAiProvider(?string $aiProvider): self
    {
        $this->aiProvider = $aiProvider;
        return $this;
    }

    public function getAiApiKey(): ?string
    {
        return $this->aiApiKey;
    }

    public function setAiApiKey(?string $aiApiKey): self
    {
        $this->aiApiKey = $aiApiKey;
        return $this;
    }

    public function getAiMode(): string
    {
        return $this->aiMode;
    }

    public function setAiMode(string $aiMode): self
    {
        $this->aiMode = $aiMode;
        return $this;
    }

    public function getAiTokensUsed(): int
    {
        return $this->aiTokensUsed;
    }

    public function setAiTokensUsed(int $aiTokensUsed): self
    {
        $this->aiTokensUsed = $aiTokensUsed;
        return $this;
    }

    public function getAiTokensLimit(): ?int
    {
        return $this->aiTokensLimit;
    }

    public function setAiTokensLimit(?int $aiTokensLimit): self
    {
        $this->aiTokensLimit = $aiTokensLimit;
        return $this;
    }

    public function getAiTokensResetAt(): ?\DateTimeImmutable
    {
        return $this->aiTokensResetAt;
    }

    public function setAiTokensResetAt(?\DateTimeImmutable $aiTokensResetAt): self
    {
        $this->aiTokensResetAt = $aiTokensResetAt;
        return $this;
    }

    public function hasReachedTokenLimit(): bool
    {
        if ($this->aiTokensLimit === null) {
            return false;
        }
        return $this->aiTokensUsed >= $this->aiTokensLimit;
    }
}
