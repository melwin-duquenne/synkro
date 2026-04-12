<?php

namespace App\Dto\File;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateFolderInput
{
    #[Assert\NotBlank(message: 'Le nom du dossier est requis')]
    #[Assert\Length(max: 255)]
    #[Groups(['file:write'])]
    public string $name;

    #[Groups(['file:write'])]
    public ?int $parentId = null;
}
