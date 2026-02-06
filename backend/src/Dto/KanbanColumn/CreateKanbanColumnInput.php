<?php

namespace App\Dto\KanbanColumn;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateKanbanColumnInput
{
    #[Assert\NotBlank(message: 'Name is required')]
    #[Assert\Length(max: 255)]
    public string $name;

    #[Assert\Length(max: 50)]
    public string $color = 'bg-slate-500';
}
