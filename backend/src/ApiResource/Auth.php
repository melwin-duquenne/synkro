<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model;
use App\Dto\Auth\RegisterInput;
use App\Dto\Auth\SetupEntrepriseInput;
use App\Dto\Auth\UserOutput;
use App\State\AuthProcessor;
use App\State\MeProvider;
use App\State\SetupEntrepriseProcessor;

#[ApiResource(
    operations: [
        new Post(
            uriTemplate: '/auth/login',
            openapi: new Model\Operation(
                summary: 'User login',
                description: 'Authenticate user and return JWT token',
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'email' => ['type' => 'string', 'example' => 'user@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'password123']
                                ],
                                'required' => ['email', 'password']
                            ]
                        ]
                    ])
                )
            ),
            name: 'login'
        ),
        new Post(
            uriTemplate: '/auth/register',
            input: RegisterInput::class,
            processor: AuthProcessor::class,
            name: 'register'
        ),
        new Get(
            uriTemplate: '/auth/me',
            output: UserOutput::class,
            provider: MeProvider::class,
            name: 'me'
        ),
        new Post(
            uriTemplate: '/auth/setup-entreprise',
            input: SetupEntrepriseInput::class,
            processor: SetupEntrepriseProcessor::class,
            name: 'setup_entreprise'
        )
    ]
)]
class Auth
{
    // This is a virtual resource for authentication endpoints
    // The /auth/login endpoint is handled by Symfony Security (json_login firewall)
}
