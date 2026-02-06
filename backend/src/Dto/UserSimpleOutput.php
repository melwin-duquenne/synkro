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
    public ?string $email = null;

    public static function fromEntity(?User $user, bool $includeEmail = false): ?self
    {
        if (!$user) {
            return null;
        }

        $output = new self();
        $output->id = $user->getId();
        $output->displayName = $user->getDisplayName();
        if ($includeEmail) {
            $output->email = $user->getEmail();
        }

        return $output;
    }
}
