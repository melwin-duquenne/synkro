<?php

namespace App\Dto\Task;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateTaskInput
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    public ?int $columnId = null;

    public ?int $assignedToId = null;

    #[Assert\PositiveOrZero]
    public ?int $estimation = null;
}
