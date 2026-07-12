<?php

namespace App\Dto\Ai;

use Symfony\Component\Validator\Constraints as Assert;

class AiChatInput
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 4000)]
    public string $message = '';

    #[Assert\NotBlank]
    public string $module = 'general';
}
