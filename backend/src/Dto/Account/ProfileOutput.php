<?php

namespace App\Dto\Account;

use App\Dto\EntrepriseSimpleOutput;
use App\Dto\TeamSimpleOutput;
use App\Entity\User;

final class ProfileOutput
{
    public int $id;
    public string $email;
    public string $displayName;
    public string $role;
    public ?string $avatarUrl = null;
    public ?EntrepriseSimpleOutput $entreprise = null;
    public ?TeamSimpleOutput $team = null;

    public static function fromEntity(User $user): self
    {
        $output = new self();
        $output->id = $user->getId();
        $output->email = $user->getEmail();
        $output->displayName = $user->getDisplayName();
        $output->role = $user->getRole();
        $output->avatarUrl = $user->getAvatarPath();

        if ($user->getEntreprise()) {
            $output->entreprise = EntrepriseSimpleOutput::fromEntity($user->getEntreprise());
        }

        if ($user->getTeam()) {
            $output->team = TeamSimpleOutput::fromEntity($user->getTeam());
        }

        return $output;
    }
}
