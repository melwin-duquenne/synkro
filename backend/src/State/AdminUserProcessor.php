<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Account\AdminUpdateUserInput;
use App\Dto\Account\AdminUserOutput;
use App\Entity\Team;
use App\Entity\User;
use App\Exception\ErrorMessage;
use App\Service\EntrepriseContext;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private EntrepriseContext $entrepriseContext
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException(ErrorMessage::AUTH_REQUIRED);
        }

        $entreprise = $this->entrepriseContext->getEntreprise();

        // Only owner and admin can manage users
        if (!$this->entrepriseContext->isAtLeastInCurrent(User::ROLE_OWNER)) {
            throw new AccessDeniedHttpException(ErrorMessage::OWNER_OR_ADMIN_REQUIRED);
        }

        $targetUser = $this->entityManager->getRepository(User::class)->find($uriVariables['id']);

        if (!$targetUser) {
            throw new NotFoundHttpException(ErrorMessage::USER_NOT_FOUND);
        }

        // Ensure target user is in the same entreprise
        if (!$targetUser->hasEntreprise($entreprise)) {
            throw new AccessDeniedHttpException(ErrorMessage::USER_WRONG_ENTREPRISE);
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $currentUser, $targetUser);
        }

        if ($operation instanceof Delete) {
            return $this->remove($currentUser, $targetUser);
        }

        return null;
    }

    private function update(mixed $data, User $currentUser, User $targetUser): AdminUserOutput
    {
        if (!$data instanceof AdminUpdateUserInput) {
            throw new \InvalidArgumentException(ErrorMessage::INVALID_DATA);
        }

        // Prevent user from changing their own role
        if ($targetUser->getId() === $currentUser->getId() && $data->role !== null) {
            throw new BadRequestHttpException(ErrorMessage::USER_SELF_ROLE_CHANGE);
        }

        if ($data->role !== null) {
            // Check if current user can assign this role (based on enterprise role)
            $currentRole = $entreprise ? $currentUser->getRoleInEntreprise($entreprise) ?? User::ROLE_USER : User::ROLE_USER;
            $tempUser = new User();
            $tempUser->setRole($currentRole);
            if (!$tempUser->canAssignRole($data->role)) {
                throw new AccessDeniedHttpException(ErrorMessage::USER_ROLE_NOT_ASSIGNABLE);
            }

            // Prevent demoting an admin if current user is not admin
            $targetRoleInEntreprise = $entreprise ? $targetUser->getRoleInEntreprise($entreprise) : null;
            if ($targetRoleInEntreprise === User::ROLE_ADMIN && $currentRole !== User::ROLE_ADMIN) {
                throw new AccessDeniedHttpException(ErrorMessage::USER_ADMIN_ROLE_ADMIN_ONLY);
            }

            $targetUser->setRole($data->role);
            $targetUser->addToEntreprise($entreprise, $data->role);
        }

        if ($data->teamId !== null) {
            $team = $this->entityManager->getRepository(Team::class)->find($data->teamId);
            if (!$team || ($entreprise && $team->getEntreprise()?->getId() !== $entreprise->getId())) {
                throw new BadRequestHttpException(ErrorMessage::USER_INVALID_TEAM);
            }
            $targetUser->setTeam($team);
        }

        $this->entityManager->flush();

        return AdminUserOutput::fromEntity($targetUser);
    }

    private function remove(User $currentUser, User $targetUser): null
    {
        if ($targetUser->getId() === $currentUser->getId()) {
            throw new BadRequestHttpException(ErrorMessage::USER_SELF_DELETE);
        }

        // Only admin can delete other admins
        if ($targetUser->getRole() === User::ROLE_ADMIN && $currentUser->getRole() !== User::ROLE_ADMIN) {
            throw new AccessDeniedHttpException(ErrorMessage::USER_ADMIN_DELETE_ADMIN_ONLY);
        }

        $this->entityManager->remove($targetUser);
        $this->entityManager->flush();

        return null;
    }
}
