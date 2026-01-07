<?php

namespace App\Dto\Task;

use App\Dto\UserSimpleOutput;
use App\Entity\Task;

final class TaskOutput
{
    public int $id;
    public string $title;
    public ?string $description;
    public string $status;
    public int $position;
    public ?UserSimpleOutput $assignedTo = null;
    public string $createdAt;
    public int $roomId;

    public static function fromEntity(Task $task): self
    {
        $output = new self();
        $output->id = $task->getId();
        $output->title = $task->getTitle();
        $output->description = $task->getDescription();
        $output->status = $task->getStatus();
        $output->position = $task->getPosition();
        $output->createdAt = $task->getCreatedAt()->format('c');
        $output->roomId = $task->getRoom()->getId();

        if ($task->getAssignedTo()) {
            $output->assignedTo = UserSimpleOutput::fromEntity($task->getAssignedTo());
        }

        return $output;
    }
}
