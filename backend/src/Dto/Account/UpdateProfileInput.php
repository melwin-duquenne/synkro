<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateProfileInput
{
    #[Assert\Length(max: 255)]
    public ?string $displayName = null;

    #[Assert\Email]
    public ?string $email = null;
}
