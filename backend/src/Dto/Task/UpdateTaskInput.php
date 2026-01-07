<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateTaskInput
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $description = null;

    #[Assert\Choice(choices: ['todo', 'in_progress', 'done'])]
    public ?string $status = null;

    public ?int $position = null;

    public ?int $assignedToId = null;
}
