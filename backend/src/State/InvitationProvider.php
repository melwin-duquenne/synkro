<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Invitation\InvitationOutput;
use App\Entity\Invitation;
use App\Entity\User;
use App\Service\EntrepriseContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvitationProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private EntrepriseContext $entrepriseContext
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        $entreprise = $this->entrepriseContext->getEntreprise();

        if ($this->entrepriseContext->getRoleInCurrent() !== User::ROLE_ADMIN) {
            throw new AccessDeniedHttpException('Droits administrateur requis');
        }

        $invitations = $this->entityManager->getRepository(Invitation::class)->findBy(
            ['entreprise' => $entreprise],
            ['createdAt' => 'DESC']
        );

        return array_map(
            fn(Invitation $invitation) => InvitationOutput::fromEntity($invitation),
            $invitations
        );
    }
}
