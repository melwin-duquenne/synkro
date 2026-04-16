<?php

namespace App\Dto\Account;

use App\Dto\TeamSimpleOutput;
use App\Dto\UserEntrepriseOutput;
use App\Entity\User;
use App\Entity\UserEntreprise;

final class ProfileOutput
{
    public int $id;
    public string $email;
    public string $displayName;
    public string $role;
    public ?string $avatarUrl = null;
    public ?TeamSimpleOutput $team = null;
    /** @var UserEntrepriseOutput[] */
    public array $entreprises = [];

    public static function fromEntity(User $user): self
    {
        $output = new self();
        $output->id = $user->getId();
        $output->email = $user->getEmail();
        $output->displayName = $user->getDisplayName();
        $output->role = $user->getRole();
        $output->avatarUrl = $user->getAvatarPath();

        if ($user->getTeam()) {
            $output->team = TeamSimpleOutput::fromEntity($user->getTeam());
        }

        $output->entreprises = array_map(
            fn(UserEntreprise $ue) => UserEntrepriseOutput::fromMembership($ue),
            $user->getUserEntreprises()->toArray()
        );

        return $output;
    }
}
