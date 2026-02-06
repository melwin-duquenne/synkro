<?php

namespace App\Dto\Room;

use App\Entity\User;

final class RoomMemberOutput
{
    public int $id;
    public string $displayName;
    public ?string $email = null;
    public bool $isCreator;

    public static function fromEntity(User $user, bool $isCreator = false): self
    {
        $output = new self();
        $output->id = $user->getId();
        $output->displayName = $user->getDisplayName();
        $output->email = $user->getEmail();
        $output->isCreator = $isCreator;
        return $output;
    }
}
