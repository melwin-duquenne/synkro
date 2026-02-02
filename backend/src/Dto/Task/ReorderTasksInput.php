<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class ReorderTasksInput
{
    #[Assert\NotNull]
    public int $roomId;

    #[Assert\NotBlank(message: 'Tasks array is required')]
    #[Assert\All([
        new Assert\Collection([
            'id' => new Assert\NotNull(),
            'status' => new Assert\Choice(choices: ['todo', 'in_progress', 'done']),
            'position' => new Assert\NotNull()
        ])
    ])]
    public array $tasks = [];
}
