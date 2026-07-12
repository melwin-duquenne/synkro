<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\CreateEntrepriseInput;
use App\Dto\EntrepriseSimpleOutput;
use App\Entity\Entreprise;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Service\SlugGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CreateEntrepriseProcessor implements ProcessorInterface
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

        if (!$data instanceof CreateEntrepriseInput) {
            throw new BadRequestHttpException(ErrorMessage::INVALID_DATA);
        }

        $entreprise = new Entreprise();
        $entreprise->setName($data->name);
        $entreprise->setSlug($this->slugGenerator->generate($data->name));
        $entreprise->setDomain($data->domain);

        $this->entityManager->persist($entreprise);

        $user->addToEntreprise($entreprise, User::ROLE_ADMIN);

        $this->entityManager->flush();

        return EntrepriseSimpleOutput::fromEntity($entreprise);
    }
}
