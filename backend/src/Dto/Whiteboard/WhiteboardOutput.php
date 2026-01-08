<?php

namespace App\Dto\Whiteboard;

use App\Entity\Whiteboard;

final class WhiteboardOutput
{
    public int $id;
    public int $roomId;
    public ?array $strokes = null;
    public string $updatedAt;

    public static function fromEntity(Whiteboard $whiteboard): self
    {
        $output = new self();
        $output->id = $whiteboard->getId();
        $output->roomId = $whiteboard->getRoom()->getId();
        $output->strokes = $whiteboard->getStrokes();
        $output->updatedAt = $whiteboard->getUpdatedAt()->format('c');

        return $output;
    }
}
