<?php

namespace App\Dto\Calendar;

use App\Entity\CalendarEventParticipant;

final class ParticipantOutput
{
    public int $id;
    public int $userId;
    public string $displayName;
    public string $status;

    public static function fromEntity(CalendarEventParticipant $participant): self
    {
        $output = new self();
        $output->id = $participant->getId();
        $output->userId = $participant->getUser()->getId();
        $output->displayName = $participant->getUser()->getDisplayName();
        $output->status = $participant->getStatus();

        return $output;
    }
}
