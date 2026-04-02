<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class RequestResetPasswordInput
{
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email]
    public string $email;
}
