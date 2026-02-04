<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\State\DashboardStatsProvider;

#[ApiResource(
    shortName: 'Dashboard',
    operations: [
        new Get(
            uriTemplate: '/dashboard',
            provider: DashboardStatsProvider::class
        ),
    ],
    paginationEnabled: false
)]
class Dashboard
{
    // Ce n'est qu'une ressource API, pas une entité
    // Les données sont fournies par le DashboardStatsProvider
}
