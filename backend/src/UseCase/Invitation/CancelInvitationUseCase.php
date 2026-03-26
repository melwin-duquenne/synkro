<?php

namespace App\UseCase\Invitation;

use App\Entity\Invitation;
use App\Entity\User;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CancelInvitationUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function execute(int $id, User $admin): null
    {
        $invitation = $this->entityManager->getRepository(Invitation::class)->find($id);
        if (!$invitation) {
            throw new NotFoundHttpException(ErrorMessage::INVITATION_NOT_FOUND);
        }

        if ($invitation->getEntreprise()->getId() !== $admin->getEntreprise()?->getId()) {
            throw new AccessDeniedHttpException(ErrorMessage::INVITATION_WRONG_ENTREPRISE);
        }

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return null;
    }
}
