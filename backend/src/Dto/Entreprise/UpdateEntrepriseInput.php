<?php

namespace App\Dto\Entreprise;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateEntrepriseInput
{
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(max: 255)]
    public string $name;

    public ?bool $aiEnabled = null;

    #[Assert\Length(max: 50)]
    public ?string $aiProvider = null;

    public ?string $aiApiKey = null;
}
