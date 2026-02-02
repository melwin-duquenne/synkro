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
        $admin = $this->security->getUser();

        if (!$admin instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }

        if ($admin->getRole() !== 'admin') {
            throw new AccessDeniedHttpException('Admin access required');
        }

        $targetUser = $this->entityManager->getRepository(User::class)->find($uriVariables['id']);

        if (!$targetUser) {
            throw new NotFoundHttpException('User not found');
        }

        // Ensure target user is in the same entreprise
        if ($targetUser->getEntreprise()?->getId() !== $admin->getEntreprise()?->getId()) {
            throw new AccessDeniedHttpException('Cannot manage users from another entreprise');
        }

        if ($operation instanceof Patch) {
            return $this->update($data, $admin, $targetUser);
        }

        if ($operation instanceof Delete) {
            return $this->remove($admin, $targetUser);
        }

        return null;
    }

    private function update(mixed $data, User $admin, User $targetUser): AdminUserOutput
    {
        if (!$data instanceof AdminUpdateUserInput) {
            throw new \InvalidArgumentException('Invalid input');
        }

        // Prevent admin from demoting themselves
        if ($targetUser->getId() === $admin->getId() && $data->role !== null && $data->role !== 'admin') {
            throw new BadRequestHttpException('You cannot change your own admin role');
        }

        if ($data->role !== null) {
            $targetUser->setRole($data->role);
        }

        if ($data->teamId !== null) {
            $team = $this->entityManager->getRepository(Team::class)->find($data->teamId);
            if (!$team || $team->getEntreprise()?->getId() !== $admin->getEntreprise()?->getId()) {
                throw new BadRequestHttpException('Invalid team');
            }
            $targetUser->setTeam($team);
        }

        $this->entityManager->flush();

        return AdminUserOutput::fromEntity($targetUser);
    }

    private function remove(User $admin, User $targetUser): null
    {
        if ($targetUser->getId() === $admin->getId()) {
            throw new BadRequestHttpException('You cannot delete your own account');
        }

        $this->entityManager->remove($targetUser);
        $this->entityManager->flush();

        return null;
    }
}
