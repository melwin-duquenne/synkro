<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\ConfirmDeleteAccountInput;
use App\Entity\User;
use App\Service\MailerService;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class DeleteAccountProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private MailerService $mailerService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $operationName = $operation->getName();

        if ($operationName === 'account_delete_request') {
            return $this->request();
        }

        if ($data instanceof ConfirmDeleteAccountInput) {
            return $this->confirm($data);
        }

        return null;
    }

    private function request(): object
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $token = bin2hex(random_bytes(32));
        $user->setDeleteAccountToken($token);
        $user->setDeleteAccountExpiresAt(new \DateTime('+1 hour'));
        $this->entityManager->flush();

        $this->mailerService->sendDeleteAccountEmail($user, $token);

        return new class {
            public string $message = 'A confirmation email has been sent.';
        };
    }

    private function confirm(ConfirmDeleteAccountInput $data): object
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['deleteAccountToken' => $data->token]);

        if (!$user) {
            throw new BadRequestHttpException(ErrorMessage::TOKEN_INVALID);
        }

        if ($user->getDeleteAccountExpiresAt() < new \DateTime()) {
            $user->setDeleteAccountToken(null);
            $user->setDeleteAccountExpiresAt(null);
            $this->entityManager->flush();
            throw new BadRequestHttpException(ErrorMessage::TOKEN_EXPIRED);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return new class {
            public string $message = 'Account has been deleted successfully.';
        };
    }
}
