<?php

namespace App\Dto\Document;

use App\Entity\Document;

final class DocumentOutput
{
    public int $id;
    public int $roomId;
    public ?string $contentHtml = null;
    public string $updatedAt;

    public static function fromEntity(Document $document): self
    {
        $output = new self();
        $output->id = $document->getId();
        $output->roomId = $document->getRoom()->getId();
        $output->contentHtml = $document->getContentHtml();
        $output->updatedAt = $document->getUpdatedAt()->format('c');

        return $output;
    }
}
