<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $displayName = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $role = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $entrepriseId = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $teamId = null;

    // Getters and setters ...

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

    public function getEntrepriseId(): ?int
    {
        return $this->entrepriseId;
    }
    public function setEntrepriseId(?int $entrepriseId): self
    {
        $this->entrepriseId = $entrepriseId;
        return $this;
    }

    public function getTeamId(): ?int
    {
        return $this->teamId;
    }
    public function setTeamId(?int $teamId): self
    {
        $this->teamId = $teamId;
        return $this;
    }
}
