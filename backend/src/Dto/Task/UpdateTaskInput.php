<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTaskInput
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $description = null;

    public ?int $columnId = null;

    public ?int $position = null;

    public ?int $assignedToId = null;

    #[Assert\PositiveOrZero]
    public ?int $estimation = null;

    #[Assert\Choice(choices: ['active', 'archived'])]
    public ?string $type = null;
}
