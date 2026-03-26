<?php

namespace App\Security;

use App\Exception\ErrorMessage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LoginFailureHandler implements AuthenticationFailureHandlerInterface
{
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse(
            ['hydra:description' => ErrorMessage::INVALID_CREDENTIALS],
            JsonResponse::HTTP_UNAUTHORIZED
        );
    }
}
