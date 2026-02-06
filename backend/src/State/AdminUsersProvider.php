<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Account\AdminUserOutput;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminUsersProvider implements ProviderInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        // Only owner and admin can see user list
        if (!$user->isAtLeast(User::ROLE_OWNER)) {
            throw new AccessDeniedHttpException('Owner or admin access required');
        }

        $entreprise = $user->getEntreprise();

        if (!$entreprise) {
            return [];
        }

        $members = $this->entityManager->getRepository(User::class)->findBy(
            ['entreprise' => $entreprise],
            ['createdAt' => 'DESC']
        );

        return array_map(
            fn(User $member) => AdminUserOutput::fromEntity($member),
            $members
        );
    }
}
