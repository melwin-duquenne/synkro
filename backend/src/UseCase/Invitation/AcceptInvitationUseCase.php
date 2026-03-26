<?php

namespace App\UseCase\Invitation;

use App\Dto\Invitation\AcceptInvitationInput;
use App\Entity\Invitation;
use App\Entity\User;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AcceptInvitationUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private Security $security
    ) {}

    public function execute(AcceptInvitationInput $input): object
    {
        $invitation = $this->entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $input->token]);

        if (!$invitation) {
            throw new BadRequestHttpException(ErrorMessage::INVITATION_INVALID);
        }

        if ($invitation->getStatus() !== 'pending') {
            throw new BadRequestHttpException(ErrorMessage::INVITATION_ALREADY_USED);
        }

        if ($invitation->isExpired()) {
            $invitation->setStatus('expired');
            $this->entityManager->flush();
            throw new BadRequestHttpException(ErrorMessage::INVITATION_EXPIRED);
        }

        $user = $this->security->getUser();

        if ($user instanceof User) {
            $user->setEntreprise($invitation->getEntreprise());
            $invitation->setStatus('accepted');
            $this->entityManager->flush();

            return new class($invitation->getEntreprise()->getName()) {
                public string $message;
                public bool $accepted = true;

                public function __construct(string $entrepriseName)
                {
                    $this->message = "You have joined {$entrepriseName}";
                }
            };
        }

        return new class($invitation->getEntreprise()->getName(), $invitation->getEmail()) {
            public string $message;
            public bool $accepted = false;
            public string $entrepriseName;
            public string $email;

            public function __construct(string $entrepriseName, string $email)
            {
                $this->entrepriseName = $entrepriseName;
                $this->email = $email;
                $this->message = "Please login or register to join {$entrepriseName}";
            }
        };
    }
}
