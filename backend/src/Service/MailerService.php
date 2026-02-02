<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class MailerService
{
    public function __construct(
        private MailerInterface $mailer,
        private string $frontendUrl
    ) {}

    public function sendResetPasswordEmail(User $user, string $token): void
    {
        $resetLink = $this->frontendUrl . '/reset-password?token=' . $token;

        $email = (new Email())
            ->from('noreply@synkro.app')
            ->to($user->getEmail())
            ->subject('Synkro — Réinitialisation de votre mot de passe')
            ->text(
                "Bonjour {$user->getDisplayName()},\n\n" .
                "Vous avez demandé la réinitialisation de votre mot de passe.\n\n" .
                "Cliquez sur le lien suivant pour définir un nouveau mot de passe :\n" .
                "{$resetLink}\n\n" .
                "Ce lien est valable pendant 1 heure.\n\n" .
                "Si vous n'avez pas fait cette demande, ignorez cet email.\n\n" .
                "— L'équipe Synkro"
            );

        $this->mailer->send($email);
    }

    public function sendDeleteAccountEmail(User $user, string $token): void
    {
        $confirmLink = $this->frontendUrl . '/confirm-delete?token=' . $token;

        $email = (new Email())
            ->from('noreply@synkro.app')
            ->to($user->getEmail())
            ->subject('Synkro — Confirmation de suppression de compte')
            ->text(
                "Bonjour {$user->getDisplayName()},\n\n" .
                "Vous avez demandé la suppression de votre compte Synkro.\n\n" .
                "Pour confirmer cette action irréversible, cliquez sur le lien suivant :\n" .
                "{$confirmLink}\n\n" .
                "Ce lien est valable pendant 1 heure.\n\n" .
                "Si vous n'avez pas fait cette demande, ignorez cet email et votre compte restera intact.\n\n" .
                "— L'équipe Synkro"
            );

        $this->mailer->send($email);
    }
}
