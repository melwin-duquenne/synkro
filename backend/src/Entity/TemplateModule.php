<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'template_module')]
#[ORM\UniqueConstraint(name: 'unique_template_module', columns: ['template_id', 'module_id'])]
#[ApiResource]
class TemplateModule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['template:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: RoomTemplate::class, inversedBy: 'templateModules')]
    #[ORM\JoinColumn(nullable: false)]
    private ?RoomTemplate $template = null;

    #[ORM\ManyToOne(targetEntity: Module::class, inversedBy: 'templateModules')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['template:read'])]
    private ?Module $module = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?RoomTemplate
    {
        return $this->template;
    }

    public function setTemplate(?RoomTemplate $template): self
    {
        $this->template = $template;
        return $this;
    }

    public function getModule(): ?Module
    {
        return $this->module;
    }

    public function setModule(?Module $module): self
    {
        $this->module = $module;
        return $this;
    }
}
