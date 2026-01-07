<?php

namespace App\Dto\Calendar;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCalendarEventInput
{
    #[Assert\NotBlank(message: 'Title is required')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    #[Assert\Choice(choices: ['meeting', 'absence', 'blocked', 'reminder', 'other'])]
    public string $eventType = 'other';

    #[Assert\NotBlank(message: 'Start date is required')]
    public string $startDate;

    #[Assert\NotBlank(message: 'End date is required')]
    public string $endDate;

    public bool $isAllDay = false;

    public ?string $recurrence = null;

    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Invalid color format')]
    public ?string $color = null;

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    public bool $isPrivate = false;

    /** Room ID - null for personal calendar */
    public ?int $roomId = null;

    /** Array of user IDs to add as participants */
    public array $participantIds = [];
}
