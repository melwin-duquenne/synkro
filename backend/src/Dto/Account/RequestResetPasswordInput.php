<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestResetPasswordInput
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email]
    public string $email;
}
