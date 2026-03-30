<?php

namespace App\Dto\Room;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoomInput
{
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank(message: 'Au moins un module est requis')]
    #[Assert\Count(min: 1, minMessage: 'Au moins un module est requis')]
    public array $modules = [];

    #[Assert\Choice(choices: ['enterprise', 'private'])]
    public string $visibility = 'enterprise';

    public bool $isTemporary = false;

    /** @var int[] User IDs to invite (for private rooms) */
    public array $memberIds = [];
}
