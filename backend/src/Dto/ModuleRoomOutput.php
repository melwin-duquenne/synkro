<?php

namespace App\Dto;

use App\Entity\ModuleRoom;

/**
 * ModuleRoom representation
 */
final class ModuleRoomOutput
{
    public int $id;
    public ModuleSimpleOutput $module;
    public ?array $configJson = null;

    public static function fromEntity(ModuleRoom $moduleRoom): self
    {
        $output = new self();
        $output->id = $moduleRoom->getId();
        $output->module = ModuleSimpleOutput::fromEntity($moduleRoom->getModule());
        $output->configJson = $moduleRoom->getConfigJson();

        return $output;
    }
}
