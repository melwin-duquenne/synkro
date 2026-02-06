<?php

namespace App\Dto;

class DashboardData
{
    public function __construct(
        public array $workload,
        public array $upcomingEvents,
        public array $todayEvents,
        public array $tasks,
        public array $recentRooms,
        public array $teamAvailability,
        public array $statistics,
        public array $notifications,
    ) {}
}
