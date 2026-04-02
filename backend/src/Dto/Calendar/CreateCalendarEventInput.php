<?php

namespace App\Dto\Calendar;

use Symfony\Component\Validator\Constraints as Assert;

final class CreateCalendarEventInput
{
    #[Assert\NotBlank(message: 'Le titre est requis')]
    #[Assert\Length(max: 255)]
    public string $title;

    public ?string $description = null;

    #[Assert\Choice(choices: ['meeting', 'absence', 'blocked', 'reminder', 'other'])]
    public string $eventType = 'other';

    #[Assert\NotBlank(message: 'La date de début est requise')]
    public string $startDate;

    #[Assert\NotBlank(message: 'La date de fin est requise')]
    public string $endDate;

    public bool $isAllDay = false;

    public ?string $recurrence = null;

    #[Assert\Regex(pattern: '/^#[0-9A-Fa-f]{6}$/', message: 'Format de couleur invalide (ex: #FF0000)')]
    public ?string $color = null;

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    public bool $isPrivate = false;

    /** Room ID - null for personal calendar */
    public ?int $roomId = null;

    /** Array of user IDs to add as participants */
    public array $participantIds = [];
}
