<?php

namespace App\Dto\Entreprise;

use Symfony\Component\Validator\Constraints as Assert;

class AiSettingsInput
{
    public ?bool $aiEnabled = null;

    #[Assert\Length(max: 50)]
    public ?string $aiProvider = null;

    public ?string $aiApiKey = null;
}
