<?php

namespace App\Dto\Invitation;

use Symfony\Component\Validator\Constraints as Assert;

final class AcceptInvitationInput
{
    #[Assert\NotBlank(message: 'Token is required')]
    public string $token;
}
