<?php

namespace App\Dto\KanbanColumn;

use App\Entity\KanbanColumn;
use App\Entity\Task;

final class KanbanColumnOutput
{
    public int $id;
    public string $name;
    public string $color;
    public int $position;
    public int $roomId;
    public int $taskCount;

    public static function fromEntity(KanbanColumn $column): self
    {
        $output = new self();
        $output->id = $column->getId();
        $output->name = $column->getName();
        $output->color = $column->getColor();
        $output->position = $column->getPosition();
        $output->roomId = $column->getRoom()->getId();
        $output->taskCount = $column->getTasks()->filter(
            fn(Task $task) => $task->getType() === Task::TYPE_ACTIVE
        )->count();

        return $output;
    }
}
