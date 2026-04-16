<?php

namespace App\Dto\Account;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateEntrepriseInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 2, max: 255)]
    public string $name;

    #[Assert\Length(max: 255)]
    public ?string $domain = null;
}
