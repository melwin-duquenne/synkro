<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class ResetPasswordInput
{
    #[Assert\NotBlank(message: 'Le token est requis')]
    public string $token;

    #[Assert\NotBlank(message: 'Le mot de passe est requis')]
    #[Assert\Length(min: 6, minMessage: 'Le mot de passe doit contenir au moins 6 caractères')]
    public string $password;
}
