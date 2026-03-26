<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\RequestResetPasswordInput;
use App\Dto\Account\ResetPasswordInput;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ResetPasswordProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private MailerService $mailerService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof RequestResetPasswordInput) {
            return $this->request($data);
        }

        if ($data instanceof ResetPasswordInput) {
            return $this->confirm($data);
        }

        return null;
    }

    private function request(RequestResetPasswordInput $data): object
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email]);

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $user->setResetPasswordToken($token);
            $user->setResetPasswordExpiresAt(new \DateTime('+1 hour'));
            $this->entityManager->flush();

            $this->mailerService->sendResetPasswordEmail($user, $token);
        }

        // Always return success to prevent email enumeration
        return new class {
            public string $message = 'If this email exists, a reset link has been sent.';
        };
    }

    private function confirm(ResetPasswordInput $data): object
    {
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['resetPasswordToken' => $data->token]);

        if (!$user) {
            throw new BadRequestHttpException('Lien invalide ou expiré');
        }

        if ($user->getResetPasswordExpiresAt() < new \DateTime()) {
            $user->setResetPasswordToken(null);
            $user->setResetPasswordExpiresAt(null);
            $this->entityManager->flush();
            throw new BadRequestHttpException('Ce lien a expiré, veuillez en demander un nouveau');
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data->password);
        $user->setPassword($hashedPassword);
        $user->setResetPasswordToken(null);
        $user->setResetPasswordExpiresAt(null);
        $this->entityManager->flush();

        return new class {
            public string $message = 'Password has been reset successfully.';
        };
    }
}
