<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'team_room_permission')]
#[ORM\UniqueConstraint(name: 'unique_team_room', columns: ['team_id', 'room_id'])]
#[ApiResource]
class TeamRoomPermission
{
    public const ROLE_VIEWER = 'viewer';
    public const ROLE_EDITOR = 'editor';
    public const ROLE_OWNER = 'owner';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Room::class, inversedBy: 'teamPermissions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Room $room = null;

    #[ORM\ManyToOne(targetEntity: Team::class, inversedBy: 'roomPermissions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['room:read'])]
    private ?Team $team = null;

    #[ORM\Column(type: 'string', length: 20)]
    #[Groups(['room:read'])]
    private string $role = self::ROLE_VIEWER;

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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): self
    {
        $this->team = $team;
        return $this;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;
        return $this;
    }
}
