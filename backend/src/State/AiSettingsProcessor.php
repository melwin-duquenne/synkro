<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Entreprise\AiSettingsInput;
use App\Dto\Entreprise\EntrepriseOutput;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Service\EncryptionService;
use App\Service\EntrepriseContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AiSettingsProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private EntrepriseContext $entrepriseContext,
        private EncryptionService $encryptionService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $entreprise = $this->entrepriseContext->getEntreprise();

        if ($this->entrepriseContext->getRoleInCurrent() !== User::ROLE_ADMIN) {
            throw new AccessDeniedHttpException(ErrorMessage::ADMIN_REQUIRED);
        }

        if (!$data instanceof AiSettingsInput) {
            throw new \InvalidArgumentException(ErrorMessage::INVALID_DATA);
        }

        if ($data->aiEnabled !== null) {
            $entreprise->setAiEnabled($data->aiEnabled);
        }
        if ($data->aiMode !== null) {
            $entreprise->setAiMode($data->aiMode);
        }
        if ($data->aiProvider !== null) {
            $entreprise->setAiProvider($data->aiProvider);
        }
        if ($data->aiApiKey !== null && $data->aiApiKey !== '') {
            $entreprise->setAiApiKey($this->encryptionService->encrypt($data->aiApiKey));
        }

        $this->entityManager->flush();

        return EntrepriseOutput::fromEntity($entreprise);
    }
}
