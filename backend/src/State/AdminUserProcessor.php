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
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $currentUser = $this->security->getUser();

        if (!$currentUser instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        // Only owner and admin can manage users
        if (!$currentUser->isAtLeast(User::ROLE_OWNER)) {
            throw new AccessDeniedHttpException('Owner or admin access required');
        }

        $targetUser = $this->entityManager->getRepository(User::class)->find($uriVariables['id']);

        if (!$targetUser) {
            throw new NotFoundHttpException('User not found');
        }

        // Ensure target user is in the same entreprise
        if ($targetUser->getEntreprise()?->getId() !== $currentUser->getEntreprise()?->getId()) {
            throw new AccessDeniedHttpException('Cannot manage users from another entreprise');
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
            throw new \InvalidArgumentException('Invalid input');
        }

        // Prevent user from changing their own role
        if ($targetUser->getId() === $currentUser->getId() && $data->role !== null) {
            throw new BadRequestHttpException('You cannot change your own role');
        }

        if ($data->role !== null) {
            // Check if current user can assign this role
            if (!$currentUser->canAssignRole($data->role)) {
                throw new AccessDeniedHttpException('You cannot assign this role');
            }

            // Prevent demoting an admin if current user is not admin
            if ($targetUser->getRole() === User::ROLE_ADMIN && $currentUser->getRole() !== User::ROLE_ADMIN) {
                throw new AccessDeniedHttpException('Only admins can change admin roles');
            }

            $targetUser->setRole($data->role);
        }

        if ($data->teamId !== null) {
            $team = $this->entityManager->getRepository(Team::class)->find($data->teamId);
            if (!$team || $team->getEntreprise()?->getId() !== $currentUser->getEntreprise()?->getId()) {
                throw new BadRequestHttpException('Invalid team');
            }
            $targetUser->setTeam($team);
        }

        $this->entityManager->flush();

        return AdminUserOutput::fromEntity($targetUser);
    }

    private function remove(User $currentUser, User $targetUser): null
    {
        if ($targetUser->getId() === $currentUser->getId()) {
            throw new BadRequestHttpException('You cannot delete your own account');
        }

        // Only admin can delete other admins
        if ($targetUser->getRole() === User::ROLE_ADMIN && $currentUser->getRole() !== User::ROLE_ADMIN) {
            throw new AccessDeniedHttpException('Only admins can delete other admins');
        }

        $this->entityManager->remove($targetUser);
        $this->entityManager->flush();

        return null;
    }
}
