<?php

namespace App\Dto;

use App\Entity\Module;

/**
 * Simple module representation for nested relations
 */
final class ModuleSimpleOutput
{
    public int $id;
    public string $code;
    public string $name;

    public static function fromEntity(Module $module): self
    {
        $output = new self();
        $output->id = $module->getId();
        $output->code = $module->getCode();
        $output->name = $module->getName();

        return $output;
    }
}
