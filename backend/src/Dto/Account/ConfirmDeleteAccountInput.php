<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class ConfirmDeleteAccountInput
{
    #[Assert\NotBlank(message: 'Le token est requis')]
    public string $token;
}
