<?php

namespace App\Dto\KanbanColumn;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateKanbanColumnInput
{
    #[Assert\Length(max: 255)]
    public ?string $name = null;

    #[Assert\Length(max: 50)]
    public ?string $color = null;
}
