<?php

namespace App\UseCase\Invitation;

use App\Dto\Invitation\InvitationOutput;
use App\Dto\Invitation\SendInvitationInput;
use App\Entity\Invitation;
use App\Entity\User;
use App\Service\MailerService;
use App\Exception\ErrorMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class SendInvitationUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerService $mailerService
    ) {}

    public function execute(SendInvitationInput $input, User $admin): InvitationOutput
    {
        $entreprise = $admin->getEntreprise();
        if (!$entreprise) {
            throw new BadRequestHttpException(ErrorMessage::INVITATION_REQUIRES_ENTREPRISE);
        }

        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $input->email, 'entreprise' => $entreprise]);
        if ($existingUser) {
            throw new ConflictHttpException(ErrorMessage::INVITATION_USER_ALREADY_MEMBER);
        }

        $existingInvitation = $this->entityManager->getRepository(Invitation::class)
            ->findOneBy(['email' => $input->email, 'entreprise' => $entreprise, 'status' => 'pending']);
        if ($existingInvitation) {
            throw new ConflictHttpException(ErrorMessage::INVITATION_DUPLICATE);
        }

        $invitation = new Invitation();
        $invitation->setEmail($input->email);
        $invitation->setToken(bin2hex(random_bytes(32)));
        $invitation->setEntreprise($entreprise);
        $invitation->setInvitedBy($admin);
        $invitation->setExpiresAt(new \DateTime('+7 days'));

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        $this->mailerService->sendInvitationEmail($invitation, $admin);

        return InvitationOutput::fromEntity($invitation);
    }
}
