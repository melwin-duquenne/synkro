<?php

namespace App\Dto\Calendar;

use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCalendarEventInput
{
    #[Assert\Length(max: 255)]
    public ?string $title = null;

    public ?string $description = null;

    #[Assert\Choice(choices: ['meeting', 'absence', 'blocked', 'reminder', 'other'])]
    public ?string $eventType = null;

    public ?string $startDate = null;

    public ?string $endDate = null;

    public ?bool $isAllDay = null;

    public ?string $recurrence = null;

    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Invalid color format')]
    public ?string $color = null;

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    public ?bool $isPrivate = null;

    /** Array of user IDs to set as participants (replaces existing) */
    public ?array $participantIds = null;
}
