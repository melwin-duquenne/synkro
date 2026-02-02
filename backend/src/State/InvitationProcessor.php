<?php

namespace App\State;

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Post;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Invitation\AcceptInvitationInput;
use App\Dto\Invitation\InvitationOutput;
use App\Dto\Invitation\SendInvitationInput;
use App\Entity\Invitation;
use App\Entity\User;
use App\Service\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class InvitationProcessor implements ProcessorInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $entityManager,
        private MailerService $mailerService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $operationName = $operation->getName();

        if ($operationName === 'invitation_accept') {
            return $this->accept($data);
        }

        // All other operations require admin
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new AccessDeniedHttpException('Not authenticated');
        }
        if ($user->getRole() !== 'admin') {
            throw new AccessDeniedHttpException('Admin access required');
        }

        if ($data instanceof SendInvitationInput) {
            return $this->send($data, $user);
        }

        if ($operation instanceof Delete) {
            return $this->cancel($user, $uriVariables['id']);
        }

        return null;
    }

    private function send(SendInvitationInput $data, User $admin): InvitationOutput
    {
        $entreprise = $admin->getEntreprise();
        if (!$entreprise) {
            throw new BadRequestHttpException('You must belong to an entreprise to send invitations');
        }

        // Check if email is already in the entreprise
        $existingUser = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => $data->email, 'entreprise' => $entreprise]);
        if ($existingUser) {
            throw new ConflictHttpException('This user is already in your entreprise');
        }

        // Check for existing pending invitation
        $existingInvitation = $this->entityManager->getRepository(Invitation::class)
            ->findOneBy(['email' => $data->email, 'entreprise' => $entreprise, 'status' => 'pending']);
        if ($existingInvitation) {
            throw new ConflictHttpException('An invitation is already pending for this email');
        }

        $invitation = new Invitation();
        $invitation->setEmail($data->email);
        $invitation->setToken(bin2hex(random_bytes(32)));
        $invitation->setEntreprise($entreprise);
        $invitation->setInvitedBy($admin);
        $invitation->setExpiresAt(new \DateTime('+7 days'));

        $this->entityManager->persist($invitation);
        $this->entityManager->flush();

        $this->mailerService->sendInvitationEmail($invitation, $admin);

        return InvitationOutput::fromEntity($invitation);
    }

    private function cancel(User $admin, int $id): null
    {
        $invitation = $this->entityManager->getRepository(Invitation::class)->find($id);
        if (!$invitation) {
            throw new NotFoundHttpException('Invitation not found');
        }

        if ($invitation->getEntreprise()->getId() !== $admin->getEntreprise()?->getId()) {
            throw new AccessDeniedHttpException('Cannot manage invitations from another entreprise');
        }

        $this->entityManager->remove($invitation);
        $this->entityManager->flush();

        return null;
    }

    private function accept(mixed $data): object
    {
        if (!$data instanceof AcceptInvitationInput) {
            throw new BadRequestHttpException('Invalid input');
        }

        $invitation = $this->entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $data->token]);

        if (!$invitation) {
            throw new BadRequestHttpException('Invalid or expired invitation token');
        }

        if ($invitation->getStatus() !== 'pending') {
            throw new BadRequestHttpException('This invitation has already been used');
        }

        if ($invitation->isExpired()) {
            $invitation->setStatus('expired');
            $this->entityManager->flush();
            throw new BadRequestHttpException('This invitation has expired');
        }

        // Check if the current user is authenticated
        $user = $this->security->getUser();

        if ($user instanceof User) {
            // Authenticated user: attach to entreprise
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

        // Not authenticated: return invitation info so frontend can redirect to login/register
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
