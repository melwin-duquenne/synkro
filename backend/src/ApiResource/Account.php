<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Dto\Account\AdminUpdateUserInput;
use App\Dto\Account\AdminUserOutput;
use App\Dto\Account\ConfirmDeleteAccountInput;
use App\Dto\Account\CreateEntrepriseInput;
use App\Dto\Account\ProfileOutput;
use App\Dto\Account\RequestResetPasswordInput;
use App\Dto\Account\ResetPasswordInput;
use App\Dto\Account\UpdateProfileInput;
use App\Dto\EntrepriseSimpleOutput;
use App\Dto\Entreprise\AiSettingsInput;
use App\Dto\Entreprise\EntrepriseOutput;
use App\Dto\Entreprise\UpdateEntrepriseInput;
use App\State\AdminUserProcessor;
use App\State\AiSettingsProcessor;
use App\State\AdminUsersProvider;
use App\State\AvatarProcessor;
use App\State\CreateEntrepriseProcessor;
use App\State\DeleteAccountProcessor;
use App\State\EntrepriseProcessor;
use App\State\ProfileProcessor;
use App\State\ProfileProvider;
use App\State\ResetPasswordProcessor;

#[ApiResource(
    operations: [
        // Profile
        new Get(
            uriTemplate: '/account/profile',
            output: ProfileOutput::class,
            provider: ProfileProvider::class,
            name: 'account_profile'
        ),
        new Patch(
            uriTemplate: '/account/profile',
            input: UpdateProfileInput::class,
            output: ProfileOutput::class,
            processor: ProfileProcessor::class,
            name: 'account_profile_update'
        ),

        // Avatar
        new Post(
            uriTemplate: '/account/avatar',
            output: ProfileOutput::class,
            processor: AvatarProcessor::class,
            deserialize: false,
            name: 'account_avatar_upload'
        ),
        new Delete(
            uriTemplate: '/account/avatar',
            output: ProfileOutput::class,
            processor: AvatarProcessor::class,
            name: 'account_avatar_delete'
        ),

        // Reset password
        new Post(
            uriTemplate: '/account/reset-password/request',
            input: RequestResetPasswordInput::class,
            processor: ResetPasswordProcessor::class,
            name: 'account_reset_password_request'
        ),
        new Post(
            uriTemplate: '/account/reset-password/confirm',
            input: ResetPasswordInput::class,
            processor: ResetPasswordProcessor::class,
            name: 'account_reset_password_confirm'
        ),

        // Delete account
        new Post(
            uriTemplate: '/account/delete/request',
            processor: DeleteAccountProcessor::class,
            name: 'account_delete_request'
        ),
        new Post(
            uriTemplate: '/account/delete/confirm',
            input: ConfirmDeleteAccountInput::class,
            processor: DeleteAccountProcessor::class,
            name: 'account_delete_confirm'
        ),

        // Entreprises
        new Post(
            uriTemplate: '/account/entreprises',
            input: CreateEntrepriseInput::class,
            output: EntrepriseSimpleOutput::class,
            processor: CreateEntrepriseProcessor::class,
            name: 'account_create_entreprise'
        ),
        new Patch(
            uriTemplate: '/account/entreprise',
            input: UpdateEntrepriseInput::class,
            output: EntrepriseOutput::class,
            processor: EntrepriseProcessor::class,
            name: 'account_entreprise_update'
        ),
        new Patch(
            uriTemplate: '/account/entreprise/ai',
            input: AiSettingsInput::class,
            output: EntrepriseOutput::class,
            processor: AiSettingsProcessor::class,
            name: 'account_entreprise_ai_settings'
        ),

        // Admin
        new GetCollection(
            uriTemplate: '/account/admin/users',
            output: AdminUserOutput::class,
            provider: AdminUsersProvider::class,
            name: 'admin_users_list'
        ),
        new Patch(
            uriTemplate: '/account/admin/users/{id}',
            input: AdminUpdateUserInput::class,
            output: AdminUserOutput::class,
            processor: AdminUserProcessor::class,
            read: false,
            name: 'admin_user_update'
        ),
        new Delete(
            uriTemplate: '/account/admin/users/{id}',
            processor: AdminUserProcessor::class,
            read: false,
            name: 'admin_user_delete'
        )
    ]
)]
class Account
{
    // Virtual resource for account management endpoints
}
