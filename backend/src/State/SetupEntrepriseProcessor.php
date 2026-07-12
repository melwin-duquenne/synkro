<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Auth\SetupEntrepriseInput;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class SetupEntrepriseProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security,
        private SlugGenerator $slugGenerator
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        // If user already has an entreprise, return the first one
        $existing = $user->getUserEntreprises()->first();
        if ($existing !== false) {
            return [
                'message' => 'Entreprise already configured',
                'entreprise' => [
                    'id' => $existing->getEntreprise()->getId(),
                    'name' => $existing->getEntreprise()->getName(),
                    'slug' => $existing->getEntreprise()->getSlug()
                ]
            ];
        }

        // Extract domain from email
        $emailParts = explode('@', $user->getEmail());
        $domain = $emailParts[1];

        // Create a new entreprise
        $companyName = $data->companyName ?? ucfirst(explode('.', $domain)[0]);
        $entreprise = new Entreprise();
        $entreprise->setDomain($domain);
        $entreprise->setName($companyName);
        $entreprise->setSlug($this->slugGenerator->generate($companyName));
        $this->entityManager->persist($entreprise);

        $user->setRole(User::ROLE_ADMIN);
        $user->addToEntreprise($entreprise, User::ROLE_ADMIN);
        $this->entityManager->flush();

        return [
            'message' => 'Entreprise configured successfully',
            'entreprise' => [
                'id' => $entreprise->getId(),
                'name' => $entreprise->getName(),
                'slug' => $entreprise->getSlug()
            ]
        ];
    }
}
