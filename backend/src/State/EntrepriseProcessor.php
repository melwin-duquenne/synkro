<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Entreprise\EntrepriseOutput;
use App\Dto\Entreprise\UpdateEntrepriseInput;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Service\EncryptionService;
use App\Service\EntrepriseContext;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private EntrepriseContext $entrepriseContext,
        private SlugGenerator $slugGenerator,
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

        if (!$data instanceof UpdateEntrepriseInput) {
            throw new \InvalidArgumentException(ErrorMessage::INVALID_DATA);
        }

        $entreprise->setName($data->name);
        $entreprise->setSlug($this->slugGenerator->generate($data->name, $entreprise->getId()));

        if (isset($data->aiEnabled)) {
            $entreprise->setAiEnabled((bool)$data->aiEnabled);
        }
        if (isset($data->aiProvider)) {
            $entreprise->setAiProvider($data->aiProvider);
        }
        if (isset($data->aiApiKey) && $data->aiApiKey !== '') {
            $entreprise->setAiApiKey($this->encryptionService->encrypt($data->aiApiKey));
        }

        $this->entityManager->flush();

        return EntrepriseOutput::fromEntity($entreprise);
    }
}
