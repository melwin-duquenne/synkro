<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTaskInput
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    #[Assert\Choice(choices: ['todo', 'in_progress', 'done'])]
    public string $status = 'todo';

    public ?int $assignedToId = null;

    #[Assert\NotNull]
    public int $roomId;
}
