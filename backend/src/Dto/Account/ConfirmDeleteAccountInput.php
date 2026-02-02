<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmDeleteAccountInput
{
    #[Assert\NotBlank(message: 'Token is required')]
    public string $token;
}
