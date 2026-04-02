<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Invitation\InvitationOutput;
use App\Entity\Invitation;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class InvitationProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Vous devez être connecté pour effectuer cette action');
        }

        if ($user->getRole() !== 'admin') {
            throw new AccessDeniedHttpException('Droits administrateur requis');
        }

        $entreprise = $user->getEntreprise();
        if (!$entreprise) {
            return [];
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
