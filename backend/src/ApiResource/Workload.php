<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\WorkloadProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/workload/daily',
            provider: WorkloadProvider::class
        ),
        new Get(
            uriTemplate: '/workload/daily/{date}',
            provider: WorkloadProvider::class
        ),
        new Get(
            uriTemplate: '/workload/weekly',
            provider: WorkloadProvider::class
        ),
        new Get(
            uriTemplate: '/workload/weekly/{date}',
            provider: WorkloadProvider::class
        )
    ]
)]
class Workload
{
    public string $status;
    public string $statusLabel;
    public string $color;
    public int $percentage;
    public float $totalHours;
    public int $eventCount;
    public int $meetingCount;
    public bool $hasShortBreaks;
    public int $standardHours;
    public string $period;
    public array $events;
    public array $modifiers;
}
