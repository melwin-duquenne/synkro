<?php

namespace App\Service;

use App\Entity\Entreprise;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EntrepriseContext
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private RequestStack $requestStack,
        private Security $security
    ) {}

    public function getEntreprise(): Entreprise
    {
        $request = $this->requestStack->getCurrentRequest();
        $slug = $request?->headers->get('X-Entreprise-Slug');

        if (!$slug) {
            throw new BadRequestHttpException('En-tête X-Entreprise-Slug requis');
        }

        $entreprise = $this->entityManager
            ->getRepository(Entreprise::class)
            ->findOneBy(['slug' => $slug]);

        if (!$entreprise) {
            throw new NotFoundHttpException('Entreprise introuvable');
        }

        $user = $this->security->getUser();
        if (!$user instanceof User || !$user->hasEntreprise($entreprise)) {
            throw new AccessDeniedHttpException('Vous n\'êtes pas membre de cette entreprise');
        }

        return $entreprise;
    }

    public function getRoleInCurrent(): string
    {
        $entreprise = $this->getEntreprise();
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Authentification requise');
        }

        return $user->getRoleInEntreprise($entreprise) ?? User::ROLE_USER;
    }

    public function isAtLeastInCurrent(string $minRole): bool
    {
        $roleHierarchy = [User::ROLE_USER, User::ROLE_EDITOR, User::ROLE_OWNER, User::ROLE_ADMIN];
        $role = $this->getRoleInCurrent();
        $userLevel = array_search($role, $roleHierarchy);
        $requiredLevel = array_search($minRole, $roleHierarchy);
        if ($userLevel === false || $requiredLevel === false) {
            return false;
        }
        return $userLevel >= $requiredLevel;
    }
}
