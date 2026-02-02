<?php

namespace App\Dto\Invitation;

use Symfony\Component\Validator\Constraints as Assert;

final class SendInvitationInput
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email]
    public string $email;
}
