<?php

namespace App\Dto\Task;

use App\Dto\UserSimpleOutput;
use App\Entity\Task;

final class TaskOutput
{
    public int $id;
    public string $title;
    public ?string $description;
    public int $columnId;
    public string $columnName;
    public string $columnColor;
    public string $type;
    public int $position;
    public ?UserSimpleOutput $assignedTo = null;
    public ?int $estimation = null;
    public string $createdAt;
    public int $roomId;

    public static function fromEntity(Task $task): self
    {
        $output = new self();
        $output->id = $task->getId();
        $output->title = $task->getTitle();
        $output->description = $task->getDescription();
        $output->columnId = $task->getColumn()->getId();
        $output->columnName = $task->getColumn()->getName();
        $output->columnColor = $task->getColumn()->getColor();
        $output->type = $task->getType();
        $output->position = $task->getPosition();
        $output->createdAt = $task->getCreatedAt()->format('c');
        $output->roomId = $task->getRoom()->getId();
        $output->estimation = $task->getEstimation();

        if ($task->getAssignedTo()) {
            $output->assignedTo = UserSimpleOutput::fromEntity($task->getAssignedTo());
        }

        return $output;
    }
}
