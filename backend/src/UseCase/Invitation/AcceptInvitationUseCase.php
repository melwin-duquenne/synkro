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
            $role = $invitation->getRole();
            $user->addToEntreprise($invitation->getEntreprise(), $role);
            $invitation->setStatus('accepted');
            $this->entityManager->flush();

            $slug = $invitation->getEntreprise()->getSlug();
            $name = $invitation->getEntreprise()->getName();

            return new class($name, $slug) {
                public string $message;
                public bool $accepted = true;
                public string $entrepriseSlug;
                public string $entrepriseName;

                public function __construct(string $entrepriseName, string $entrepriseSlug)
                {
                    $this->entrepriseName = $entrepriseName;
                    $this->entrepriseSlug = $entrepriseSlug;
                    $this->message = "You have joined {$entrepriseName}";
                }
            };
        }

        return new class($invitation->getEntreprise()->getName(), $invitation->getEntreprise()->getSlug() ?? '', $invitation->getEmail()) {
            public string $message;
            public bool $accepted = false;
            public string $entrepriseName;
            public string $entrepriseSlug;
            public string $email;

            public function __construct(string $entrepriseName, string $entrepriseSlug, string $email)
            {
                $this->entrepriseName = $entrepriseName;
                $this->entrepriseSlug = $entrepriseSlug;
                $this->email = $email;
                $this->message = "Please login or register to join {$entrepriseName}";
            }
        };
    }
}
