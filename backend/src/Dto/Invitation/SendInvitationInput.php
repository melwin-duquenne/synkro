<?php

namespace App\Dto\Invitation;

use App\Entity\User;
use Symfony\Component\Validator\Constraints as Assert;

final class SendInvitationInput
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email]
    public string $email;

    #[Assert\Choice(choices: [User::ROLE_USER, User::ROLE_EDITOR, User::ROLE_OWNER])]
    public string $role = User::ROLE_USER;
}
