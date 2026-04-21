<?php

namespace App\Dto\Entreprise;

use Symfony\Component\Validator\Constraints as Assert;

class AiSettingsInput
{
    public ?bool $aiEnabled = null;

    #[Assert\Choice(choices: ['byok', 'platform'])]
    public ?string $aiMode = null;

    #[Assert\Choice(choices: ['mistral'])]
    #[Assert\Length(max: 50)]
    public ?string $aiProvider = null;

    #[Assert\Length(max: 512)]
    public ?string $aiApiKey = null;
}
