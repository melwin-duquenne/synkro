<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'module')]
#[ApiResource(
    normalizationContext: ['groups' => ['module:read']],
    denormalizationContext: ['groups' => ['module:write']]
)]
class Module
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['module:read', 'room:read', 'template:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['module:read', 'module:write', 'room:read', 'template:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 50, unique: true)]
    #[Groups(['module:read', 'module:write', 'room:read', 'template:read'])]
    private ?string $code = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['module:read', 'module:write'])]
    private ?string $description = null;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: ModuleRoom::class)]
    private Collection $moduleRooms;

    #[ORM\OneToMany(mappedBy: 'module', targetEntity: TemplateModule::class)]
    private Collection $templateModules;

    public function __construct()
    {
        $this->moduleRooms = new ArrayCollection();
        $this->templateModules = new ArrayCollection();
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

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;
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

    public function getModuleRooms(): Collection
    {
        return $this->moduleRooms;
    }

    public function getTemplateModules(): Collection
    {
        return $this->templateModules;
    }
}
