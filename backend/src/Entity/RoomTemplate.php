<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\TemplateProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'room_template')]
#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/templates',
            provider: TemplateProvider::class,
            name: 'default_templates'
        ),
        new GetCollection(
            uriTemplate: '/room-templates'
        ),
        new Get(
            uriTemplate: '/room-templates/{id}'
        )
    ],
    normalizationContext: ['groups' => ['template:read']],
    denormalizationContext: ['groups' => ['template:write']]
)]
class RoomTemplate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['template:read', 'room:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Groups(['template:read', 'template:write', 'room:read'])]
    private ?string $name = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Groups(['template:read', 'template:write'])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['template:read'])]
    private ?User $creator = null;

    #[ORM\ManyToOne(targetEntity: Entreprise::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['template:read'])]
    private ?Entreprise $entreprise = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['template:read', 'template:write'])]
    private bool $isDefault = false;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: TemplateModule::class, cascade: ['persist', 'remove'])]
    #[Groups(['template:read'])]
    private Collection $templateModules;

    #[ORM\OneToMany(mappedBy: 'template', targetEntity: Room::class)]
    private Collection $rooms;

    public function __construct()
    {
        $this->templateModules = new ArrayCollection();
        $this->rooms = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;
        return $this;
    }

    public function getEntreprise(): ?Entreprise
    {
        return $this->entreprise;
    }

    public function setEntreprise(?Entreprise $entreprise): self
    {
        $this->entreprise = $entreprise;
        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): self
    {
        $this->isDefault = $isDefault;
        return $this;
    }

    public function getTemplateModules(): Collection
    {
        return $this->templateModules;
    }

    public function addTemplateModule(TemplateModule $templateModule): self
    {
        if (!$this->templateModules->contains($templateModule)) {
            $this->templateModules->add($templateModule);
            $templateModule->setTemplate($this);
        }
        return $this;
    }

    public function removeTemplateModule(TemplateModule $templateModule): self
    {
        if ($this->templateModules->removeElement($templateModule)) {
            if ($templateModule->getTemplate() === $this) {
                $templateModule->setTemplate(null);
            }
        }
        return $this;
    }

    public function getRooms(): Collection
    {
        return $this->rooms;
    }
}
