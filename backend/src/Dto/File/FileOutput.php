<?php

namespace App\Dto\File;

use App\Dto\UserSimpleOutput;
use App\Entity\FileResource;
use Symfony\Component\Serializer\Attribute\Groups;

final class FileOutput
{
    #[Groups(['file:read'])]
    public int $id;

    #[Groups(['file:read'])]
    public string $fileName;

    #[Groups(['file:read'])]
    public ?string $filePath;

    #[Groups(['file:read'])]
    public ?string $mimeType;

    #[Groups(['file:read'])]
    public int $size;

    #[Groups(['file:read'])]
    public bool $isFolder;

    #[Groups(['file:read'])]
    public ?int $parentId;

    #[Groups(['file:read'])]
    public int $roomId;

    #[Groups(['file:read'])]
    public ?UserSimpleOutput $user = null;

    #[Groups(['file:read'])]
    public string $createdAt;

    #[Groups(['file:read'])]
    public int $childCount = 0;

    public static function fromEntity(FileResource $file): self
    {
        $output = new self();
        $output->id = $file->getId();
        $output->fileName = $file->getFileName();
        $output->filePath = $file->getFilePath();
        $output->mimeType = $file->getMimeType();
        $output->size = $file->getSize();
        $output->isFolder = $file->isFolder();
        $output->parentId = $file->getParent()?->getId();
        $output->roomId = $file->getRoom()->getId();
        $output->createdAt = $file->getCreatedAt()->format('c');
        $output->childCount = $file->getChildren()->count();

        if ($file->getUser()) {
            $output->user = UserSimpleOutput::fromEntity($file->getUser());
        }

        return $output;
    }
}
