<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use App\Dto\UserSimpleOutput;
use App\State\EntrepriseMembersProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/entreprise/members',
            output: UserSimpleOutput::class,
            provider: EntrepriseMembersProvider::class,
            name: 'entreprise_members'
        )
    ]
)]
class EntrepriseMembers
{
    // Virtual resource for fetching entreprise members
}
