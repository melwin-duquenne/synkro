<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\Dto\Invitation\AcceptInvitationInput;
use App\Dto\Invitation\InvitationOutput;
use App\Dto\Invitation\SendInvitationInput;
use App\State\InvitationProcessor;
use App\State\InvitationProvider;

#[ApiResource(
    operations: [
        new GetCollection(
            uriTemplate: '/invitations',
            output: InvitationOutput::class,
            provider: InvitationProvider::class,
            name: 'invitation_list'
        ),
        new Post(
            uriTemplate: '/invitations',
            input: SendInvitationInput::class,
            output: InvitationOutput::class,
            processor: InvitationProcessor::class,
            name: 'invitation_send'
        ),
        new Delete(
            uriTemplate: '/invitations/{id}',
            processor: InvitationProcessor::class,
            name: 'invitation_cancel'
        ),
        new Post(
            uriTemplate: '/invitations/accept',
            input: AcceptInvitationInput::class,
            processor: InvitationProcessor::class,
            name: 'invitation_accept'
        )
    ]
)]
class InvitationResource
{
    // Virtual resource for invitation endpoints
}
