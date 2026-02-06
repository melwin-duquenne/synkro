<?php

namespace App\Dto\Room;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateRoomInput
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\NotBlank(message: 'At least one module is required')]
    #[Assert\Count(min: 1, minMessage: 'At least one module is required')]
    public array $modules = [];

    #[Assert\Choice(choices: ['enterprise', 'private'])]
    public string $visibility = 'enterprise';

    public bool $isTemporary = false;

    /** @var int[] User IDs to invite (for private rooms) */
    public array $memberIds = [];
}
