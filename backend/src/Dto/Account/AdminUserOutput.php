<?php

namespace App\Dto\Account;

use App\Dto\TeamSimpleOutput;
use App\Entity\User;

final class AdminUserOutput
{
    public int $id;
    public string $email;
    public string $displayName;
    public string $role;
    public ?string $avatarUrl = null;
    public ?TeamSimpleOutput $team = null;
    public string $createdAt;

    public static function fromEntity(User $user): self
    {
        $output = new self();
        $output->id = $user->getId();
        $output->email = $user->getEmail();
        $output->displayName = $user->getDisplayName();
        $output->role = $user->getRole();
        $output->avatarUrl = $user->getAvatarPath();
        $output->createdAt = $user->getCreatedAt()->format('c');

        if ($user->getTeam()) {
            $output->team = TeamSimpleOutput::fromEntity($user->getTeam());
        }

        return $output;
    }
}
