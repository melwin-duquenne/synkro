<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterInput
{
    #[Assert\NotBlank(message: 'Email is required')]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(message: 'Password is required')]
    #[Assert\Length(min: 6, minMessage: 'Password must be at least 6 characters')]
    public string $password;

    #[Assert\NotBlank(message: 'Display name is required')]
    #[Assert\Length(max: 255)]
    public string $displayName;

    public ?string $companyName = null;

    public string $role = 'user';
}
