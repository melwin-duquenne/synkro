<?php

namespace App\Dto\Auth;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterInput
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email]
    public string $email;

    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins 6 caractères')]
    public string $password;

    #[Assert\NotBlank(message: 'Le nom d\'affichage est requis')]
    #[Assert\Length(max: 255)]
    public string $displayName;

    public ?string $companyName = null;

    public string $role = 'user';
}
