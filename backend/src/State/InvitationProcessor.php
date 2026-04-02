<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Invitation\AcceptInvitationInput;
use App\Dto\Invitation\SendInvitationInput;
use App\Entity\User;
use App\UseCase\Invitation\AcceptInvitationUseCase;
use App\UseCase\Invitation\CancelInvitationUseCase;
use App\Exception\ErrorMessage;
use App\UseCase\Invitation\SendInvitationUseCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private SendInvitationUseCase $sendInvitationUseCase,
        private CancelInvitationUseCase $cancelInvitationUseCase,
        private AcceptInvitationUseCase $acceptInvitationUseCase
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $operationName = $operation->getName();

        if ($operationName === 'invitation_accept') {
            return $this->acceptInvitationUseCase->execute($data);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }
        if ($user->getRole() !== 'admin') {
            throw new AccessDeniedHttpException(ErrorMessage::ADMIN_REQUIRED);
        }

        if ($data instanceof SendInvitationInput) {
            return $this->sendInvitationUseCase->execute($data, $user);
        }

        if ($operation instanceof Delete) {
            return $this->cancelInvitationUseCase->execute($uriVariables['id'], $user);
        }

        return null;
    }
}
