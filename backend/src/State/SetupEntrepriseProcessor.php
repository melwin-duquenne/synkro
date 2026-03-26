<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Auth\SetupEntrepriseInput;
use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SetupEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        // If user already has an entreprise, return it
        if ($user->getEntreprise()) {
            return [
                'message' => 'Entreprise already configured',
                'entreprise' => [
                    'id' => $user->getEntreprise()->getId(),
                    'name' => $user->getEntreprise()->getName()
                ]
            ];
        }

        // Extract domain from email
        $emailParts = explode('@', $user->getEmail());
        $domain = $emailParts[1];

        // Create a new entreprise for this user
        $entreprise = new Entreprise();
        $entreprise->setDomain($domain);
        $companyName = $data->companyName ?? ucfirst(explode('.', $domain)[0]);
        $entreprise->setName($companyName);
        $this->entityManager->persist($entreprise);

        $user->setEntreprise($entreprise);
        $this->entityManager->flush();

        return [
            'message' => 'Entreprise configured successfully',
            'entreprise' => [
                'id' => $entreprise->getId(),
                'name' => $entreprise->getName()
            ]
        ];
    }
}
