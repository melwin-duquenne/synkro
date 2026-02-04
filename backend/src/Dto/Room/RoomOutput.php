<?php

namespace App\Dto\Room;

use App\Dto\EntrepriseSimpleOutput;
use App\Dto\ModuleRoomOutput;
use App\Dto\UserSimpleOutput;
use App\Entity\Room;

final class RoomOutput
{
    public int $id;
    public string $name;
    public string $visibility;
    public bool $isTemporary;
    public string $createdAt;
    public string $layoutType;
    public UserSimpleOutput $creator;
    /** @var ModuleRoomOutput[] */
    public array $moduleRooms = [];
    public ?EntrepriseSimpleOutput $entreprise = null;

    public static function fromEntity(Room $room, bool $detailed = false): self
    {
        $output = new self();
        $output->id = $room->getId();
        $output->name = $room->getName();
        $output->visibility = $room->getVisibility();
        $output->isTemporary = $room->isTemporary();
        $output->createdAt = $room->getCreatedAt()->format('c');
        $output->layoutType = $room->getLayoutType();
        $output->creator = UserSimpleOutput::fromEntity($room->getCreator());

        $output->moduleRooms = array_map(
            fn($mr) => ModuleRoomOutput::fromEntity($mr),
            $room->getModuleRooms()->toArray()
        );

        if ($detailed && $room->getEntreprise()) {
            $output->entreprise = EntrepriseSimpleOutput::fromEntity($room->getEntreprise());
        }

        return $output;
    }
}
