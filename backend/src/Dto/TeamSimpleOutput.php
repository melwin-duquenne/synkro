<?php

namespace App\Dto;

use App\Entity\Team;

/**
 * Simple team representation for nested relations
 */
final class TeamSimpleOutput
{
    public int $id;
    public string $name;

    public static function fromEntity(?Team $team): ?self
    {
        if (!$team) {
            return null;
        }

        $output = new self();
        $output->id = $team->getId();
        $output->name = $team->getName();

        return $output;
    }
}
