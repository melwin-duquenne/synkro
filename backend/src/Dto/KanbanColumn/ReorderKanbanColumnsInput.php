<?php

namespace App\Dto\KanbanColumn;

use Symfony\Component\Validator\Constraints as Assert;

final class ReorderKanbanColumnsInput
{
    #[Assert\NotBlank(message: 'Columns array is required')]
    #[Assert\All([
        new Assert\Collection([
            'id' => new Assert\NotNull(),
            'position' => new Assert\NotNull()
        ])
    ])]
    public array $columns = [];
}
