<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Dto\Account\AdminUserOutput;
use App\Entity\User;
use App\Service\EntrepriseContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class AdminUsersProvider implements ProviderInterface
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

        // Only owner and admin can see user list
        if (!$this->entrepriseContext->isAtLeastInCurrent(User::ROLE_OWNER)) {
            throw new AccessDeniedHttpException('Droits propriétaire ou administrateur requis');
        }

        $members = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u')
            ->join('u.userEntreprises', 'ue')
            ->where('ue.entreprise = :entreprise')
            ->setParameter('entreprise', $entreprise)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(
            fn(User $member) => AdminUserOutput::fromEntity($member),
            $members
        );
    }
}
