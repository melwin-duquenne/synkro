<?php

namespace App\Dto\File;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateFileInput
{
    #[Assert\Length(max: 255)]
    #[Groups(['file:write'])]
    public ?string $fileName = null;

    #[Groups(['file:write'])]
    public ?int $parentId = null;

    #[Groups(['file:write'])]
    public bool $moveToRoot = false;
}
