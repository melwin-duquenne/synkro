<?php

namespace App\Dto;

use App\Entity\User;

/**
 * Simple user representation for nested relations
 */
final class UserSimpleOutput
{
    public int $id;
    public string $displayName;

    public static function fromEntity(?User $user): ?self
    {
        if (!$user) {
            return null;
        }

        $output = new self();
        $output->id = $user->getId();
        $output->displayName = $user->getDisplayName();

        return $output;
    }
}
