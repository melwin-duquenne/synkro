<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class AdminUpdateUserInput
{
    #[Assert\Choice(choices: ['user', 'editor', 'owner', 'admin'])]
    public ?string $role = null;

    public ?int $teamId = null;
}
