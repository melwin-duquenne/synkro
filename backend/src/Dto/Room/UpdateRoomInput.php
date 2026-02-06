<?php

namespace App\Dto\Room;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateRoomInput
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Choice(choices: ['enterprise', 'private'])]
    public ?string $visibility = null;

    public ?string $layoutType = null;
}
