<?php

namespace App\Dto\Invitation;

use App\Dto\UserSimpleOutput;
use App\Entity\Invitation;

final class InvitationOutput
{
    public int $id;
    public string $email;
    public string $status;
    public string $createdAt;
    public string $expiresAt;
    public ?UserSimpleOutput $invitedBy = null;

    public static function fromEntity(Invitation $invitation): self
    {
        $output = new self();
        $output->id = $invitation->getId();
        $output->email = $invitation->getEmail();
        $output->status = $invitation->getStatus();
        $output->createdAt = $invitation->getCreatedAt()->format('c');
        $output->expiresAt = $invitation->getExpiresAt()->format('c');

        if ($invitation->getInvitedBy()) {
            $output->invitedBy = UserSimpleOutput::fromEntity($invitation->getInvitedBy());
        }

        return $output;
    }
}
