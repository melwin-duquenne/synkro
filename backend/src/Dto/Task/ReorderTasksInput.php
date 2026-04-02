<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class ReorderTasksInput
{
    #[Assert\NotBlank(message: 'La liste des tâches est requise')]
    #[Assert\All([
        new Assert\Collection([
            'id' => new Assert\NotNull(),
            'columnId' => new Assert\NotNull(),
            'position' => new Assert\NotNull()
        ])
    ])]
    public array $tasks = [];
}
