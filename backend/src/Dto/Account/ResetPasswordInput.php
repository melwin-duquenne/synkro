<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordInput
{
    #[Assert\NotBlank(message: 'Token is required')]
    public string $token;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least 6 characters')]
    public string $password;
}
