<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']]
)]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'user';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_OWNER = 'owner';
    public const ROLE_ADMIN = 'admin';

    private const ROLE_HIERARCHY = [
        self::ROLE_USER,
        self::ROLE_EDITOR,
        self::ROLE_OWNER,
        self::ROLE_ADMIN,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['user:read', 'room:read', 'message:read', 'task:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['user:read', 'user:write', 'room:read', 'message:read', 'task:read'])]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 50)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $role = 'user';

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?Team $team = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true)]
    #[Groups(['user:read'])]
    private ?string $avatarPath = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetPasswordExpiresAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $deleteAccountToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deleteAccountExpiresAt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $firstName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $lastName = null;

    #[ORM\OneToMany(mappedBy: 'creator', targetEntity: Room::class)]
    private Collection $createdRooms;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Message::class)]
    private Collection $messages;

    #[ORM\OneToMany(mappedBy: 'assignedTo', targetEntity: Task::class)]
    private Collection $assignedTasks;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: CalendarEvent::class)]
    private Collection $calendarEvents;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserRoomPermission::class)]
    private Collection $roomPermissions;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: FileResource::class)]
    private Collection $files;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserEntreprise::class, cascade: ['persist', 'remove'])]
    private Collection $userEntreprises;

    public function __construct()
    {
        $this->createdRooms = new ArrayCollection();
        $this->messages = new ArrayCollection();
        $this->assignedTasks = new ArrayCollection();
        $this->calendarEvents = new ArrayCollection();
        $this->roomPermissions = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->userEntreprises = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getDisplayName(): ?string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
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

    public function getAvatarPath(): ?string
    {
        return $this->avatarPath;
    }

    public function setAvatarPath(?string $avatarPath): self
    {
        $this->avatarPath = $avatarPath;
        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): self
    {
        $this->resetPasswordToken = $resetPasswordToken;
        return $this;
    }

    public function getResetPasswordExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetPasswordExpiresAt;
    }

    public function setResetPasswordExpiresAt(?\DateTimeInterface $resetPasswordExpiresAt): self
    {
        $this->resetPasswordExpiresAt = $resetPasswordExpiresAt;
        return $this;
    }

    public function getDeleteAccountToken(): ?string
    {
        return $this->deleteAccountToken;
    }

    public function setDeleteAccountToken(?string $deleteAccountToken): self
    {
        $this->deleteAccountToken = $deleteAccountToken;
        return $this;
    }

    public function getDeleteAccountExpiresAt(): ?\DateTimeInterface
    {
        return $this->deleteAccountExpiresAt;
    }

    public function setDeleteAccountExpiresAt(?\DateTimeInterface $deleteAccountExpiresAt): self
    {
        $this->deleteAccountExpiresAt = $deleteAccountExpiresAt;
        return $this;
    }

    public function getCreatedRooms(): Collection
    {
        return $this->createdRooms;
    }

    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function getAssignedTasks(): Collection
    {
        return $this->assignedTasks;
    }

    public function getCalendarEvents(): Collection
    {
        return $this->calendarEvents;
    }

    public function getRoomPermissions(): Collection
    {
        return $this->roomPermissions;
    }

    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function getUserEntreprises(): Collection
    {
        return $this->userEntreprises;
    }

    public function addToEntreprise(Entreprise $entreprise, string $role): self
    {
        foreach ($this->userEntreprises as $ue) {
            if ($ue->getEntreprise() === $entreprise) {
                $ue->setRole($role);
                return $this;
            }
        }

        $ue = new UserEntreprise();
        $ue->setUser($this);
        $ue->setEntreprise($entreprise);
        $ue->setRole($role);
        $this->userEntreprises->add($ue);
        return $this;
    }

    public function removeFromEntreprise(Entreprise $entreprise): self
    {
        foreach ($this->userEntreprises as $ue) {
            if ($ue->getEntreprise() === $entreprise) {
                $this->userEntreprises->removeElement($ue);
                return $this;
            }
        }
        return $this;
    }

    public function hasEntreprise(Entreprise $entreprise): bool
    {
        foreach ($this->userEntreprises as $ue) {
            if ($ue->getEntreprise()->getId() === $entreprise->getId()) {
                return true;
            }
        }
        return false;
    }

    public function getRoleInEntreprise(Entreprise $entreprise): ?string
    {
        foreach ($this->userEntreprises as $ue) {
            if ($ue->getEntreprise()->getId() === $entreprise->getId()) {
                return $ue->getRole();
            }
        }
        return null;
    }

    public function isAtLeast(string $role): bool
    {
        $userLevel = array_search($this->role, self::ROLE_HIERARCHY);
        $requiredLevel = array_search($role, self::ROLE_HIERARCHY);
        if ($userLevel === false || $requiredLevel === false) {
            return false;
        }
        return $userLevel >= $requiredLevel;
    }

    public function isAtLeastInEntreprise(Entreprise $entreprise, string $minRole): bool
    {
        $role = $this->getRoleInEntreprise($entreprise) ?? self::ROLE_USER;
        $userLevel = array_search($role, self::ROLE_HIERARCHY);
        $requiredLevel = array_search($minRole, self::ROLE_HIERARCHY);
        if ($userLevel === false || $requiredLevel === false) {
            return false;
        }
        return $userLevel >= $requiredLevel;
    }

    public function canAssignRole(string $targetRole): bool
    {
        // Admin peut tout attribuer
        if ($this->role === self::ROLE_ADMIN) {
            return true;
        }
        // Owner peut attribuer user/editor/owner (pas admin)
        if ($this->role === self::ROLE_OWNER && $targetRole !== self::ROLE_ADMIN) {
            return true;
        }
        return false;
    }

    // UserInterface methods
    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->isAtLeast(self::ROLE_EDITOR)) {
            $roles[] = 'ROLE_EDITOR';
        }
        if ($this->isAtLeast(self::ROLE_OWNER)) {
            $roles[] = 'ROLE_OWNER';
        }
        if ($this->role === self::ROLE_ADMIN) {
            $roles[] = 'ROLE_ADMIN';
        }
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // Nothing to erase
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): self
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;
        return $this;
    }
}
