<?php

namespace App\Command;

use App\Entity\Entreprise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:ai:reset-token-counters',
    description: 'Remet à zéro les compteurs de tokens IA pour toutes les entreprises (à planifier le 1er de chaque mois).'
)]
class ResetAiTokenCountersCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTimeImmutable();

        $updated = $this->entityManager->createQueryBuilder()
            ->update(Entreprise::class, 'e')
            ->set('e.aiTokensUsed', 0)
            ->set('e.aiTokensResetAt', ':now')
            ->where('e.aiMode = :mode')
            ->setParameter('now', $now)
            ->setParameter('mode', 'platform')
            ->getQuery()
            ->execute();

        $io->success("Compteurs réinitialisés pour {$updated} entreprise(s) en mode plateforme.");

        return Command::SUCCESS;
    }
}
