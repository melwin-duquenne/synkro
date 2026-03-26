<?php

namespace App\Dto\Invitation;

use Symfony\Component\Validator\Constraints as Assert;

final class SendInvitationInput
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email]
    public string $email;
}
