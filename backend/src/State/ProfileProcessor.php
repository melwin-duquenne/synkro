<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\ProfileOutput;
use App\Dto\Account\UpdateProfileInput;
use App\Entity\User;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ProfileProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        if (!$data instanceof UpdateProfileInput) {
            throw new \InvalidArgumentException(ErrorMessage::INVALID_DATA);
        }

        if ($data->displayName !== null) {
            $user->setDisplayName($data->displayName);
        }

        if ($data->email !== null && $data->email !== $user->getEmail()) {
            $existing = $this->entityManager->getRepository(User::class)
                ->findOneBy(['email' => $data->email]);

            if ($existing) {
                throw new ConflictHttpException(ErrorMessage::USER_EMAIL_TAKEN);
            }

            $user->setEmail($data->email);
        }

        $this->entityManager->flush();

        return ProfileOutput::fromEntity($user);
    }
}
