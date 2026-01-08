<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Put;
use App\Dto\Whiteboard\WhiteboardOutput;
use App\Dto\Whiteboard\UpdateWhiteboardInput;
use App\State\WhiteboardProcessor;
use App\State\WhiteboardProvider;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity]
#[ORM\Table(name: 'whiteboard')]
#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/rooms/{roomId}/whiteboard',
            provider: WhiteboardProvider::class,
            output: WhiteboardOutput::class,
            uriVariables: ['roomId']
        ),
        new Put(
            uriTemplate: '/rooms/{roomId}/whiteboard',
            input: UpdateWhiteboardInput::class,
            output: WhiteboardOutput::class,
            processor: WhiteboardProcessor::class,
            uriVariables: ['roomId']
        )
    ],
    normalizationContext: ['groups' => ['whiteboard:read']],
    denormalizationContext: ['groups' => ['whiteboard:write']]
)]
class Whiteboard
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    #[Groups(['whiteboard:read', 'room:read'])]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'whiteboard', targetEntity: Room::class)]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    #[Groups(['whiteboard:read'])]
    private ?Room $room = null;

    #[ORM\Column(type: 'blob', nullable: true)]
    private $yjsState = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Groups(['whiteboard:read', 'whiteboard:write'])]
    private ?array $strokes = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['whiteboard:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->updatedAt = new \DateTime();
        $this->strokes = [];
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

    public function getStrokes(): ?array
    {
        return $this->strokes;
    }

    public function setStrokes(?array $strokes): self
    {
        $this->strokes = $strokes;
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
