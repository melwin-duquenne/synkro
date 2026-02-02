<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Entreprise\EntrepriseOutput;
use App\Dto\Entreprise\UpdateEntrepriseInput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class EntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        if ($user->getRole() !== 'admin') {
            throw new AccessDeniedHttpException('Admin access required');
        }

        $entreprise = $user->getEntreprise();
        if (!$entreprise) {
            throw new BadRequestHttpException('You do not belong to any entreprise');
        }

        if (!$data instanceof UpdateEntrepriseInput) {
            throw new \InvalidArgumentException('Invalid input');
        }

        $entreprise->setName($data->name);
        $this->entityManager->flush();

        return EntrepriseOutput::fromEntity($entreprise);
    }
}
