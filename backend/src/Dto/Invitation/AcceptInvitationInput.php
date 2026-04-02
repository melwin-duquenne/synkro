<?php

namespace App\Dto\Invitation;

use Symfony\Component\Validator\Constraints as Assert;

final class AcceptInvitationInput
{
    #[Assert\NotBlank(message: 'Le token est requis')]
    public string $token;
}
